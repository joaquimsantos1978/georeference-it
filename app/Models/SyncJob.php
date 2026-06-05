<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncJob extends Model
{
    protected $fillable = [
        'work_session_id',
        'country_code',
        'dataset_key',
        'bbox_north',
        'bbox_south',
        'bbox_east',
        'bbox_west',
        'fetched_count',
        'total_count',
        'offset',
        'status',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'bbox_north' => 'decimal:7',
        'bbox_south' => 'decimal:7',
        'bbox_east' => 'decimal:7',
        'bbox_west' => 'decimal:7',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function workSession(): BelongsTo
    {
        return $this->belongsTo(WorkSession::class);
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function progressPercentage(): int
    {
        if (!$this->total_count || $this->total_count === 0) {
            return 0;
        }
        return (int) round(($this->fetched_count / $this->total_count) * 100);
    }
}