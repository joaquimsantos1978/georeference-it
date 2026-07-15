<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LocalityGroup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'group_hash',
        'locality_string',
        'verbatim_locality',
        'normalized_locality',
        'country_code',
        'continent',
        'state_province',
        'county',
        'municipality',
        'island',
        'island_group',
        'water_body',
        'higher_geography',
        'location_remarks',
        'occurrence_count',
        'pending_count',
        'validated_count',
        'ungeoreferenced_count',
        'consistency_status',
    ];

    public function occurrences(): HasMany
    {
        return $this->hasMany(Occurrence::class);
    }

    public function suggestions(): HasMany
    {
        return $this->hasMany(GeorefSuggestion::class);
    }

    public static function normalizeLocality(string $text): string
    {
        $s = mb_strtolower($text);
        $s = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }

    public static function hashFromOccurrence(array $fields): string
    {
        // Use verbatimLocality if present, fall back to locality (DwC interpreted field).
        // Must match the COALESCE(verbatim_locality, locality) logic in GbifImportDownload SQL.
        $verbatimLocality = (trim($fields['verbatim_locality'] ?? '') !== '')
            ? $fields['verbatim_locality']
            : ($fields['locality'] ?? '');

        $parts = array_filter([
            strtolower(trim($fields['continent'] ?? '')),
            strtolower(trim($fields['country_code'] ?? '')),
            strtolower(trim($fields['state_province'] ?? '')),
            strtolower(trim($fields['county'] ?? '')),
            strtolower(trim($fields['municipality'] ?? '')),
            strtolower(trim($verbatimLocality)),
            strtolower(trim($fields['water_body'] ?? '')),
            strtolower(trim($fields['island_group'] ?? '')),
            strtolower(trim($fields['island'] ?? '')),
            strtolower(trim($fields['higher_geography'] ?? '')),
            strtolower(trim($fields['location_remarks'] ?? '')),
        ]);

        return sha1(implode('|', $parts));
    }
    public function comments(): HasMany
    {
        return $this->hasMany(LocalityGroupComment::class);
    }

    // Country-filter dropdown source for Explore/Impact/Activity. Two steps, both
    // deliberately shaped around what stays fast on a table this size:
    //
    // 1. A plain filter-free DISTINCT on country_code uses a loose index scan
    //    (EXPLAIN: "Using index for group-by", ~46K rows) regardless of table size —
    //    adding *any* other WHERE condition here (occurrence_count > 0, deleted_at IS
    //    NULL) breaks that optimization and forces a ~48M-row full scan instead, since
    //    no index covers those columns together with country_code. Format validity
    //    (exactly two uppercase letters) is filtered in PHP against this small
    //    distinct-values result instead of as a SQL REGEXP for the same reason —
    //    pre-validation-era data left plenty of garbage still sitting in the column
    //    (tabs, digits, whole province names in Chinese).
    // 2. Even after that, a code whose *only* groups are all soft-deleted (e.g. "AA", a
    //    reserved-but-unassigned ISO code) would still show up and always return zero
    //    results when picked, since Explore's actual listing query is soft-delete-aware
    //    and this DISTINCT isn't. Re-checking every remaining candidate with `WHERE
    //    country_code = ? AND deleted_at IS NULL LIMIT 1` is fast per code (index_merge,
    //    ~30ms even for a huge country like the US — LIMIT 1 stops at the first match
    //    regardless of how many rows that country actually has) but adds up over ~300
    //    candidates, so the whole result is cached rather than re-checked every request.
    public static function activeCountryCodes(): \Illuminate\Support\Collection
    {
        return \Illuminate\Support\Facades\Cache::remember('locality_groups:active_country_codes', 900, function () {
            $candidates = \Illuminate\Support\Facades\DB::table('locality_groups')
                ->whereNotNull('country_code')
                ->where('country_code', '!=', '')
                ->distinct()
                ->pluck('country_code')
                ->filter(fn($code) => preg_match('/^[A-Z]{2}$/', $code));

            return $candidates
                ->filter(fn($code) => \Illuminate\Support\Facades\DB::table('locality_groups')
                    ->where('country_code', $code)
                    ->whereNull('deleted_at')
                    ->exists())
                ->sort()
                ->values();
        });
    }

    public function recalculateCounters(): void
    {
        \Illuminate\Support\Facades\DB::statement("
            UPDATE locality_groups lg
            JOIN (
                SELECT
                    COUNT(*)                             AS total,
                    COALESCE(SUM(georef_status = 'validated'), 0)     AS validated,
                    COALESCE(SUM(georef_status = 'ungeoreferenced'), 0) AS ungeoreferenced
                FROM occurrences
                WHERE locality_group_id = ?
            ) occ ON lg.id = ?
            JOIN (
                SELECT COUNT(*) AS pending
                FROM georef_suggestions
                WHERE locality_group_id = ? AND status = 'pending'
            ) sug ON 1=1
            SET
                lg.occurrence_count      = occ.total,
                lg.pending_count         = sug.pending,
                lg.validated_count       = occ.validated,
                lg.ungeoreferenced_count = occ.ungeoreferenced,
                lg.updated_at            = NOW()
        ", [$this->id, $this->id, $this->id]);

        $this->refresh();
    }
}