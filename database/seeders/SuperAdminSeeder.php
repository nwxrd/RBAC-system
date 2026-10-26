<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL');
        $password = env('SUPER_ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command->error('Define SUPER_ADMIN_EMAIL y SUPER_ADMIN_PASSWORD en el .env');

            return;
        }

        $name = env('SUPER_ADMIN_NAME', 'Super Admin');

        $role = Role::query()->where('is_system', true)->first();

        if ($role === null) {
            $role = new Role;
            $role->forceFill([
                'name' => 'Super Admin',
                'description' => 'Acceso total al sistema',
                'is_system' => true,
            ])->save();
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $user = new User;
            $user->forceFill([
                'name' => $name,
                'email' => $email,
                'email_verified_at' => now(),
                'password' => $password,
                'is_active' => true,
            ])->save();
        }

        $alreadyAssigned = $user->roles()->whereKey($role->id)->exists();

        if (! $alreadyAssigned) {
            $user->roles()->attach($role->id, [
                'created_by' => $user->id,
            ]);
        }

        if ($user->created_by === null) {
            $user->forceFill(['created_by' => $user->id])->save();
        }

        if ($role->created_by === null) {
            $role->forceFill(['created_by' => $user->id])->save();
        }

        $this->command->info("Super Admin listo: {$email}");
    }
}
