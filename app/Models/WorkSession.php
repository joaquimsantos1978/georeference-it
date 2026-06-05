<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkSession extends Model
{
    protected $fillable = [
        'user_id',
        'area_name',
        'country_code',
        'dataset_key',
        'bbox_north',
        'bbox_south',
        'bbox_east',
        'bbox_west',
        'occurrences_done',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'bbox_north' => 'decimal:7',
        'bbox_south' => 'decimal:7',
        'bbox_east' => 'decimal:7',
        'bbox_west' => 'decimal:7',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function syncJobs(): HasMany
    {
        return $this->hasMany(SyncJob::class);
    }

    public function isActive(): bool
    {
        return $this->started_at !== null && $this->ended_at === null;
    }
}