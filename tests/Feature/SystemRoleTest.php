<?php

use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ModuleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

test('the module catalog seeds roles and users without duplicating them', function () {
    $this->seed(ModuleSeeder::class);
    $this->seed(ModuleSeeder::class);

    expect(Module::query()->whereIn('code', ['roles', 'users'])->count())->toBe(2);
    $this->assertDatabaseHas('modules', ['code' => 'roles', 'name' => 'Roles']);
    $this->assertDatabaseHas('modules', ['code' => 'users', 'name' => 'Usuarios']);
});

test('system role permissions do not depend on the super admin name', function () {
    Module::query()->create([
        'code' => 'roles',
        'name' => 'Roles',
    ]);
    $user = userHoldingSystemRole();

    expect($user->getAllPermissions()['roles'])->toBe([
        'can_view' => true,
        'can_create' => true,
        'can_update' => true,
        'can_delete' => true,
    ]);
});

test('a system role cannot be edited updated or deleted', function () {
    $admin = userHoldingSystemRole();
    $role = $admin->roles()->first();
    $module = Module::query()->create([
        'code' => 'roles',
        'name' => 'Roles',
    ]);
    $role->modules()->attach($module->id, [
        'can_view' => true,
        'can_create' => false,
        'can_update' => false,
        'can_delete' => false,
    ]);

    $this->actingAs($admin)
        ->from(route('roles.index'))
        ->get(route('roles.edit', $role))
        ->assertRedirect(route('roles.index'))
        ->assertSessionHasErrors([
            'error' => 'No es posible modificar un rol de sistema.',
        ]);

    $this->actingAs($admin)
        ->from(route('roles.index'))
        ->put(route('roles.update', $role), [
            'name' => 'Renamed',
            'description' => 'Changed',
            'is_system' => false,
            'permissions' => [
                [
                    'module_id' => $module->id,
                    'can_view' => true,
                    'can_create' => true,
                    'can_update' => true,
                    'can_delete' => true,
                ],
            ],
        ])
        ->assertRedirect(route('roles.index'))
        ->assertSessionHasErrors([
            'error' => 'No es posible modificar un rol de sistema.',
        ]);

    $this->actingAs($admin)
        ->from(route('roles.index'))
        ->delete(route('roles.destroy', $role))
        ->assertRedirect(route('roles.index'))
        ->assertSessionHasErrors([
            'error' => 'No es posible modificar un rol de sistema.',
        ]);

    $role->refresh();

    expect($role->name)->toBe('Owner')
        ->and($role->is_system)->toBeTrue();
    $this->assertDatabaseHas('role_modules', [
        'role_id' => $role->id,
        'module_id' => $module->id,
        'can_view' => true,
        'can_create' => false,
        'can_update' => false,
        'can_delete' => false,
    ]);
});

test('a second system role cannot be stored', function () {
    $role = new Role;
    $role->forceFill([
        'name' => 'Owner',
        'is_system' => true,
    ])->save();

    $second = new Role;
    $second->forceFill([
        'name' => 'Other',
        'is_system' => true,
    ]);

    expect(fn () => $second->save())->toThrow(QueryException::class);
    $this->assertModelExists($role);
    expect($role->fresh()->is_system)->toBeTrue();
});

test('the system role cannot be assigned to a second user or removed from the first', function () {
    $admin = userHoldingSystemRole();
    $role = $admin->roles()->first();
    $other = User::factory()->create();

    expect(fn () => $other->roles()->attach($role))->toThrow(ValidationException::class);
    expect(fn () => $admin->roles()->detach($role))->toThrow(ValidationException::class);

    $this->assertDatabaseMissing('user_roles', [
        'user_id' => $other->id,
        'role_id' => $role->id,
    ]);
    $this->assertDatabaseHas('user_roles', [
        'user_id' => $admin->id,
        'role_id' => $role->id,
    ]);
});

test('the user who holds the system role cannot be deleted', function () {
    $admin = userHoldingSystemRole();

    expect(fn () => $admin->delete())->toThrow(ValidationException::class);

    $this->assertModelExists($admin);
});

test('a role without the system flag can be renamed', function () {
    $admin = userHoldingSystemRole();
    $module = Module::query()->create([
        'code' => 'roles',
        'name' => 'Roles',
    ]);
    $role = Role::query()->create([
        'name' => 'Analyst',
    ]);

    $response = $this->actingAs($admin)->put(route('roles.update', $role), [
        'name' => 'Supervisor',
        'description' => null,
        'permissions' => [
            [
                'module_id' => $module->id,
                'can_view' => true,
                'can_create' => false,
                'can_update' => false,
                'can_delete' => false,
            ],
        ],
    ]);

    $response->assertRedirect(route('roles.index'));
    $this->assertDatabaseHas('roles', [
        'id' => $role->id,
        'name' => 'Supervisor',
    ]);
});

function userHoldingSystemRole(): User
{
    $user = User::factory()->create();
    $role = new Role;
    $role->forceFill([
        'name' => 'Owner',
        'is_system' => true,
    ])->save();
    $user->roles()->attach($role);

    return $user;
}
