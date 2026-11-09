<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property UserRole|null $role
 * @property string $public_id
 * @property bool $is_active
 * @property int $auth_version
 * @property int|null $birthday_month
 * @property int|null $birthday_day
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    protected $attributes = ['is_active' => true, 'auth_version' => 1];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'auth_version' => 'integer',
            'birthday_month' => 'integer',
            'birthday_day' => 'integer',
        ];
    }

    public function isCapitolAdmin(): bool
    {
        return $this->is_active && in_array($this->role, [UserRole::SuperAdmin, UserRole::Admin], true);
    }

    public function canEnterWorkspace(): bool
    {
        return $this->is_active && $this->role !== null;
    }

    /** @return BelongsToMany<Municipality, $this> */
    public function assignedTowns(): BelongsToMany
    {
        return $this->belongsToMany(Municipality::class, 'municipality_user');
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            $user->public_id ??= (string) Str::uuid();
        });
        static::updating(function (User $user): void {
            if ($user->isDirty(['role', 'is_active', 'password'])) {
                $user->auth_version++;
            }
        });
    }
}
