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
        'country_code',
        'state_province',
        'county',
        'municipality',
        'occurrence_count',
        'pending_count',
        'validated_count',
    ];

    public function occurrences(): HasMany
    {
        return $this->hasMany(Occurrence::class);
    }

    public function suggestions(): HasMany
    {
        return $this->hasMany(GeorefSuggestion::class);
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
}