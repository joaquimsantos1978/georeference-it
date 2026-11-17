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

    // Stand-in address given to people who sign in through a provider (ORCID) that doesn't
    // expose an email (see SocialiteController). It has no mailbox: anything sent to it
    // bounces back to info@, so no mail may ever be sent there.
    public const PLACEHOLDER_EMAIL_DOMAIN = 'no-email.georeference.it';

    public function hasDeliverableEmail(): bool
    {
        return !str_ends_with(strtolower((string) $this->email), '@' . self::PLACEHOLDER_EMAIL_DOMAIN);
    }

    public function scopeWithDeliverableEmail(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->where('email', 'not like', '%@' . self::PLACEHOLDER_EMAIL_DOMAIN);
    }

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
'locale',
'timezone',
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

    public function badges(): HasMany
    {
        return $this->hasMany(UserBadge::class);
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