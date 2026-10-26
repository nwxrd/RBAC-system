<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Validation\ValidationException;

class UserRole extends Pivot
{
    public const UPDATED_AT = null;

    protected $table = 'user_roles';

    public $incrementing = false;

    protected static function booted(): void
    {
        static::creating(function (UserRole $pivot): void {
            $roleIsSystem = Role::query()
                ->whereKey($pivot->role_id)
                ->where('is_system', true)
                ->exists();

            if (! $roleIsSystem) {
                return;
            }

            $assignedToSomeoneElse = static::query()
                ->where('role_id', $pivot->role_id)
                ->where('user_id', '!=', $pivot->user_id)
                ->exists();

            if ($assignedToSomeoneElse) {
                throw ValidationException::withMessages([
                    'role_id' => 'El rol de sistema ya está asignado a un usuario.',
                ]);
            }
        });

        static::deleting(function (UserRole $pivot): void {
            $roleIsSystem = Role::query()
                ->whereKey($pivot->role_id)
                ->where('is_system', true)
                ->exists();

            if ($roleIsSystem) {
                throw ValidationException::withMessages([
                    'role_id' => 'No es posible quitar el rol de sistema de su usuario.',
                ]);
            }
        });
    }
}
