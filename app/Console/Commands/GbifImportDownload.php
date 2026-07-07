<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
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
                            {--multimedia-only= : Path to multimedia.txt — skip everything else and only import multimedia}
                            {--prune-deleted : Soft-delete occurrences absent from this staging batch — only safe for full, unfiltered world imports, NOT for a --country-filtered download}';

    protected $description = 'Poll, download, and import a GBIF DWCA download into occurrences';

    private string $storageDir;

    // Writes into the same cache key GbifRefreshHeartbeat reads, so the periodic email
    // reports which actual sub-phase is running (LOAD DATA vs cleanup vs locality_groups
    // vs occurrences upsert) and how far along it is — previously the heartbeat only ever
    // showed the top-level "Step 2/6: Importing", with no visibility into hours of work
    // happening underneath it. Harmless no-op cache entry when run standalone outside
    // gbif:monthly-refresh (no 'running' flag set, so the heartbeat's own check ignores it).
    private function markProgress(string $step, array $counters = []): void
    {
        $status = Cache::get(\App\Console\Commands\GbifMonthlyRefresh::STATUS_KEY, []);
        $status['substep']    = $step;
        $status['counters']   = $counters;
        $status['updated_at'] = now();
        Cache::forever(\App\Console\Commands\GbifMonthlyRefresh::STATUS_KEY, $status);
    }

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
            [$csvPath, $colList, $ignoreHeader, $multimediaPath, $fieldsEnclosedBy] = $this->extractAndMapColumns($zipPath);
            if (!$csvPath) {
                return self::FAILURE;
            }

            // Step 3: LOAD DATA LOCAL INFILE → gbif_staging
            if (!$this->loadIntoStaging($csvPath, $colList, $fieldsEnclosedBy)) {
                return self::FAILURE;
            }
        } else {
            $multimediaPath = null;
        }

        // Step 4: staging → locality_groups + occurrences
        $this->processStaging($this->option('prune-deleted'));

        // Step 4b: import multimedia if extracted
        if (!empty($multimediaPath) && file_exists($multimediaPath)) {
            $this->importMultimedia($multimediaPath);
        }

        // Step 5: cleanup — once the data is safely in occurrences/locality_groups, the
        // raw download artifacts (zip, extracted occurrence.txt, multimedia.txt) are no
        // longer needed and are large (100GB+ each). Left alone, they accumulate every
        // month and silently eat disk space (this is what filled the disk to 85% before
        // anyone noticed). Only deleted on a fully successful run, so a failed run can
        // still retry without re-downloading/re-extracting.
        if (!$this->option('skip-cleanup')) {
            $this->info('Truncating gbif_staging...');
            $this->markProgress('Cleaning up: truncating gbif_staging and removing downloaded files');
            DB::statement('TRUNCATE TABLE gbif_staging');
            Cache::forget('gbif:staging-loaded-from');

            foreach (array_filter([$zipPath, $csvPath ?? null, $multimediaPath ?? null]) as $file) {
                if (is_string($file) && file_exists($file)) {
                    unlink($file);
                    $this->info("Removed {$file}");
                }
            }
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
        $this->markProgress('Waiting for GBIF to prepare the download');

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
        $this->markProgress('Downloading ZIP from GBIF');

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

        // Stream-extract occurrence file (can be large) — skip if an extraction from
        // this exact ZIP entry already exists on disk (matched by size, so a stale or
        // partial leftover file from an unrelated run doesn't get reused by mistake).
        $expectedSize = $zip->statName($location)['size'] ?? null;
        if (file_exists($csvTarget) && $expectedSize !== null && filesize($csvTarget) === $expectedSize) {
            $this->info("{$csvTarget} already extracted (" . $this->bytes(filesize($csvTarget)) . '), skipping');
        } else {
            $this->info("Extracting {$location} → {$csvTarget}");
            $this->markProgress("Extracting {$location} from ZIP");
            $src  = $zip->getStream($location);
            $dest = fopen($csvTarget, 'wb');
            stream_copy_to_stream($src, $dest);
            fclose($src);
            fclose($dest);

            $this->info('Extracted: ' . $this->bytes(filesize($csvTarget)));
        }

        // Check if the DWCA has a header line to skip
        $ignoreHeader = (int) ($core['ignoreHeaderLines'] ?? 0);

        // GBIF's occurrence.txt is a plain TSV with no field enclosure
        // (fieldsEnclosedBy="" in meta.xml) — hardcoding ENCLOSED BY '"' let a single
        // literal quote character anywhere in 283M rows of free-text fields (remarks,
        // recordedBy, etc.) desync LOAD DATA's field parser for the rest of the file,
        // silently collapsing everything after it into far fewer rows with no error.
        $fieldsEnclosedBy = isset($core['fieldsEnclosedBy']) ? (string) $core['fieldsEnclosedBy'] : '"';

        // Also extract multimedia extension if present
        $multimediaPath = null;
        foreach ($xml->extension ?? [] as $ext) {
            $rowType = (string) ($ext['rowType'] ?? '');
            if (str_contains($rowType, 'Multimedia') || str_contains($rowType, 'multimedia')) {
                $mLoc   = (string) ($ext->files->location ?? '');
                if ($mLoc) {
                    $mTarget = "{$this->storageDir}/" . basename($mLoc);
                    $mExpectedSize = $zip->statName($mLoc)['size'] ?? null;
                    if (file_exists($mTarget) && $mExpectedSize !== null && filesize($mTarget) === $mExpectedSize) {
                        $this->info("{$mTarget} already extracted (" . $this->bytes(filesize($mTarget)) . '), skipping');
                        $multimediaPath = $mTarget;
                    } else {
                        $mSrc = $zip->getStream($mLoc);
                        if ($mSrc) {
                            $mDest = fopen($mTarget, 'wb');
                            stream_copy_to_stream($mSrc, $mDest);
                            fclose($mSrc);
                            fclose($mDest);
                            $multimediaPath = $mTarget;
                            $this->info("Extracted multimedia: " . $this->bytes(filesize($mTarget)));
                        }
                    }
                }
                break;
            }
        }

        $zip->close();

        return [$csvTarget, $colList, $ignoreHeader, $multimediaPath, $fieldsEnclosedBy];
    }

    // -------------------------------------------------------------------------
    // LOAD DATA LOCAL INFILE
    // -------------------------------------------------------------------------

    private function loadIntoStaging(string $csvPath, array $colList, string $fieldsEnclosedBy = ''): bool
    {
        // Skip re-running LOAD DATA if gbif_staging already holds this exact file (by
        // path+size+mtime — cheap to check, unlike re-verifying via COUNT(*) on a
        // 200M+ row table). This is the difference between a retry after a downstream
        // failure taking minutes instead of redoing an hours-long reload for no reason —
        // observed costing hours on production when a --key retry blindly re-truncated
        // and reloaded staging that was already complete and correct.
        $fingerprint = [
            'path'  => realpath($csvPath) ?: $csvPath,
            'size'  => filesize($csvPath),
            'mtime' => filemtime($csvPath),
        ];
        $previous = Cache::get('gbif:staging-loaded-from');
        if ($previous && $previous['path'] === $fingerprint['path']
            && $previous['size'] === $fingerprint['size']
            && $previous['mtime'] === $fingerprint['mtime']) {
            $this->info("gbif_staging already loaded from this exact file ({$previous['rows']} rows), skipping LOAD DATA");
            return true;
        }

        $this->info('Loading into gbif_staging...');
        $this->markProgress('LOAD DATA INFILE → gbif_staging (single long-running statement, no batch progress)');

        DB::statement('TRUNCATE TABLE gbif_staging');
        Cache::forget('gbif:staging-loaded-from');

        $colString  = implode(', ', $colList);
        $escapedPath = str_replace('\\', '/', $csvPath);
        // Only emit ENCLOSED BY when the archive actually declares one — GBIF's
        // occurrence.txt normally has fieldsEnclosedBy="" (no quoting at all), and
        // forcing '"' as an enclosure character lets a single stray quote in any
        // free-text field desync the parser for the rest of the file.
        $enclosedByClause = $fieldsEnclosedBy !== ''
            ? "OPTIONALLY ENCLOSED BY '" . addslashes($fieldsEnclosedBy) . "'"
            : '';

        try {
            DB::statement("
                LOAD DATA LOCAL INFILE '{$escapedPath}'
                INTO TABLE gbif_staging
                CHARACTER SET utf8mb4
                FIELDS TERMINATED BY '\\t' {$enclosedByClause}
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
        Cache::forever('gbif:staging-loaded-from', $fingerprint + ['rows' => $count]);
        return true;
    }

    // Each of these used to be one UPDATE over all 283M staging rows in a single
    // transaction — observed holding 100M+ rows locked and 300MB+ of pure
    // lock-structure memory after 36 minutes, on track to OOM before finishing (same
    // failure class as the unbatched locality_groups INSERT below). Batching by
    // gbif_id range keeps each transaction's lock footprint bounded; safe to redo a
    // batch since every predicate here is idempotent (re-nulling an already-null or
    // already-valid value is a no-op).
    private function cleanupStagingBatched(): void
    {
        $bounds = DB::table('gbif_staging')->selectRaw('MIN(gbif_id) as min_id, MAX(gbif_id) as max_id')->first();
        if (!$bounds || $bounds->min_id === null) {
            return;
        }

        $batchSize    = 500000;
        $totalBatches = (int) ceil((($bounds->max_id - $bounds->min_id) + 1) / $batchSize);
        $batchNum     = 0;
        $totals = [
            'country_code_nulled' => 0, 'verbatim_locality_nulled' => 0, 'locality_nulled' => 0,
            'municipality_nulled' => 0, 'county_nulled' => 0, 'state_province_nulled' => 0,
        ];

        for ($start = $bounds->min_id; $start <= $bounds->max_id; $start += $batchSize) {
            $end = $start + $batchSize - 1;
            $batchNum++;

            $totals['country_code_nulled']      += DB::affectingStatement("UPDATE gbif_staging SET country_code = NULL WHERE gbif_id BETWEEN ? AND ? AND country_code NOT REGEXP '^[A-Z]{2}$'", [$start, $end]);
            $totals['verbatim_locality_nulled'] += DB::affectingStatement("UPDATE gbif_staging SET verbatim_locality = NULL WHERE gbif_id BETWEEN ? AND ? AND verbatim_locality REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}T'", [$start, $end]);
            $totals['locality_nulled']          += DB::affectingStatement("UPDATE gbif_staging SET locality          = NULL WHERE gbif_id BETWEEN ? AND ? AND locality          REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}T'", [$start, $end]);
            $totals['municipality_nulled']      += DB::affectingStatement("UPDATE gbif_staging SET municipality      = NULL WHERE gbif_id BETWEEN ? AND ? AND municipality      REGEXP '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$'", [$start, $end]);
            $totals['county_nulled']            += DB::affectingStatement("UPDATE gbif_staging SET county            = NULL WHERE gbif_id BETWEEN ? AND ? AND county            REGEXP '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$'", [$start, $end]);
            $totals['state_province_nulled']    += DB::affectingStatement("UPDATE gbif_staging SET state_province    = NULL WHERE gbif_id BETWEEN ? AND ? AND state_province    REGEXP '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$'", [$start, $end]);

            if ($batchNum % 10 === 0 || $batchNum === $totalBatches) {
                $this->line("  Cleanup batch {$batchNum}/{$totalBatches}");
                $this->markProgress("Cleaning staged data: batch {$batchNum}/{$totalBatches}", $totals);
            }
        }
    }

    // -------------------------------------------------------------------------
    // staging → locality_groups + occurrences
    // -------------------------------------------------------------------------

    private function processStaging(bool $pruneDeleted = false): void
    {
        $this->info('Step 0/3: Cleaning staged data (in batches)...');
        $this->cleanupStagingBatched();

        $this->info('Step 1/3: Creating locality groups from staging (in batches)...');

        // Batched by gbif_id range, same reasoning as the occurrences upsert below: the
        // single-statement version of this ran as ONE transaction over all 283M staging
        // rows, observed locking 174M+ rows and 500MB+ of pure lock-structure memory for
        // 4+ hours straight — a major contributor to the memory pressure that kept
        // building throughout this import. INSERT IGNORE is safe to run per-chunk since
        // group_hash already exists for a locality is simply skipped on later chunks;
        // the only difference from one global GROUP BY is that MIN(country_code) etc. is
        // taken from whichever chunk hashes to that group first, which is the same value
        // in practice since every occurrence sharing a group_hash also shares those fields.
        // Unfiltered on purpose: gbif_id is the primary key, so this is an instant lookup
        // of the first/last row. Adding "WHERE basis_of_record IN (...)" here (there's no
        // index on that column) forced a full 283M-row scan just to pick batch
        // boundaries — observed taking 90+ minutes on production. The filter itself still
        // applies correctly inside each batch's own INSERT below; an unfiltered ID range
        // is fine for chunking purposes, it just means some chunks may include gbif_ids
        // that don't match and contribute nothing.
        $groupBounds = DB::selectOne("SELECT MIN(gbif_id) AS min_id, MAX(gbif_id) AS max_id FROM gbif_staging");

        if ($groupBounds && $groupBounds->min_id !== null) {
            $groupBatchSize = 500000;
            $groupMinId     = (int) $groupBounds->min_id;
            $groupMaxId     = (int) $groupBounds->max_id;
            $groupTotalBatches = (int) ceil((($groupMaxId - $groupMinId) + 1) / $groupBatchSize);
            $groupBatchNum  = 0;
            $groupsCreated  = 0;

            for ($from = $groupMinId; $from <= $groupMaxId; $from += $groupBatchSize) {
                $to = min($from + $groupBatchSize - 1, $groupMaxId);
                $groupBatchNum++;

                // Replicates LocalityGroup::hashFromOccurrence() in SQL:
                // SHA1 of non-empty lowercased fields joined by '|'
                // NULLIF(LOWER(TRIM(...)), '') converts empty → NULL so CONCAT_WS skips them
                // COALESCE(verbatim_locality, locality): use verbatimLocality if present,
                // else fall back to locality (DwC interpreted field). Both map to
                // verbatim_locality column in locality_groups for grouping and display.
                $groupsCreated += DB::affectingStatement("
                    INSERT IGNORE INTO locality_groups
                        (group_hash, country_code, continent, state_province, county, municipality,
                         verbatim_locality, location_remarks, higher_geography, locality_string, created_at, updated_at)
                    SELECT
                        SHA1(CONCAT_WS('|',
                            NULLIF(LOWER(TRIM(COALESCE(continent, ''))), ''),
                            NULLIF(LOWER(TRIM(COALESCE(country_code, ''))), ''),
                            NULLIF(LOWER(TRIM(COALESCE(state_province, ''))), ''),
                            NULLIF(LOWER(TRIM(COALESCE(county, ''))), ''),
                            NULLIF(LOWER(TRIM(COALESCE(municipality, ''))), ''),
                            NULLIF(LOWER(TRIM(COALESCE(NULLIF(TRIM(verbatim_locality),''), NULLIF(TRIM(locality),''), ''))), ''),
                            NULLIF(LOWER(TRIM(COALESCE(water_body, ''))), ''),
                            NULLIF(LOWER(TRIM(COALESCE(island_group, ''))), ''),
                            NULLIF(LOWER(TRIM(COALESCE(island, ''))), ''),
                            NULLIF(LOWER(TRIM(COALESCE(higher_geography, ''))), ''),
                            NULLIF(LOWER(TRIM(COALESCE(location_remarks, ''))), '')
                        )) AS group_hash,
                        MIN(country_code),
                        MIN(continent),
                        MIN(state_province),
                        MIN(county),
                        MIN(municipality),
                        MIN(COALESCE(NULLIF(TRIM(verbatim_locality),''), NULLIF(TRIM(locality),''))) ,
                        MIN(location_remarks),
                        MIN(higher_geography),
                        MIN(TRIM(CONCAT_WS(', ',
                            NULLIF(continent, ''),
                            NULLIF(country_code, ''),
                            NULLIF(state_province, ''),
                            NULLIF(county, ''),
                            NULLIF(municipality, ''),
                            NULLIF(COALESCE(NULLIF(TRIM(verbatim_locality),''), NULLIF(TRIM(locality),'')), ''),
                            NULLIF(water_body, ''),
                            NULLIF(island_group, ''),
                            NULLIF(island, ''),
                            NULLIF(location_remarks, '')
                        ))),
                        NOW(),
                        NOW()
                    FROM gbif_staging
                    WHERE basis_of_record IN ('PRESERVED_SPECIMEN', 'FOSSIL_SPECIMEN')
                        AND gbif_id BETWEEN {$from} AND {$to}
                    GROUP BY group_hash
                ");

                if ($groupBatchNum % 20 === 0 || $groupBatchNum === $groupTotalBatches) {
                    $this->line("  Batch {$groupBatchNum}/{$groupTotalBatches} (gbif_id {$from}-{$to})");
                    $this->markProgress("Creating locality groups: batch {$groupBatchNum}/{$groupTotalBatches}", ['new_locality_groups' => $groupsCreated]);
                }
            }
        }

        $groupCount = DB::table('locality_groups')->count();
        $this->info("  Locality groups total: {$groupCount}");

        $this->info('Step 2/3: Upserting occurrences in batches (keeps the site responsive)...');

        // Batch by gbif_id range instead of one giant statement — a single multi-million-row
        // INSERT ... ON DUPLICATE KEY UPDATE holds row locks on `occurrences` for a very long
        // time, which would stall user submissions for the duration. Chunking lets other
        // queries interleave between batches.
        //
        // Unfiltered on purpose (see the identical comment above for Step 1) — gbif_id is
        // the primary key, so this is instant; filtering by the unindexed basis_of_record
        // forces a full table scan just to pick batch boundaries. The filter still applies
        // inside each batch's own INSERT below.
        $bounds = DB::selectOne("SELECT MIN(gbif_id) AS min_id, MAX(gbif_id) AS max_id FROM gbif_staging");

        if ($bounds && $bounds->min_id !== null) {
            $batchSize = 200000;
            $minId     = (int) $bounds->min_id;
            $maxId     = (int) $bounds->max_id;
            $totalBatches = (int) ceil((($maxId - $minId) + 1) / $batchSize);
            $batchNum  = 0;
            $rowsTouched = 0; // MySQL counts an INSERT as 1 and an ON DUPLICATE KEY UPDATE as 2 affected rows

            for ($from = $minId; $from <= $maxId; $from += $batchSize) {
                $to = min($from + $batchSize - 1, $maxId);
                $batchNum++;

                $rowsTouched += DB::affectingStatement("
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
                        NULLIF(LOWER(TRIM(COALESCE(s.continent, ''))), ''),
                        NULLIF(LOWER(TRIM(COALESCE(s.country_code, ''))), ''),
                        NULLIF(LOWER(TRIM(COALESCE(s.state_province, ''))), ''),
                        NULLIF(LOWER(TRIM(COALESCE(s.county, ''))), ''),
                        NULLIF(LOWER(TRIM(COALESCE(s.municipality, ''))), ''),
                        NULLIF(LOWER(TRIM(COALESCE(NULLIF(TRIM(s.verbatim_locality),''), NULLIF(TRIM(s.locality),''), ''))), ''),
                        NULLIF(LOWER(TRIM(COALESCE(s.water_body, ''))), ''),
                        NULLIF(LOWER(TRIM(COALESCE(s.island_group, ''))), ''),
                        NULLIF(LOWER(TRIM(COALESCE(s.island, ''))), ''),
                        NULLIF(LOWER(TRIM(COALESCE(s.higher_geography, ''))), ''),
                        NULLIF(LOWER(TRIM(COALESCE(s.location_remarks, ''))), '')
                    ))
                    WHERE s.basis_of_record IN ('PRESERVED_SPECIMEN', 'FOSSIL_SPECIMEN')
                        AND s.gbif_id BETWEEN {$from} AND {$to}
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
                        deleted_at = NULL,
                        synced_at  = NOW(),
                        updated_at = NOW()
                ");

                $this->line("  Batch {$batchNum}/{$totalBatches} done (gbif_id {$from}–{$to})");
                if ($batchNum % 10 === 0 || $batchNum === $totalBatches) {
                    $this->markProgress("Upserting occurrences: batch {$batchNum}/{$totalBatches}", ['occurrence_rows_touched' => $rowsTouched]);
                }

                // Brief pause between batches so queued user requests get a chance to run.
                usleep(200000);
            }
        }

        if (!$pruneDeleted) {
            $this->line('Step 2b/3: Skipped (pass --prune-deleted on a full, unfiltered import to enable).');
        } else {
            $this->info('Step 2b/3: Soft-deleting occurrences no longer present in GBIF...');

            // Anything currently in `occurrences` whose key isn't in the fresh full staging import
            // is gone from GBIF (retracted, dataset removed, etc). Soft-delete rather than hard-delete
            // so validated/suggested georeferences aren't lost if the record reappears later.
            // Only safe when staging holds a full, unfiltered world import — a --country-filtered
            // staging batch would otherwise look like everything else in the world was deleted.
            $occBounds = DB::selectOne('SELECT MIN(id) AS min_id, MAX(id) AS max_id FROM occurrences');

            if ($occBounds && $occBounds->min_id !== null) {
                $batchSize    = 200000;
                $minId        = (int) $occBounds->min_id;
                $maxId        = (int) $occBounds->max_id;
                $totalBatches = (int) ceil((($maxId - $minId) + 1) / $batchSize);
                $batchNum     = 0;
                $softDeleted  = 0;

                for ($from = $minId; $from <= $maxId; $from += $batchSize) {
                    $to = min($from + $batchSize - 1, $maxId);
                    $batchNum++;

                    $softDeleted += DB::affectingStatement("
                        UPDATE occurrences o
                        LEFT JOIN gbif_staging s ON s.gbif_id = CAST(o.gbif_occurrence_key AS UNSIGNED)
                        SET o.deleted_at = NOW()
                        WHERE s.gbif_id IS NULL
                          AND o.deleted_at IS NULL
                          AND o.id BETWEEN {$from} AND {$to}
                    ");

                    $this->line("  Batch {$batchNum}/{$totalBatches} done (occurrence id {$from}–{$to})");
                    if ($batchNum % 10 === 0 || $batchNum === $totalBatches) {
                        $this->markProgress("Soft-deleting removed occurrences: batch {$batchNum}/{$totalBatches}", ['occurrences_soft_deleted' => $softDeleted]);
                    }
                    usleep(200000);
                }
            }
        }

        $occCount = DB::table('occurrences')->count();
        $this->info("  Occurrences total: {$occCount}");

        $this->info('Step 3/3: Updating group counters in batches...');

        // Batch by locality_groups.id range, same reasoning as the occurrences upsert above —
        // locality_groups is read on every /georef page load, so one giant UPDATE touching
        // millions of rows at once would hold locks on that hot table for a long time.
        $lgBounds = DB::selectOne('SELECT MIN(id) AS min_id, MAX(id) AS max_id FROM locality_groups');

        if ($lgBounds && $lgBounds->min_id !== null) {
            $batchSize    = 200000;
            $minId        = (int) $lgBounds->min_id;
            $maxId        = (int) $lgBounds->max_id;
            $totalBatches = (int) ceil((($maxId - $minId) + 1) / $batchSize);
            $batchNum     = 0;

            for ($from = $minId; $from <= $maxId; $from += $batchSize) {
                $to = min($from + $batchSize - 1, $maxId);
                $batchNum++;

                // Update counters for groups in this id range that have occurrences
                // (deleted_at IS NULL — soft-deleted occurrences no longer count)
                DB::statement("
                    UPDATE locality_groups lg
                    JOIN (
                        SELECT locality_group_id,
                            COUNT(*) AS total,
                            SUM(georef_status IN ('has_suggestion', 'conflicted')) AS pending,
                            SUM(georef_status = 'validated') AS validated,
                            SUM(georef_status = 'ungeoreferenced') AS ungeoreferenced
                        FROM occurrences
                        WHERE locality_group_id BETWEEN {$from} AND {$to}
                          AND deleted_at IS NULL
                        GROUP BY locality_group_id
                    ) c ON c.locality_group_id = lg.id
                    SET
                        lg.occurrence_count       = c.total,
                        lg.pending_count          = c.pending,
                        lg.validated_count        = c.validated,
                        lg.ungeoreferenced_count  = c.ungeoreferenced,
                        lg.updated_at             = NOW()
                    WHERE lg.id BETWEEN {$from} AND {$to}
                ");

                // Zero out groups in this range with no remaining (non-deleted) occurrences
                DB::statement("
                    UPDATE locality_groups lg
                    LEFT JOIN occurrences o ON o.locality_group_id = lg.id AND o.deleted_at IS NULL
                    SET lg.occurrence_count      = 0,
                        lg.pending_count         = 0,
                        lg.validated_count       = 0,
                        lg.ungeoreferenced_count = 0,
                        lg.updated_at            = NOW()
                    WHERE o.id IS NULL
                      AND lg.occurrence_count > 0
                      AND lg.id BETWEEN {$from} AND {$to}
                ");

                if ($pruneDeleted) {
                    // Soft-delete groups left with zero occurrences (fully orphaned), and
                    // revive any previously soft-deleted group that has occurrences again.
                    DB::statement("
                        UPDATE locality_groups
                        SET deleted_at = NOW()
                        WHERE occurrence_count = 0
                          AND deleted_at IS NULL
                          AND id BETWEEN {$from} AND {$to}
                    ");

                    DB::statement("
                        UPDATE locality_groups
                        SET deleted_at = NULL
                        WHERE occurrence_count > 0
                          AND deleted_at IS NOT NULL
                          AND id BETWEEN {$from} AND {$to}
                    ");
                }

                $this->line("  Batch {$batchNum}/{$totalBatches} done (locality_group id {$from}–{$to})");
                usleep(200000);
            }
        }

        $this->info('  Counters updated.');
    }

    // -------------------------------------------------------------------------
    // Import multimedia extension (multimedia.txt) → occurrences.media
    // -------------------------------------------------------------------------

    private function importMultimedia(string $path): void
    {
        $this->info('Importing multimedia...');
        $this->markProgress('Importing multimedia');

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
            'continent'                     => 'continent',
            'higherGeography'               => 'higher_geography',
            'locationRemarks'               => 'location_remarks',
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
