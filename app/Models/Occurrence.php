<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Occurrence extends Model
{
    protected $fillable = [
        'gbif_occurrence_key',
        'dataset_key',
        'publisher_key',
        'catalog_number',
        'institution_code',
        'collection_code',
        'basis_of_record',
        'verbatim_locality',
        'country',
        'country_code',
        'state_province',
        'county',
        'municipality',
        'island',
        'island_group',
        'water_body',
        'higher_geography',
        'event_date',
        'recorded_by',
        'field_number',
        'gbif_decimal_latitude',
        'gbif_decimal_longitude',
        'gbif_geodetic_datum',
        'gbif_coordinate_uncertainty_m',
        'locality_group_id',
        'georef_status',
        'synced_at',
    ];

protected $casts = [
    'gbif_decimal_latitude' => 'decimal:7',
    'gbif_decimal_longitude' => 'decimal:7',
    'media' => 'array',
    'synced_at' => 'datetime',
];

    public function localityGroup(): BelongsTo
    {
        return $this->belongsTo(LocalityGroup::class);
    }

    public function suggestions(): HasMany
    {
        return $this->hasMany(GeorefSuggestion::class);
    }

    public function validatedSuggestion(): HasMany
    {
        return $this->hasMany(GeorefSuggestion::class)->where('status', 'validated');
    }

    public function isGeoreferenced(): bool
    {
        return in_array($this->georef_status, ['gbif_georeferenced', 'gbif_reviewed', 'validated']);
    }

    public function needsGeoreferencing(): bool
    {
        return in_array($this->georef_status, ['ungeoreferenced', 'has_suggestion']);
    }


}