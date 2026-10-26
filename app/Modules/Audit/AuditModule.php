<?php

namespace App\Modules\Audit;

use App\Modules\ModuleDefinition;

final class AuditModule implements ModuleDefinition
{
    public function code(): string
    {
        return 'audit';
    }

    public function name(): string
    {
        return 'Auditoría';
    }

    public function description(): string
    {
        return 'Historial de accesos y cambios';
    }

    public function routeFile(): string
    {
        return base_path('routes/modules/audit.php');
    }

    public function navigation(): array
    {
        return [
            'title' => 'Auditoría',
            'icon' => 'ScrollText',
            'route' => 'audit.index',
        ];
    }
}
