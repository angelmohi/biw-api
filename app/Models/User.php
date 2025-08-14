<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the role that owns the user.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Check if user is a full administrator
     */
    public function isFullAdministrator(): bool
    {
        return $this->role && $this->role->isFullAdministrator();
    }

    /**
     * Check if user is staff
     */
    public function isStaff(): bool
    {
        return $this->role && $this->role->isStaff();
    }

    /**
     * Check if user has administrative privileges
     */
    public function canManageAccess(): bool
    {
        return $this->isFullAdministrator();
    }

    /**
     * The leagues that belong to the user.
     */
    public function leagues()
    {
        // If user is Full Administrator, return all leagues
        if ($this->isFullAdministrator()) {
            return League::query();
        }

        return $this->belongsToMany(League::class, 'user_leagues')
                    ->withPivot('is_active')
                    ->withTimestamps()
                    ->wherePivot('is_active', true);
    }

    /**
     * Get all leagues (including inactive ones) that belong to the user.
     */
    public function allLeagues()
    {
        // If user is Full Administrator, return all leagues
        if ($this->isFullAdministrator()) {
            return League::query();
        }

        return $this->belongsToMany(League::class, 'user_leagues')
                    ->withPivot('is_active')
                    ->withTimestamps();
    }

    /**
     * Check if user has access to a specific league.
     */
    public function hasAccessToLeague($leagueId): bool
    {
        // Full Administrators have access to all leagues
        if ($this->isFullAdministrator()) {
            return League::where('id', $leagueId)->exists();
        }

        return $this->leagues()->where('league.id', $leagueId)->exists();
    }

    /**
     * Check if user has a specific role in a league.
     * Note: This method is deprecated since we removed roles from user_leagues
     */
    public function hasRoleInLeague($leagueId, $role): bool
    {
        // Full Administrators always have full access
        if ($this->isFullAdministrator()) {
            return true;
        }

        // For staff users, they just have access or not (no specific roles)
        return $this->hasAccessToLeague($leagueId);
    }

    /**
     * Get user's role in a specific league.
     * Note: This method is deprecated since we removed roles from user_leagues
     */
    public function getRoleInLeague($leagueId): ?string
    {
        // Full Administrators always have owner-like permissions
        if ($this->isFullAdministrator()) {
            return 'owner';
        }

        // Staff users just have viewer access
        if ($this->hasAccessToLeague($leagueId)) {
            return 'viewer';
        }

        return null;
    }
}
