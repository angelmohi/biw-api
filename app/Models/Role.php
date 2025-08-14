<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    /**
     * Role constants
     */
    const FULL_ADMINISTRATOR = 'full_administrator';
    const STAFF = 'staff';

    /**
     * Get the users for the role.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Check if this is a full administrator role
     */
    public function isFullAdministrator(): bool
    {
        return $this->name === self::FULL_ADMINISTRATOR;
    }

    /**
     * Check if this is a staff role
     */
    public function isStaff(): bool
    {
        return $this->name === self::STAFF;
    }

    /**
     * Get role by name
     */
    public static function getByName(string $name): ?Role
    {
        return static::where('name', $name)->first();
    }
}
