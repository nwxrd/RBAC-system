<?php

use App\Models\Module;
use App\Models\Role;
use App\Models\User;

test('guests are redirected to the login page when visiting users', function () {
    $this->get(route('users.index'))->assertRedirect(route('login'));
});

test('users without permission receive 403 when visiting users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('users.index'))->assertForbidden();
});

test('users who can only view users can open the index and cannot create users', function () {
    $user = userWithUsersPermission(canCreate: false);

    $this->actingAs($user)->get(route('users.index'))->assertOk();

    $this->actingAs($user)->post(route('users.store'), [
        'name' => 'Ana',
        'email' => 'ana@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => [],
    ])->assertForbidden();
});

test('store creates an active verified user with the requested roles', function () {
    $actor = userWithUsersPermission(canCreate: true);
    $role = Role::query()->create(['name' => 'Analyst']);

    $this->actingAs($actor)->post(route('users.store'), [
        'name' => 'Ana',
        'email' => 'ana@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => [$role->id],
    ])->assertRedirect(route('users.index'));

    $created = User::query()->where('email', 'ana@example.com')->first();

    expect($created)->not->toBeNull()
        ->and($created->is_active)->toBeTrue()
        ->and($created->email_verified_at)->not->toBeNull()
        ->and($created->roles()->pluck('roles.id')->all())->toBe([$role->id]);
});

test('the system role cannot be assigned to another user', function () {
    $actor = userWithUsersPermission(canCreate: true, canUpdate: true);
    $system = new Role;
    $system->forceFill([
        'name' => 'Owner',
        'is_system' => true,
    ])->save();
    $holder = User::factory()->create();
    $holder->roles()->attach($system);

    $this->actingAs($actor)->post(route('users.store'), [
        'name' => 'Ana',
        'email' => 'ana@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => [$system->id],
    ])->assertSessionHasErrors('role_id');

    expect(User::query()->where('email', 'ana@example.com')->exists())->toBeFalse();

    $target = User::factory()->create(['name' => 'Original']);
    $analyst = Role::query()->create(['name' => 'Analyst']);
    $target->roles()->attach($analyst);

    $this->actingAs($actor)->put(route('users.update', $target), [
        'name' => 'Cambiado',
        'email' => $target->email,
        'is_active' => true,
        'roles' => [$system->id],
    ])->assertSessionHasErrors('role_id');

    expect($target->fresh()->name)->toBe('Original')
        ->and($target->roles()->pluck('roles.id')->all())->toBe([$analyst->id])
        ->and($holder->roles()->pluck('roles.id')->all())->toBe([$system->id]);
});

test('editing the system role holder keeps that role and saves the other changes', function () {
    $holder = User::factory()->create(['name' => 'Titular']);
    $system = new Role;
    $system->forceFill([
        'name' => 'Owner',
        'is_system' => true,
    ])->save();
    $holder->roles()->attach($system);
    $analyst = Role::query()->create(['name' => 'Analyst']);

    $this->actingAs($holder)->put(route('users.update', $holder), [
        'name' => 'Titular editado',
        'email' => $holder->email,
        'is_active' => true,
        'roles' => [$analyst->id],
    ])->assertRedirect(route('users.index'));

    $holder->refresh();

    expect($holder->name)->toBe('Titular editado')
        ->and($holder->roles()->orderBy('roles.id')->pluck('roles.id')->all())->toEqualCanonicalizing([$system->id, $analyst->id]);
});

test('updating the system role holder without assignable roles keeps that role', function () {
    $holder = User::factory()->create(['name' => 'Titular']);
    $system = new Role;
    $system->forceFill([
        'name' => 'Owner',
        'is_system' => true,
    ])->save();
    $holder->roles()->attach($system);

    $this->actingAs($holder)->put(route('users.update', $holder), [
        'name' => 'Titular editado',
        'email' => $holder->email,
        'is_active' => '1',
        'roles' => [],
    ])->assertRedirect(route('users.index'));

    $holder->refresh();

    expect($holder->name)->toBe('Titular editado')
        ->and($holder->is_active)->toBeTrue()
        ->and($holder->roles()->pluck('roles.id')->all())->toBe([$system->id]);
});

test('destroy deactivates a user without deleting the row', function () {
    $actor = userWithUsersPermission(canDelete: true);
    $target = User::factory()->create();

    $this->actingAs($actor)->delete(route('users.destroy', $target))
        ->assertRedirect(route('users.index'));

    expect($target->fresh()->is_active)->toBeFalse()
        ->and(User::query()->whereKey($target->id)->exists())->toBeTrue();
});

test('destroy and update do not deactivate the system role holder or the authenticated user', function () {
    $actor = userWithUsersPermission(canUpdate: true, canDelete: true);
    $holder = User::factory()->create(['name' => 'Titular']);
    $system = new Role;
    $system->forceFill([
        'name' => 'Owner',
        'is_system' => true,
    ])->save();
    $holder->roles()->attach($system);
    $analyst = Role::query()->create(['name' => 'Analyst']);

    $this->actingAs($actor)->delete(route('users.destroy', $holder))
        ->assertSessionHasErrors('error');

    expect($holder->fresh()->is_active)->toBeTrue();

    $this->actingAs($actor)->put(route('users.update', $holder), [
        'name' => 'Otro nombre',
        'email' => 'otro@example.com',
        'is_active' => false,
        'roles' => [$analyst->id],
    ])->assertSessionHasErrors('error');

    $holder->refresh();

    expect($holder->is_active)->toBeTrue()
        ->and($holder->name)->toBe('Titular')
        ->and($holder->email)->not->toBe('otro@example.com');

    $this->actingAs($actor)->delete(route('users.destroy', $actor))
        ->assertSessionHasErrors('error');

    expect($actor->fresh()->is_active)->toBeTrue();

    $this->actingAs($actor)->put(route('users.update', $actor), [
        'name' => 'Actor editado',
        'email' => 'actor-editado@example.com',
        'is_active' => false,
        'roles' => [$actor->roles()->first()->id],
    ])->assertSessionHasErrors('error');

    $actor->refresh();

    expect($actor->is_active)->toBeTrue()
        ->and($actor->name)->not->toBe('Actor editado');
});

test('an inactive account cannot log in and an active account can', function () {
    $inactive = User::factory()->create(['is_active' => false]);

    $this->post(route('login.store'), [
        'email' => $inactive->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();

    $active = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $active->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($active);
});

test('an open session is closed when the user is inactive', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('public registration is not available', function () {
    $this->get('/register')->assertNotFound();
});

function userWithUsersPermission(bool $canCreate = false, bool $canUpdate = false, bool $canDelete = false): User
{
    $user = User::factory()->create();
    $role = Role::query()->create([
        'name' => 'Users '.fake()->unique()->word(),
    ]);
    $module = Module::query()->firstOrCreate(
        ['code' => 'users'],
        ['name' => 'Usuarios'],
    );

    $role->modules()->attach($module->id, [
        'can_view' => true,
        'can_create' => $canCreate,
        'can_update' => $canUpdate,
        'can_delete' => $canDelete,
    ]);
    $user->roles()->attach($role);

    return $user;
}
