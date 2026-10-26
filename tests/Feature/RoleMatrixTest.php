<?php

use App\Models\Module;
use App\Models\Role;
use App\Models\User;

test('storing a role saves viewable module permissions and skips the rest', function () {
    $admin = systemUser();
    $visible = Module::query()->create([
        'code' => 'roles',
        'name' => 'Roles',
    ]);
    $hidden = Module::query()->create([
        'code' => 'users',
        'name' => 'Users',
    ]);

    $response = $this->actingAs($admin)->post(route('roles.store'), [
        'name' => 'Analyst',
        'description' => 'Reads roles',
        'permissions' => [
            [
                'module_id' => $visible->id,
                'can_view' => true,
                'can_create' => false,
                'can_update' => true,
                'can_delete' => false,
            ],
            [
                'module_id' => $hidden->id,
                'can_view' => false,
                'can_create' => true,
                'can_update' => false,
                'can_delete' => false,
            ],
        ],
    ]);

    $role = Role::query()->where('name', 'Analyst')->first();

    $response->assertRedirect(route('roles.index'));
    $this->assertDatabaseHas('roles', [
        'name' => 'Analyst',
        'description' => 'Reads roles',
    ]);
    $this->assertDatabaseHas('role_modules', [
        'role_id' => $role->id,
        'module_id' => $visible->id,
        'can_view' => true,
        'can_create' => false,
        'can_update' => true,
        'can_delete' => false,
    ]);
    $this->assertDatabaseMissing('role_modules', [
        'role_id' => $role->id,
        'module_id' => $hidden->id,
    ]);
});

test('updating a role replaces the previous module permissions', function () {
    $admin = systemUser();
    $previous = Module::query()->create([
        'code' => 'roles',
        'name' => 'Roles',
    ]);
    $next = Module::query()->create([
        'code' => 'users',
        'name' => 'Users',
    ]);
    $role = Role::query()->create([
        'name' => 'Analyst',
    ]);
    $role->modules()->attach($previous->id, [
        'can_view' => true,
        'can_create' => false,
        'can_update' => false,
        'can_delete' => false,
    ]);

    $response = $this->actingAs($admin)->put(route('roles.update', $role), [
        'name' => 'Supervisor',
        'description' => 'Manages users',
        'permissions' => [
            [
                'module_id' => $next->id,
                'can_view' => true,
                'can_create' => true,
                'can_update' => false,
                'can_delete' => false,
            ],
        ],
    ]);

    $response->assertRedirect(route('roles.index'));
    $this->assertDatabaseHas('roles', [
        'id' => $role->id,
        'name' => 'Supervisor',
        'description' => 'Manages users',
    ]);
    $this->assertDatabaseMissing('role_modules', [
        'role_id' => $role->id,
        'module_id' => $previous->id,
    ]);
    $this->assertDatabaseHas('role_modules', [
        'role_id' => $role->id,
        'module_id' => $next->id,
        'can_view' => true,
        'can_create' => true,
        'can_update' => false,
        'can_delete' => false,
    ]);
});

test('deleting a role removes its module permissions and keeps the module', function () {
    $admin = systemUser();
    $module = Module::query()->create([
        'code' => 'roles',
        'name' => 'Roles',
    ]);
    $role = Role::query()->create([
        'name' => 'Analyst',
    ]);
    $role->modules()->attach($module->id, [
        'can_view' => true,
        'can_create' => false,
        'can_update' => false,
        'can_delete' => false,
    ]);

    $response = $this->actingAs($admin)->delete(route('roles.destroy', $role));

    $response->assertRedirect(route('roles.index'));
    $this->assertModelMissing($role);
    $this->assertDatabaseMissing('role_modules', [
        'role_id' => $role->id,
        'module_id' => $module->id,
    ]);
    $this->assertModelExists($module);
});

test('a role assigned to a user is not deleted', function () {
    $admin = systemUser();
    $role = Role::query()->create([
        'name' => 'Analyst',
    ]);
    $member = User::factory()->create();
    $member->roles()->attach($role);

    $response = $this->actingAs($admin)
        ->from(route('roles.index'))
        ->delete(route('roles.destroy', $role));

    $response->assertRedirect(route('roles.index'));
    $response->assertSessionHasErrors([
        'error' => 'No es posible eliminar un rol que tiene usuarios asignados.',
    ]);
    $this->assertModelExists($role);
});

function systemUser(): User
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
