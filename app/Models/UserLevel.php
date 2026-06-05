<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserLevel extends Model
{
    protected $fillable = [
        'name',
        'min_validated',
        'vote_weight',
        'sort_order',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}