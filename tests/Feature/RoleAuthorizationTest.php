<?php

use App\Models\Module;
use App\Models\Role;
use App\Models\User;

test('guests are redirected to the login page when visiting roles', function () {
    $response = $this->get(route('roles.index'));

    $response->assertRedirect(route('login'));
});

test('users without a role receive 403 when visiting roles', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('roles.index'));

    $response->assertForbidden();
});

test('users who can only view roles can open the index', function () {
    $user = userWithRolesModulePermission();

    $response = $this->actingAs($user)->get(route('roles.index'));

    $response->assertOk();
});

test('users who can only view roles receive 403 when changing roles', function (string $method, string $routeName, bool $requiresRole) {
    $user = userWithRolesModulePermission();
    $target = Role::query()->create([
        'name' => 'Target',
    ]);

    $response = $this->actingAs($user)->call(
        $method,
        route($routeName, $requiresRole ? $target : []),
    );

    $response->assertForbidden();
})->with([
    'create' => ['get', 'roles.create', false],
    'store' => ['post', 'roles.store', false],
    'edit' => ['get', 'roles.edit', true],
    'update' => ['put', 'roles.update', true],
    'destroy' => ['delete', 'roles.destroy', true],
]);

test('system roles can open the roles index without a roles module', function () {
    $user = User::factory()->create();
    $role = new Role;
    $role->forceFill([
        'name' => 'Owner',
        'is_system' => true,
    ])->save();
    $user->roles()->attach($role);

    $response = $this->actingAs($user)->get(route('roles.index'));

    $response->assertOk();
});

function userWithRolesModulePermission(): User
{
    $user = User::factory()->create();
    $role = Role::query()->create([
        'name' => 'Analyst',
    ]);
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
    $user->roles()->attach($role);

    return $user;
}
