<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocalityGroupComment extends Model
{
    protected $fillable = [
        'locality_group_id',
        'user_id',
        'body',
    ];

    public function localityGroup(): BelongsTo
    {
        return $this->belongsTo(LocalityGroup::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}