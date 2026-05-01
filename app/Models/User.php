<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'entity_id', 'filiale_id', 'is_active',
        'last_login_at', 'login_count',
        'two_factor_secret', 'two_factor_confirmed_at',
        'password_changed_at',
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret'];

    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'last_login_at'           => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password_changed_at'     => 'datetime',
            'password'                => 'hashed',
            'is_active'               => 'boolean',
        ];
    }

    public function entity(): BelongsTo   { return $this->belongsTo(Entity::class); }
    public function filiale(): BelongsTo  { return $this->belongsTo(Filiale::class); }
    public function documents(): HasMany  { return $this->hasMany(Document::class, 'uploaded_by'); }
    public function auditLogs(): HasMany  { return $this->hasMany(AuditLog::class); }

    public function isAdmin(): bool    { return $this->role === 'admin'; }
    public function isDmc(): bool      { return $this->role === 'dmc'; }
    public function isRmc(): bool      { return $this->role === 'rmc'; }
    public function isVisiteur(): bool { return $this->role === 'visiteur'; }

    public function canUpload(): bool       { return in_array($this->role, ['admin', 'dmc']); }
    public function canDownload(): bool     { return in_array($this->role, ['admin', 'dmc', 'rmc']); }
    public function canDelete(): bool       { return in_array($this->role, ['admin', 'dmc', 'rmc']); }
    public function canManageUsers(): bool  { return $this->role === 'admin'; }

    public function hasTwoFactorEnabled(): bool
    {
        return !is_null($this->two_factor_confirmed_at);
    }

    public function isPasswordExpired(): bool
    {
        $changedAt = $this->password_changed_at ?? $this->created_at;
        return $changedAt->lt(now()->subDays(90));
    }
}
