<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;
use ZipArchive;

class GbifImportDownload extends Command
{
    protected $signature = 'gbif:import-download
                            {key : The GBIF download key}
                            {--file= : Path to an already-downloaded zip (skips polling and download)}
                            {--skip-staging : Skip LOAD DATA INFILE (re-use staging table already populated)}
                            {--skip-cleanup : Keep gbif_staging populated after import}
                            {--multimedia-only= : Path to multimedia.txt — skip everything else and only import multimedia}';

    protected $description = 'Poll, download, and import a GBIF DWCA download into occurrences';

    private string $storageDir;

    public function handle(): int
    {
        $this->storageDir = storage_path('gbif');

        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }

        $key     = $this->argument('key');
        $zipPath = $this->option('file');

        // Multimedia-only mode
        if ($multimediaOnly = $this->option('multimedia-only')) {
            if (!file_exists($multimediaOnly)) {
                $this->error("File not found: {$multimediaOnly}");
                return self::FAILURE;
            }
            $this->importMultimedia($multimediaOnly);
            return self::SUCCESS;
        }

        // Step 1: poll + download
        if (!$this->option('skip-staging')) {
            if (!$zipPath) {
                $zipPath = $this->waitAndDownload($key);
                if (!$zipPath) {
                    return self::FAILURE;
                }
            }

            // Step 2: extract occurrence.txt and parse meta.xml
            [$csvPath, $colList, $ignoreHeader, $multimediaPath] = $this->extractAndMapColumns($zipPath);
            if (!$csvPath) {
                return self::FAILURE;
            }

            // Step 3: LOAD DATA LOCAL INFILE → gbif_staging
            if (!$this->loadIntoStaging($csvPath, $colList)) {
                return self::FAILURE;
            }
        } else {
            $multimediaPath = null;
        }

        // Step 4: staging → locality_groups + occurrences
        $this->processStaging();

        // Step 4b: import multimedia if extracted
        if (!empty($multimediaPath) && file_exists($multimediaPath)) {
            $this->importMultimedia($multimediaPath);
        }

        // Step 5: cleanup
        if (!$this->option('skip-cleanup')) {
            $this->info('Truncating gbif_staging...');
            DB::statement('TRUNCATE TABLE gbif_staging');
        }

        $this->info('Done.');
        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Poll + Download
    // -------------------------------------------------------------------------

    private function waitAndDownload(string $key): ?string
    {
        $zipPath = "{$this->storageDir}/{$key}.zip";

        if (file_exists($zipPath)) {
            $this->info("ZIP already exists, skipping download: {$zipPath}");
            return $zipPath;
        }

        $this->info("Polling GBIF download status for: {$key}");

        for ($attempt = 0; $attempt < 480; $attempt++) { // max 8 hours
            $response = Http::get("https://api.gbif.org/v1/occurrence/download/{$key}");

            if (!$response->successful()) {
                $this->error('Status check failed: ' . $response->status());
                return null;
            }

            $data   = $response->json();
            $status = $data['status'] ?? 'UNKNOWN';
            $this->line('[' . now()->format('H:i:s') . "] {$status}");

            if ($status === 'SUCCEEDED') {
                return $this->downloadZip($data['downloadLink'], $zipPath);
            }

            if (in_array($status, ['FAILED', 'KILLED', 'CANCELLED'])) {
                $this->error("Download ended with status: {$status}");
                return null;
            }

            sleep(60);
        }

        $this->error('Timed out waiting for GBIF download');
        return null;
    }

    private function downloadZip(string $url, string $targetPath): ?string
    {
        $this->info("Downloading: {$url}");

        $user = config('gbif.username');
        $pass = config('gbif.password');

        $fp = fopen($targetPath, 'wb');
        if (!$fp) {
            $this->error("Cannot open for writing: {$targetPath}");
            return null;
        }

        $lastReported = 0;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        if ($user && $pass) {
            curl_setopt($ch, CURLOPT_USERPWD, "{$user}:{$pass}");
        }
        curl_setopt($ch, CURLOPT_NOPROGRESS, false);
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($r, $dlTotal, $dlNow) use (&$lastReported) {
            if ($dlTotal > 0 && $dlNow - $lastReported > 100 * 1024 * 1024) {
                $pct = round($dlNow / $dlTotal * 100);
                $this->line('  ' . $this->bytes($dlNow) . ' / ' . $this->bytes($dlTotal) . " ({$pct}%)");
                $lastReported = $dlNow;
            }
        });

        $ok  = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if (!$ok || $err) {
            $this->error("Download failed: {$err}");
            unlink($targetPath);
            return null;
        }

        $this->info('Downloaded: ' . $this->bytes(filesize($targetPath)));
        return $targetPath;
    }

    // -------------------------------------------------------------------------
    // Extract DWCA and build column mapping from meta.xml
    // -------------------------------------------------------------------------

    private function extractAndMapColumns(string $zipPath): array
    {
        $this->info("Opening ZIP: {$zipPath}");

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            $this->error('Cannot open ZIP');
            return [null, null];
        }

        // Read meta.xml
        $metaXml = $zip->getFromName('meta.xml');
        if (!$metaXml) {
            $this->error('meta.xml not found in ZIP');
            $zip->close();
            return [null, null];
        }

        // Parse field → index mapping
        $xml        = new SimpleXMLElement($metaXml);
        $xml->registerXPathNamespace('dwc', 'http://rs.tdwg.org/dwc/text/');
        $core       = $xml->core ?? $xml->children('http://rs.tdwg.org/dwc/text/')->core;
        $fieldNodes = $core->field ?? [];
        $totalCols  = 0;

        // term → staging column
        $termMap = $this->termMap();
        $indexMap = []; // index → staging column name (or @dummy)

        foreach ($fieldNodes as $field) {
            $index = (int) $field['index'];
            $term  = (string) $field['term'];
            $short = basename(str_replace('\\', '/', $term)); // last segment
            $indexMap[$index] = $termMap[$short] ?? '@dummy';
            $totalCols = max($totalCols, $index + 1);
        }

        // id field (index 0 is usually gbifID)
        if (isset($core->id)) {
            $idIdx = (int) $core->id['index'];
            if (!isset($indexMap[$idIdx])) {
                $indexMap[$idIdx] = 'gbif_id';
            }
        }

        // Build ordered column list (fill gaps with @dummy)
        $colList = [];
        for ($i = 0; $i < $totalCols; $i++) {
            $colList[] = $indexMap[$i] ?? '@dummy';
        }

        $this->line('  Columns in DWCA: ' . count($colList));

        // Get occurrence file name from meta.xml
        $location  = (string) ($core->files->location ?? 'occurrence.txt');
        $csvTarget = "{$this->storageDir}/" . basename($location);

        // Stream-extract occurrence file (can be large)
        $this->info("Extracting {$location} → {$csvTarget}");
        $src  = $zip->getStream($location);
        $dest = fopen($csvTarget, 'wb');
        stream_copy_to_stream($src, $dest);
        fclose($src);
        fclose($dest);

        $this->info('Extracted: ' . $this->bytes(filesize($csvTarget)));

        // Check if the DWCA has a header line to skip
        $ignoreHeader = (int) ($core['ignoreHeaderLines'] ?? 0);

        // Also extract multimedia extension if present
        $multimediaPath = null;
        foreach ($xml->extension ?? [] as $ext) {
            $rowType = (string) ($ext['rowType'] ?? '');
            if (str_contains($rowType, 'Multimedia') || str_contains($rowType, 'multimedia')) {
                $mLoc   = (string) ($ext->files->location ?? '');
                if ($mLoc) {
                    $mTarget = "{$this->storageDir}/" . basename($mLoc);
                    $mSrc    = $zip->getStream($mLoc);
                    if ($mSrc) {
                        $mDest = fopen($mTarget, 'wb');
                        stream_copy_to_stream($mSrc, $mDest);
                        fclose($mSrc);
                        fclose($mDest);
                        $multimediaPath = $mTarget;
                        $this->info("Extracted multimedia: " . $this->bytes(filesize($mTarget)));
                    }
                }
                break;
            }
        }

        $zip->close();

        return [$csvTarget, $colList, $ignoreHeader, $multimediaPath];
    }

    // -------------------------------------------------------------------------
    // LOAD DATA LOCAL INFILE
    // -------------------------------------------------------------------------

    private function loadIntoStaging(string $csvPath, array $colList): bool
    {
        $this->info('Loading into gbif_staging...');

        DB::statement('TRUNCATE TABLE gbif_staging');

        $colString  = implode(', ', $colList);
        $escapedPath = str_replace('\\', '/', $csvPath);

        try {
            DB::statement("
                LOAD DATA LOCAL INFILE '{$escapedPath}'
                INTO TABLE gbif_staging
                CHARACTER SET utf8mb4
                FIELDS TERMINATED BY '\\t' OPTIONALLY ENCLOSED BY '\"'
                LINES TERMINATED BY '\\n'
                IGNORE 1 LINES
                ({$colString})
                SET synced_at = NOW()
            ");
        } catch (\Exception $e) {
            $this->error('LOAD DATA LOCAL INFILE failed: ' . $e->getMessage());
            $this->warn('Ensure MySQL has local_infile=ON and config/database.php has PDO::MYSQL_ATTR_LOCAL_INFILE => true');
            return false;
        }

        $count = DB::table('gbif_staging')->count();
        $this->info("Staged {$count} records");
        return true;
    }

    // -------------------------------------------------------------------------
    // staging → locality_groups + occurrences
    // -------------------------------------------------------------------------

    private function processStaging(): void
    {
        // Nullify country_code values that are not valid ISO 3166-1 alpha-2
        DB::statement("UPDATE gbif_staging SET country_code = NULL WHERE country_code NOT REGEXP '^[A-Z]{2}$'");

        $this->info('Step 1/3: Creating locality groups from staging...');

        // Replicates LocalityGroup::hashFromOccurrence() in SQL:
        // SHA1 of non-empty lowercased fields joined by '|'
        // NULLIF(LOWER(TRIM(...)), '') converts empty → NULL so CONCAT_WS skips them
        // COALESCE(verbatim_locality, locality): use verbatimLocality if present,
        // else fall back to locality (DwC interpreted field). Both map to verbatim_locality
        // column in locality_groups for grouping and display.
        DB::statement("
            INSERT IGNORE INTO locality_groups
                (group_hash, country_code, state_province, county, municipality,
                 verbatim_locality, locality_string, created_at, updated_at)
            SELECT
                SHA1(CONCAT_WS('|',
                    NULLIF(LOWER(TRIM(COALESCE(country_code, ''))), ''),
                    NULLIF(LOWER(TRIM(COALESCE(state_province, ''))), ''),
                    NULLIF(LOWER(TRIM(COALESCE(county, ''))), ''),
                    NULLIF(LOWER(TRIM(COALESCE(municipality, ''))), ''),
                    NULLIF(LOWER(TRIM(COALESCE(NULLIF(TRIM(verbatim_locality),''), NULLIF(TRIM(locality),''), ''))), '')
                )) AS group_hash,
                MIN(country_code),
                MIN(state_province),
                MIN(county),
                MIN(municipality),
                MIN(COALESCE(NULLIF(TRIM(verbatim_locality),''), NULLIF(TRIM(locality),''))) ,
                MIN(TRIM(CONCAT_WS(', ',
                    NULLIF(country_code, ''),
                    NULLIF(state_province, ''),
                    NULLIF(county, ''),
                    NULLIF(municipality, ''),
                    NULLIF(COALESCE(NULLIF(TRIM(verbatim_locality),''), NULLIF(TRIM(locality),'')), '')
                ))),
                NOW(),
                NOW()
            FROM gbif_staging
            WHERE basis_of_record = 'PRESERVED_SPECIMEN'
            GROUP BY group_hash
        ");

        $groupCount = DB::table('locality_groups')->count();
        $this->info("  Locality groups total: {$groupCount}");

        $this->info('Step 2/3: Upserting occurrences (may take a while)...');

        DB::statement("
            INSERT INTO occurrences
                (gbif_occurrence_key, dataset_key, publisher_key, basis_of_record,
                 institution_code, collection_code, catalog_number, recorded_by,
                 event_date, country, country_code, state_province, county, municipality,
                 verbatim_locality, island, island_group, water_body,
                 scientific_name, taxon_rank, kingdom, family,
                 gbif_decimal_latitude, gbif_decimal_longitude,
                 gbif_coordinate_uncertainty_m, gbif_geodetic_datum,
                 locality_group_id, georef_status, synced_at, created_at, updated_at)
            SELECT
                CAST(s.gbif_id AS CHAR),
                s.dataset_key,
                s.publishing_org_key,
                s.basis_of_record,
                s.institution_code,
                s.collection_code,
                s.catalog_number,
                s.recorded_by,
                s.event_date,
                s.country,
                s.country_code,
                s.state_province,
                s.county,
                s.municipality,
                COALESCE(NULLIF(TRIM(s.verbatim_locality),''), NULLIF(TRIM(s.locality),'')),
                s.island,
                s.island_group,
                s.water_body,
                s.scientific_name,
                s.taxon_rank,
                s.kingdom,
                s.family,
                IF(s.has_coordinate = 'true', s.decimal_latitude, NULL),
                IF(s.has_coordinate = 'true', s.decimal_longitude, NULL),
                IF(s.has_coordinate = 'true', s.coordinate_uncertainty_m, NULL),
                NULLIF(s.geodetic_datum, ''),
                lg.id,
                IF(s.has_coordinate = 'true', 'gbif_georeferenced', 'ungeoreferenced'),
                NOW(), NOW(), NOW()
            FROM gbif_staging s
            JOIN locality_groups lg ON lg.group_hash = SHA1(CONCAT_WS('|',
                NULLIF(LOWER(TRIM(COALESCE(s.country_code, ''))), ''),
                NULLIF(LOWER(TRIM(COALESCE(s.state_province, ''))), ''),
                NULLIF(LOWER(TRIM(COALESCE(s.county, ''))), ''),
                NULLIF(LOWER(TRIM(COALESCE(s.municipality, ''))), ''),
                NULLIF(LOWER(TRIM(COALESCE(NULLIF(TRIM(s.verbatim_locality),''), NULLIF(TRIM(s.locality),''), ''))), '')
            ))
            WHERE s.basis_of_record = 'PRESERVED_SPECIMEN'
            ON DUPLICATE KEY UPDATE
                dataset_key                   = VALUES(dataset_key),
                scientific_name               = VALUES(scientific_name),
                taxon_rank                    = VALUES(taxon_rank),
                kingdom                       = VALUES(kingdom),
                family                        = VALUES(family),
                gbif_decimal_latitude         = VALUES(gbif_decimal_latitude),
                gbif_decimal_longitude        = VALUES(gbif_decimal_longitude),
                gbif_coordinate_uncertainty_m = VALUES(gbif_coordinate_uncertainty_m),
                gbif_geodetic_datum           = VALUES(gbif_geodetic_datum),
                locality_group_id             = VALUES(locality_group_id),
                georef_status = IF(
                    georef_status IN ('validated', 'gbif_reviewed', 'has_suggestion', 'conflicted'),
                    georef_status,
                    VALUES(georef_status)
                ),
                synced_at  = NOW(),
                updated_at = NOW()
        ");

        $occCount = DB::table('occurrences')->count();
        $this->info("  Occurrences total: {$occCount}");

        $this->info('Step 3/3: Updating group counters...');

        // Update counters for groups that have occurrences
        DB::statement("
            UPDATE locality_groups lg
            JOIN (
                SELECT locality_group_id,
                    COUNT(*) AS total,
                    SUM(georef_status IN ('has_suggestion', 'conflicted')) AS pending,
                    SUM(georef_status = 'validated') AS validated,
                    SUM(georef_status = 'ungeoreferenced') AS ungeoreferenced
                FROM occurrences
                WHERE locality_group_id IS NOT NULL
                GROUP BY locality_group_id
            ) c ON c.locality_group_id = lg.id
            SET
                lg.occurrence_count       = c.total,
                lg.pending_count          = c.pending,
                lg.validated_count        = c.validated,
                lg.ungeoreferenced_count  = c.ungeoreferenced,
                lg.updated_at             = NOW()
        ");

        // Zero out groups whose occurrences all moved to other groups
        DB::statement("
            UPDATE locality_groups lg
            LEFT JOIN occurrences o ON o.locality_group_id = lg.id
            SET lg.occurrence_count      = 0,
                lg.pending_count         = 0,
                lg.validated_count       = 0,
                lg.ungeoreferenced_count = 0,
                lg.updated_at            = NOW()
            WHERE o.id IS NULL
              AND lg.occurrence_count > 0
        ");

        $this->info('  Counters updated.');
    }

    // -------------------------------------------------------------------------
    // Import multimedia extension (multimedia.txt) → occurrences.media
    // -------------------------------------------------------------------------

    private function importMultimedia(string $path): void
    {
        $this->info('Importing multimedia...');

        $fh = fopen($path, 'r');
        if (!$fh) {
            $this->warn("Cannot open multimedia file: {$path}");
            return;
        }

        // Read header to find column indices
        $header = fgetcsv($fh, 0, "\t");
        if (!$header) { fclose($fh); return; }
        $header = array_map('trim', $header);

        $idxGbifId    = array_search('gbifID', $header);
        $idxType      = array_search('type', $header);
        $idxFormat    = array_search('format', $header);
        $idxIdentifier = array_search('identifier', $header);
        $idxTitle     = array_search('title', $header);
        $idxLicense   = array_search('license', $header);

        if ($idxGbifId === false || $idxIdentifier === false) {
            $this->warn('multimedia.txt missing gbifID or identifier columns');
            fclose($fh);
            return;
        }

        // Stream multimedia.txt line by line — file can be 20GB+, never load into memory
        $chunk       = [];
        $processed   = 0;
        $currentId   = null;
        $currentItems = [];

        $flushCurrent = function () use (&$chunk, &$currentId, &$currentItems, &$processed) {
            if ($currentId && $currentItems) {
                $chunk[$currentId] = $currentItems;
                $processed++;
                if (count($chunk) >= 500) {
                    $this->updateMediaChunk($chunk);
                    $chunk = [];
                }
            }
            $currentId    = null;
            $currentItems = [];
        };

        while (($row = fgetcsv($fh, 0, "\t")) !== false) {
            $gbifId = trim($row[$idxGbifId] ?? '');
            $type   = trim($row[$idxType] ?? '');
            if (!$gbifId || ($type && !str_contains(strtolower($type), 'image'))) {
                continue;
            }
            $identifier = trim($row[$idxIdentifier] ?? '');
            if (!$identifier) continue;

            if ($gbifId !== $currentId) {
                $flushCurrent();
                $currentId = $gbifId;
            }
            $currentItems[] = array_filter([
                'type'       => $type ?: 'StillImage',
                'format'     => trim($row[$idxFormat] ?? ''),
                'identifier' => $identifier,
                'title'      => trim($row[$idxTitle] ?? ''),
                'license'    => trim($row[$idxLicense] ?? ''),
            ]);

            if ($processed % 10000 === 0 && $processed > 0) {
                $this->line("  {$processed} processed...");
            }
        }
        $flushCurrent();
        if ($chunk) $this->updateMediaChunk($chunk);
        fclose($fh);

        $this->info("  Multimedia import done. {$processed} occurrences updated.");
    }

    private function updateMediaChunk(array $chunk): void
    {
        $cases = '';
        $keys  = [];
        foreach ($chunk as $gbifId => $items) {
            $json   = addslashes(json_encode(array_values($items)));
            $cases .= "WHEN gbif_occurrence_key = '{$gbifId}' THEN '{$json}'\n";
            $keys[] = $gbifId;
        }
        $inList = implode(',', array_map(fn($k) => "'{$k}'", $keys));
        DB::statement("UPDATE occurrences SET media = CASE {$cases} END WHERE gbif_occurrence_key IN ({$inList})");
    }

    // -------------------------------------------------------------------------
    // DwC term → staging column name
    // -------------------------------------------------------------------------

    private function termMap(): array
    {
        return [
            'gbifID'                        => 'gbif_id',
            'datasetKey'                    => 'dataset_key',
            'publishingOrgKey'              => 'publishing_org_key',
            'basisOfRecord'                 => 'basis_of_record',
            'institutionCode'               => 'institution_code',
            'collectionCode'                => 'collection_code',
            'catalogNumber'                 => 'catalog_number',
            'recordedBy'                    => 'recorded_by',
            'eventDate'                     => 'event_date',
            'country'                       => 'country',
            'countryCode'                   => 'country_code',
            'stateProvince'                 => 'state_province',
            'county'                        => 'county',
            'municipality'                  => 'municipality',
            'verbatimLocality'              => 'verbatim_locality',
            'locality'                      => 'locality', // fallback when verbatimLocality is empty
            'island'                        => 'island',
            'islandGroup'                   => 'island_group',
            'waterBody'                     => 'water_body',
            'scientificName'                => 'scientific_name',
            'taxonRank'                     => 'taxon_rank',
            'kingdom'                       => 'kingdom',
            'family'                        => 'family',
            'hasCoordinate'                 => 'has_coordinate',
            'decimalLatitude'               => 'decimal_latitude',
            'decimalLongitude'              => 'decimal_longitude',
            'coordinateUncertaintyInMeters' => 'coordinate_uncertainty_m',
            'geodeticDatum'                 => 'geodetic_datum',
        ];
    }

    private function bytes(int $b): string
    {
        foreach (['B', 'KB', 'MB', 'GB'] as $u) {
            if ($b < 1024) {
                return round($b, 1) . " {$u}";
            }
            $b /= 1024;
        }
        return round($b, 1) . ' TB';
    }
}
