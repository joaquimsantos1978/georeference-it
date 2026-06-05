<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeorefValidation extends Model
{
    protected $fillable = [
        'suggestion_id',
        'user_id',
        'vote',
        'points_awarded',
    ];

    public function suggestion(): BelongsTo
    {
        return $this->belongsTo(GeorefSuggestion::class, 'suggestion_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}