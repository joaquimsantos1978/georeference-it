<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'orcid',
        'provider',
        'provider_id',
        'role',
        'user_level_id',
        'total_validated',
        'avatar',
        'bio',
'email_notifications',
'preferred_task',
'public_name',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'email_notifications' => 'boolean',
            'public_name' => 'boolean',
        ];
    }

    public function userLevel(): BelongsTo
    {
        return $this->belongsTo(UserLevel::class);
    }

    public function suggestions(): HasMany
    {
        return $this->hasMany(GeorefSuggestion::class);
    }

    public function validations(): HasMany
    {
        return $this->hasMany(GeorefValidation::class);
    }

    public function workSessions(): HasMany
    {
        return $this->hasMany(WorkSession::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isModerator(): bool
    {
        return in_array($this->role, ['admin', 'moderator']);
    }

    public function getVoteWeight(): int
    {
        return $this->userLevel?->vote_weight ?? 10;
    }

    public function updateLevel(): void
    {
        $level = UserLevel::where('min_validated', '<=', $this->total_validated)
            ->orderBy('min_validated', 'desc')
            ->first();

        if ($level && $this->user_level_id !== $level->id) {
            $this->update(['user_level_id' => $level->id]);
        }
    }

public function notifications(): HasMany
{
    return $this->hasMany(Notification::class);
}

public function unreadNotifications(): HasMany
{
    return $this->hasMany(Notification::class)->whereNull('read_at');
}
}