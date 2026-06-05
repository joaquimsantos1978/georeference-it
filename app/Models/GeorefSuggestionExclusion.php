<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeorefSuggestionExclusion extends Model
{
    protected $fillable = [
        'suggestion_id',
        'occurrence_id',
    ];

    public function suggestion(): BelongsTo
    {
        return $this->belongsTo(GeorefSuggestion::class, 'suggestion_id');
    }

    public function occurrence(): BelongsTo
    {
        return $this->belongsTo(Occurrence::class);
    }
}