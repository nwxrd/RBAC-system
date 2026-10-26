<?php

namespace App\Models;

use App\Traits\HasAuditColumns;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property bool $is_active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    use HasAuditColumns, HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            if ($user->hasSystemRole()) {
                throw ValidationException::withMessages([
                    'user' => 'No es posible eliminar el usuario del rol de sistema.',
                ]);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot('created_by')
            ->using(UserRole::class);
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    public function hasSystemRole(): bool
    {
        $this->loadMissing('roles');

        return $this->roles->contains(
            fn (Role $role): bool => (bool) $role->is_system,
        );
    }

    public function canOnModule(string $moduleCode, string $action): bool
    {
        if (! in_array($action, ['can_view', 'can_create', 'can_update', 'can_delete'], true)) {
            return false;
        }

        $this->loadMissing('roles.modules');

        if ($this->hasSystemRole()) {
            return true;
        }

        foreach ($this->roles as $role) {
            $module = $role->modules->firstWhere('code', $moduleCode);

            if ($module === null) {
                continue;
            }

            if ((bool) (int) $module->pivot->getAttribute($action)) {
                return true;
            }
        }

        return false;
    }

    public function getAllPermissions(): array
    {
        if ($this->hasSystemRole()) {
            return Module::all()->mapWithKeys(function ($module) {
                return [
                    $module->code => [
                        'can_view' => true,
                        'can_create' => true,
                        'can_update' => true,
                        'can_delete' => true,
                    ],
                ];
            })->toArray();
        }

        $permissions = [];

        foreach ($this->roles as $role) {
            foreach ($role->modules as $module) {
                $code = $module->code;
                $pivot = $module->pivot;

                if (! isset($permissions[$code])) {
                    $permissions[$code] = [
                        'can_view' => false,
                        'can_create' => false,
                        'can_update' => false,
                        'can_delete' => false,
                    ];
                }

                $permissions[$code]['can_view'] = $permissions[$code]['can_view'] || (bool) $pivot->can_view;
                $permissions[$code]['can_create'] = $permissions[$code]['can_create'] || (bool) $pivot->can_create;
                $permissions[$code]['can_update'] = $permissions[$code]['can_update'] || (bool) $pivot->can_update;
                $permissions[$code]['can_delete'] = $permissions[$code]['can_delete'] || (bool) $pivot->can_delete;
            }
        }

        return $permissions;
    }
}
