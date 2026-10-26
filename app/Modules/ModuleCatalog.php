<?php

namespace App\Modules;

use App\Modules\Audit\AuditModule;
use App\Modules\Roles\RolesModule;
use App\Modules\Users\UsersModule;
use RuntimeException;

final class ModuleCatalog
{
    /**
     * @return list<ModuleDefinition>
     */
    public static function definitions(): array
    {
        return self::fromClasses([
            RolesModule::class,
            UsersModule::class,
            AuditModule::class,
        ]);
    }

    /**
     * @param  list<class-string<ModuleDefinition>>  $classes
     * @return list<ModuleDefinition>
     */
    public static function fromClasses(array $classes): array
    {
        $modules = [];
        $codes = [];

        foreach ($classes as $class) {
            $module = new $class;

            if (! $module instanceof ModuleDefinition) {
                throw new RuntimeException("La clase [{$class}] no cumple el contrato de módulo.");
            }

            $code = $module->code();

            if (isset($codes[$code])) {
                throw new RuntimeException("El código de módulo [{$code}] está repetido.");
            }

            $codes[$code] = true;
            $modules[] = $module;
        }

        return $modules;
    }
}
