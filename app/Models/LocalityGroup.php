<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LocalityGroup extends Model
{
    protected $fillable = [
        'group_hash',
        'locality_string',
        'verbatim_locality',
        'normalized_locality',
        'country_code',
        'state_province',
        'county',
        'municipality',
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
            strtolower(trim($fields['country_code'] ?? '')),
            strtolower(trim($fields['state_province'] ?? '')),
            strtolower(trim($fields['county'] ?? '')),
            strtolower(trim($fields['municipality'] ?? '')),
            strtolower(trim($verbatimLocality)),
        ]);

        return sha1(implode('|', $parts));
    }
    public function comments(): HasMany
    {
        return $this->hasMany(LocalityGroupComment::class);
    }

    public function recalculateCounters(): void
    {
        \Illuminate\Support\Facades\DB::statement("
            UPDATE locality_groups lg
            JOIN (
                SELECT
                    COUNT(*)                             AS total,
                    SUM(georef_status = 'validated')     AS validated,
                    SUM(georef_status = 'ungeoreferenced') AS ungeoreferenced
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