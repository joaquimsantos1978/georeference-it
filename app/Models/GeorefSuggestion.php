<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeorefSuggestion extends Model
{
    protected $fillable = [
        'occurrence_id',
        'locality_group_id',
        'locality_group_hash',
        'user_id',
        'anon_name',
        'session_id',
        'decimal_latitude',
        'decimal_longitude',
        'geodetic_datum',
        'coordinate_uncertainty_m',
        'coordinate_precision',
        'point_radius_spatial_fit',
        'footprint_wkt',
        'footprint_srs',
        'footprint_spatial_fit',
        'location_id',
        'georeference_protocol',
        'georeference_sources',
        'georeference_remarks',
        'share_link',
        'status',
        'total_points',
        'georeferenced_date',
    ];

    protected $casts = [
        'decimal_latitude' => 'decimal:7',
        'decimal_longitude' => 'decimal:7',
        'coordinate_precision' => 'decimal:7',
        'point_radius_spatial_fit' => 'decimal:3',
        'footprint_spatial_fit' => 'decimal:3',
        'georeferenced_date' => 'datetime',
    ];

    public function occurrence(): BelongsTo
    {
        return $this->belongsTo(Occurrence::class);
    }

    public function localityGroup(): BelongsTo
    {
        return $this->belongsTo(LocalityGroup::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function validations(): HasMany
    {
        return $this->hasMany(GeorefValidation::class, 'suggestion_id');
    }

    public function getSubmittedByAttribute(): string
    {
        if ($this->user) {
            return $this->user->public_name ? $this->user->name : 'Hidden contributor';
        }
        return $this->anon_name ?? 'Anonymous';
    }

    public function isValidated(): bool
    {
        $threshold = PlatformSetting::get('validation_threshold', 60);
        return $this->total_points >= $threshold;
    }
public function exclusions(): HasMany
{
    return $this->hasMany(GeorefSuggestionExclusion::class, 'suggestion_id');
}

public function excludedOccurrences(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
{
    return $this->belongsToMany(Occurrence::class, 'georef_suggestion_exclusions', 'suggestion_id', 'occurrence_id');
}
}