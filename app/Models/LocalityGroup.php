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

    // Common Latin diacritics folded to their base letter before comparison, so
    // "Lisboa" and "Lisbôa" (a real pair seen in the wild — same expedition, same
    // locality, transcribed differently by two data providers) normalize identically
    // and show up as "similar groups" to each other. Deliberately NOT used by
    // hashFromOccurrence() below — that hash is the group's identity, and folding
    // accents there would merge/reshuffle existing locality_groups; this one only
    // feeds normalized_locality, a secondary field used for search/similarity, so
    // widening it is low-risk. iconv('...//TRANSLIT') was tried first and rejected:
    // its output is locale/libc-dependent (e.g. "ô" became "^o", not "o", here).
    private const ACCENT_MAP = [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'ā' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'ē' => 'e', 'ė' => 'e', 'ę' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ī' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ō' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ū' => 'u',
        'ý' => 'y', 'ÿ' => 'y',
        'ñ' => 'n', 'ń' => 'n',
        'ç' => 'c', 'ć' => 'c', 'č' => 'c',
        'š' => 's', 'ś' => 's',
        'ž' => 'z', 'ź' => 'z', 'ż' => 'z',
        'ł' => 'l',
        'ß' => 'ss',
    ];

    public static function normalizeLocality(string $text): string
    {
        $s = mb_strtolower($text);
        $s = strtr($s, self::ACCENT_MAP);
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
    // 2. A per-code re-check ("does this code still have a non-soft-deleted group?") used
    //    to run here to drop codes like "AA" (reserved ISO) whose only groups are all
    //    soft-deleted. It was `WHERE country_code = ? AND deleted_at IS NULL` once per
    //    candidate — ~30ms each normally, but the `deleted_at` predicate breaks the same
    //    loose-index-scan optimization as in (1), so during a GBIF monthly refresh (when
    //    locality_groups is under heavy write churn) each check ran 8s+ and the ~300-code
    //    loop blew straight past the request timeout, taking Impact/Explore/Activity down
    //    every month. Dropped: an all-soft-deleted country lingering in the dropdown just
    //    yields an empty listing when picked — cosmetic, not an outage. TTL is long (a
    //    day) because the set of countries with data is extremely stable.
    public static function activeCountryCodes(): \Illuminate\Support\Collection
    {
        return \Illuminate\Support\Facades\Cache::remember('locality_groups:active_country_codes', 86400, function () {
            return \Illuminate\Support\Facades\DB::table('locality_groups')
                ->whereNotNull('country_code')
                ->where('country_code', '!=', '')
                ->distinct()
                ->pluck('country_code')
                ->filter(fn($code) => preg_match('/^[A-Z]{2}$/', $code))
                ->sort()
                ->values();
        });
    }

    // Single source of truth for a group's per-status occurrence breakdown — derived fresh
    // from occurrences.georef_status every time, not accumulated via +1/-1 bookkeeping, so
    // it self-heals regardless of which of the many code paths changed an occurrence's
    // status. Scoped to one locality_group_id (indexed, at most a few hundred rows), so
    // this stays cheap even though it's called synchronously after every user action.
    // deleted_at IS NULL matters: a soft-deleted occurrence (--prune-deleted) must not keep
    // counting toward its group's totals — the previous version of this query omitted that
    // filter entirely, unlike every other counter-maintenance query in the codebase.
    public function recalculateCounters(): void
    {
        \Illuminate\Support\Facades\DB::statement("
            UPDATE locality_groups lg
            JOIN (
                SELECT
                    COUNT(*)                                             AS total,
                    COALESCE(SUM(georef_status = 'validated'), 0)        AS validated,
                    COALESCE(SUM(georef_status = 'ungeoreferenced'), 0)  AS ungeoreferenced,
                    COALESCE(SUM(georef_status = 'has_suggestion'), 0)   AS has_suggestion,
                    COALESCE(SUM(georef_status = 'conflicted'), 0)       AS conflicted,
                    COALESCE(SUM(georef_status = 'gbif_georeferenced'), 0) AS gbif_georeferenced,
                    COALESCE(SUM(georef_status = 'gbif_reviewed'), 0)    AS gbif_reviewed
                FROM occurrences
                WHERE locality_group_id = ?
                  AND deleted_at IS NULL
            ) occ ON lg.id = ?
            SET
                lg.occurrence_count         = occ.total,
                lg.pending_count            = occ.has_suggestion + occ.conflicted,
                lg.has_suggestion_count     = occ.has_suggestion,
                lg.conflicted_count         = occ.conflicted,
                lg.validated_count          = occ.validated,
                lg.ungeoreferenced_count    = occ.ungeoreferenced,
                lg.gbif_georeferenced_count = occ.gbif_georeferenced,
                lg.gbif_reviewed_count      = occ.gbif_reviewed,
                lg.updated_at               = NOW()
        ", [$this->id, $this->id]);

        $this->refresh();
    }
}