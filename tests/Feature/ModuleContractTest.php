<?php

use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use App\Modules\Audit\AuditModule;
use App\Modules\ModuleCatalog;
use App\Modules\ModuleDefinition;
use App\Modules\Roles\RolesModule;
use App\Modules\Users\UsersModule;
use Database\Seeders\ModuleSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;

test('roles and users satisfy the module contract and stay in the registry', function () {
    $modules = ModuleCatalog::definitions();

    expect($modules)->toHaveCount(3)
        ->and($modules[0])->toBeInstanceOf(ModuleDefinition::class)
        ->and($modules[0])->toBeInstanceOf(RolesModule::class)
        ->and($modules[0]->code())->toBe('roles')
        ->and($modules[1])->toBeInstanceOf(UsersModule::class)
        ->and($modules[1]->code())->toBe('users')
        ->and($modules[2])->toBeInstanceOf(AuditModule::class)
        ->and($modules[2]->code())->toBe('audit')
        ->and($modules[0]->navigation()['route'])->toBe('roles.index')
        ->and($modules[1]->navigation()['route'])->toBe('users.index')
        ->and($modules[2]->navigation()['route'])->toBe('audit.index');
});

test('a repeated module code is rejected when the registry loads', function () {
    expect(fn () => ModuleCatalog::fromClasses([
        RolesModule::class,
        RolesModule::class,
    ]))->toThrow(RuntimeException::class);
});

test('the seeder writes the registry name and description and reactivates the module', function () {
    Module::query()->create([
        'code' => 'roles',
        'name' => 'Viejo',
        'description' => 'Vieja',
        'is_active' => false,
    ]);

    $this->seed(ModuleSeeder::class);
    $this->seed(ModuleSeeder::class);

    $module = Module::query()->where('code', 'roles')->first();

    expect(Module::query()->where('code', 'roles')->count())->toBe(1)
        ->and($module->name)->toBe('Roles')
        ->and($module->description)->toBe('Administración de roles y sus permisos')
        ->and($module->is_active)->toBeTrue();
});

test('the seeder deactivates a module that left the registry and keeps the row', function () {
    Module::query()->create([
        'code' => 'legacy',
        'name' => 'Legacy',
        'description' => 'Fuera',
        'is_active' => true,
    ]);

    $this->seed(ModuleSeeder::class);

    $module = Module::query()->where('code', 'legacy')->first();

    expect($module)->not->toBeNull()
        ->and($module->name)->toBe('Legacy')
        ->and($module->is_active)->toBeFalse();
});

test('module route files do not open their own auth group', function () {
    foreach (ModuleCatalog::definitions() as $module) {
        $contents = file_get_contents($module->routeFile());

        expect($contents)->not->toContain("'auth'")
            ->and($contents)->not->toContain('"auth"');
    }
});

test('the menu resolves module links on the server in registry order', function () {
    $user = User::factory()->create();
    $role = new Role;
    $role->forceFill([
        'name' => 'Owner',
        'is_system' => true,
    ])->save();
    $user->roles()->attach($role);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('navigation', 3)
            ->where('navigation.0.title', 'Roles')
            ->where('navigation.0.href', route('roles.index'))
            ->where('navigation.0.icon', 'Shield')
            ->where('navigation.1.title', 'Usuarios')
            ->where('navigation.1.href', route('users.index'))
            ->where('navigation.1.icon', 'Users')
            ->where('navigation.2.title', 'Auditoría')
            ->where('navigation.2.href', route('audit.index'))
            ->where('navigation.2.icon', 'ScrollText')
        );
});

test('the login page does not build the module menu', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('navigation', []));
});

test('a module outside the registry has no menu link even when its row remains', function () {
    $user = User::factory()->create();
    $role = Role::query()->create([
        'name' => 'Ops',
    ]);
    $module = Module::query()->create([
        'code' => 'legacy',
        'name' => 'Legacy',
        'description' => 'Fuera',
        'is_active' => true,
    ]);
    $role->modules()->attach($module->id, [
        'can_view' => true,
        'can_create' => false,
        'can_update' => false,
        'can_delete' => false,
    ]);
    $user->roles()->attach($role);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('navigation', [])
        );
});

test('saving a role keeps the permission of a module the form did not show', function () {
    $user = User::factory()->create();
    $system = new Role;
    $system->forceFill([
        'name' => 'Owner',
        'is_system' => true,
    ])->save();
    $user->roles()->attach($system);

    $role = Role::query()->create([
        'name' => 'Ops',
    ]);
    $hidden = Module::query()->create([
        'code' => 'legacy',
        'name' => 'Legacy',
        'is_active' => false,
    ]);
    $visible = Module::query()->create([
        'code' => 'visible',
        'name' => 'Visible',
        'is_active' => true,
    ]);
    $removed = Module::query()->create([
        'code' => 'removed',
        'name' => 'Removed',
        'is_active' => true,
    ]);
    $flags = [
        'can_view' => true,
        'can_create' => true,
        'can_update' => false,
        'can_delete' => false,
    ];
    $role->modules()->attach($hidden->id, $flags);
    $role->modules()->attach($removed->id, $flags);

    $this->actingAs($user)
        ->put(route('roles.update', $role), [
            'name' => 'Ops',
            'description' => null,
            'permissions' => [
                [
                    'module_id' => $visible->id,
                    'can_view' => '1',
                    'can_create' => '0',
                    'can_update' => '0',
                    'can_delete' => '0',
                ],
                [
                    'module_id' => $removed->id,
                    'can_view' => '0',
                    'can_create' => '0',
                    'can_update' => '0',
                    'can_delete' => '0',
                ],
            ],
        ])
        ->assertRedirect(route('roles.index'));

    $this->assertDatabaseHas('role_modules', [
        'role_id' => $role->id,
        'module_id' => $hidden->id,
        'can_view' => true,
    ]);
    $this->assertDatabaseHas('role_modules', [
        'role_id' => $role->id,
        'module_id' => $visible->id,
        'can_view' => true,
    ]);
    $this->assertDatabaseMissing('role_modules', [
        'role_id' => $role->id,
        'module_id' => $removed->id,
    ]);

    $hidden->update(['is_active' => true]);

    $this->assertDatabaseHas('role_modules', [
        'role_id' => $role->id,
        'module_id' => $hidden->id,
        'can_view' => true,
    ]);
});
