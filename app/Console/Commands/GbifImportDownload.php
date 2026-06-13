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
                            {--skip-cleanup : Keep gbif_staging populated after import}';

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

        // Step 1: poll + download
        if (!$this->option('skip-staging')) {
            if (!$zipPath) {
                $zipPath = $this->waitAndDownload($key);
                if (!$zipPath) {
                    return self::FAILURE;
                }
            }

            // Step 2: extract occurrence.txt and parse meta.xml
            [$csvPath, $colList] = $this->extractAndMapColumns($zipPath);
            if (!$csvPath) {
                return self::FAILURE;
            }

            // Step 3: LOAD DATA LOCAL INFILE → gbif_staging
            if (!$this->loadIntoStaging($csvPath, $colList)) {
                return self::FAILURE;
            }
        }

        // Step 4: staging → locality_groups + occurrences
        $this->processStaging();

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
        $zip->close();

        $this->info('Extracted: ' . $this->bytes(filesize($csvTarget)));

        // Check if the DWCA has a header line to skip
        $ignoreHeader = (int) ($core['ignoreHeaderLines'] ?? 0);

        return [$csvTarget, $colList, $ignoreHeader];
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
        $this->info('Step 1/3: Creating locality groups from staging...');

        // Replicates LocalityGroup::hashFromOccurrence() in SQL:
        // SHA1 of non-empty lowercased fields joined by '|'
        // NULLIF(LOWER(TRIM(...)), '') converts empty → NULL so CONCAT_WS skips them
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
                    NULLIF(LOWER(TRIM(COALESCE(verbatim_locality, ''))), '')
                )) AS group_hash,
                MIN(country_code),
                MIN(state_province),
                MIN(county),
                MIN(municipality),
                MIN(verbatim_locality),
                MIN(TRIM(CONCAT_WS(', ',
                    NULLIF(country_code, ''),
                    NULLIF(state_province, ''),
                    NULLIF(county, ''),
                    NULLIF(municipality, ''),
                    NULLIF(verbatim_locality, '')
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
                s.verbatim_locality,
                s.island,
                s.island_group,
                s.water_body,
                s.scientific_name,
                s.taxon_rank,
                s.kingdom,
                s.family,
                IF(s.has_coordinate = 1, s.decimal_latitude, NULL),
                IF(s.has_coordinate = 1, s.decimal_longitude, NULL),
                IF(s.has_coordinate = 1, s.coordinate_uncertainty_m, NULL),
                NULLIF(s.geodetic_datum, ''),
                lg.id,
                IF(s.has_coordinate = '1', 'gbif_georeferenced', 'ungeoreferenced'),
                NOW(), NOW(), NOW()
            FROM gbif_staging s
            JOIN locality_groups lg ON lg.group_hash = SHA1(CONCAT_WS('|',
                NULLIF(LOWER(TRIM(COALESCE(s.country_code, ''))), ''),
                NULLIF(LOWER(TRIM(COALESCE(s.state_province, ''))), ''),
                NULLIF(LOWER(TRIM(COALESCE(s.county, ''))), ''),
                NULLIF(LOWER(TRIM(COALESCE(s.municipality, ''))), ''),
                NULLIF(LOWER(TRIM(COALESCE(s.verbatim_locality, ''))), '')
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
                    georef_status IN ('validated', 'gbif_reviewed'),
                    georef_status,
                    VALUES(georef_status)
                ),
                synced_at  = NOW(),
                updated_at = NOW()
        ");

        $occCount = DB::table('occurrences')->count();
        $this->info("  Occurrences total: {$occCount}");

        $this->info('Step 3/3: Updating group counters...');

        DB::statement("
            UPDATE locality_groups lg
            JOIN (
                SELECT locality_group_id,
                    COUNT(*) AS total,
                    SUM(georef_status IN ('has_suggestion', 'conflicted')) AS pending,
                    SUM(georef_status = 'validated') AS validated
                FROM occurrences
                WHERE locality_group_id IS NOT NULL
                GROUP BY locality_group_id
            ) c ON c.locality_group_id = lg.id
            SET
                lg.occurrence_count = c.total,
                lg.pending_count    = c.pending,
                lg.validated_count  = c.validated,
                lg.updated_at       = NOW()
        ");

        $this->info('  Counters updated.');
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
            'locality'                      => '@dummy', // interpreted, we prefer verbatimLocality
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
