<?php

namespace App\Modules\Users;

use App\Modules\ModuleDefinition;

final class UsersModule implements ModuleDefinition
{
    public function code(): string
    {
        return 'users';
    }

    public function name(): string
    {
        return 'Usuarios';
    }

    public function description(): string
    {
        return 'Administración de usuarios y sus roles';
    }

    public function routeFile(): string
    {
        return base_path('routes/modules/users.php');
    }

    public function navigation(): array
    {
        return [
            'title' => 'Usuarios',
            'icon' => 'Users',
            'route' => 'users.index',
        ];
    }
}
