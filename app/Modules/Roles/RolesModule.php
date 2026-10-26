<?php

namespace App\Modules\Roles;

use App\Modules\ModuleDefinition;

final class RolesModule implements ModuleDefinition
{
    public function code(): string
    {
        return 'roles';
    }

    public function name(): string
    {
        return 'Roles';
    }

    public function description(): string
    {
        return 'Administración de roles y sus permisos';
    }

    public function routeFile(): string
    {
        return base_path('routes/modules/roles.php');
    }

    public function navigation(): array
    {
        return [
            'title' => 'Roles',
            'icon' => 'Shield',
            'route' => 'roles.index',
        ];
    }
}
