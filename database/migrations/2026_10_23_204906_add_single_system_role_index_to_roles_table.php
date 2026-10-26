<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('alter table roles add system_lock tinyint generated always as (case when is_system = 1 then 1 else null end) stored');
            DB::statement('alter table roles add unique index roles_system_lock_unique (system_lock)');

            return;
        }

        $condition = $driver === 'pgsql' ? 'is_system' : 'is_system = 1';

        DB::statement("create unique index roles_one_system_role on roles (is_system) where {$condition}");
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropUnique('roles_system_lock_unique');
                $table->dropColumn('system_lock');
            });

            return;
        }

        if ($driver === 'sqlsrv') {
            DB::statement('drop index roles_one_system_role on roles');

            return;
        }

        DB::statement('drop index if exists roles_one_system_role');
    }
};
