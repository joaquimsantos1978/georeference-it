<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeLocalityGroups extends Command
{
    protected $signature = 'georef:normalize-localities {--chunk=2000}';
    protected $description = 'Populate normalized_locality on all locality_groups for similar-group detection';

    public function handle(): void
    {
        $chunk = (int) $this->option('chunk');
        $total = DB::table('locality_groups')->count();
        $bar   = $this->output->createProgressBar($total);
        $bar->start();

        DB::table('locality_groups')
            ->orderBy('id')
            ->chunk($chunk, function ($rows) use ($bar) {
                $updates = [];
                foreach ($rows as $row) {
                    $updates[] = [
                        'id'                  => $row->id,
                        'normalized_locality' => self::normalize($row->verbatim_locality ?? ''),
                    ];
                }
                // Bulk update via CASE WHEN for efficiency
                $ids   = array_column($updates, 'id');
                $cases = implode(' ', array_map(
                    fn($u) => "WHEN {$u['id']} THEN " . DB::getPdo()->quote($u['normalized_locality']),
                    $updates
                ));
                $inList = implode(',', $ids);
                DB::statement("UPDATE locality_groups SET normalized_locality = CASE id {$cases} END WHERE id IN ({$inList})");
                $bar->advance(count($updates));
            });

        $bar->finish();
        $this->newLine();
        $this->info('Done.');
    }

    public static function normalize(string $text): string
    {
        // Lowercase
        $s = mb_strtolower($text);
        // Remove punctuation except word chars and spaces
        $s = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $s);
        // Collapse whitespace
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }
}
