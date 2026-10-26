<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Modules\ModuleCatalog;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $codes = [];

        foreach (ModuleCatalog::definitions() as $module) {
            $codes[] = $module->code();

            Module::query()->updateOrCreate(
                ['code' => $module->code()],
                [
                    'name' => $module->name(),
                    'description' => $module->description(),
                    'is_active' => true,
                ],
            );
        }

        $absent = Module::query();

        if ($codes === []) {
            $absent->update(['is_active' => false]);

            return;
        }

        $absent->whereNotIn('code', $codes)->update(['is_active' => false]);
    }
}
