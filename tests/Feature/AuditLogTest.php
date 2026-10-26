<?php

use App\Models\AuditLog;
use App\Models\Module;
use App\Models\Role;
use App\Models\RoleModule;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\ModuleSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;

test('changing an email keeps the previous value and the row authors', function () {
    $actor = User::factory()->create();
    $subject = User::factory()->create([
        'email' => 'old@example.com',
    ]);

    $this->actingAs($actor);

    $subject->update([
        'email' => 'new@example.com',
    ]);

    $subject->refresh();
    $log = AuditLog::query()->where('subject_type', User::class)->where('action', 'updated')->first();

    expect($subject->updated_by)->toBe($actor->id)
        ->and($subject->created_by)->toBeNull()
        ->and($log)->not->toBeNull()
        ->and($log->actor_id)->toBe($actor->id)
        ->and($log->properties['email']['old'])->toBe('old@example.com')
        ->and($log->properties['email']['new'])->toBe('new@example.com')
        ->and($log->properties)->not->toHaveKey('updated_by');
});

test('changing a password records the change without the secret', function () {
    $actor = User::factory()->create();
    $subject = User::factory()->create();
    $plain = 'secret-password';

    $this->actingAs($actor);

    $subject->update([
        'password' => $plain,
    ]);

    $log = AuditLog::query()->where('subject_id', (string) $subject->id)->where('action', 'updated')->first();
    $encoded = json_encode($log->properties);

    expect($log->properties['password'])->toBe(['changed' => true])
        ->and($encoded)->not->toContain($plain)
        ->and($encoded)->not->toContain($subject->fresh()->password);
});

test('login and logout each write one history row', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    expect(AuditLog::query()->where('action', 'login')->where('actor_id', $user->id)->count())->toBe(1);

    $this->post(route('logout'));

    expect(AuditLog::query()->where('action', 'logout')->where('actor_id', $user->id)->count())->toBe(1);
});

test('assigning a role and saving permissions write history', function () {
    $actor = User::factory()->create();
    $target = User::factory()->create(['name' => 'Ana']);
    $role = Role::query()->create(['name' => 'Analista']);
    $module = Module::query()->create([
        'code' => 'roles',
        'name' => 'Roles',
        'is_active' => true,
    ]);

    $this->actingAs($actor);

    $target->roles()->attach($role->id);

    $assignment = AuditLog::query()->where('subject_type', UserRole::class)->first();

    expect($assignment)->not->toBeNull()
        ->and($assignment->action)->toBe('created')
        ->and($assignment->subject_label)->toBe('Rol Analista en Ana');

    $role->modules()->sync([
        $module->id => [
            'can_view' => true,
            'can_create' => false,
            'can_update' => false,
            'can_delete' => false,
        ],
    ]);

    $permission = AuditLog::query()->where('subject_type', RoleModule::class)->first();

    expect($permission)->not->toBeNull()
        ->and($permission->action)->toBe('created')
        ->and($permission->subject_label)->toBe('Permisos de Analista sobre Roles')
        ->and((bool) $permission->properties['can_view']['new'])->toBeTrue();
});

test('a rolled back save does not keep history', function () {
    $actor = User::factory()->create();
    $subject = User::factory()->create();

    $this->actingAs($actor);

    expect(function () use ($subject): void {
        DB::transaction(function () use ($subject): void {
            $subject->update([
                'email' => 'rolled@example.com',
            ]);

            throw new RuntimeException('stop');
        });
    })->toThrow(RuntimeException::class);

    expect(AuditLog::query()->count())->toBe(0)
        ->and($subject->fresh()->email)->not->toBe('rolled@example.com');
});

test('the module seeder does not write history without a session', function () {
    $this->seed(ModuleSeeder::class);

    expect(AuditLog::query()->count())->toBe(0);
});

test('guests are sent to login and users without permission receive 403', function () {
    $this->get(route('audit.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get(route('audit.index'))
        ->assertForbidden();
});

test('the system role holder can open the audit index without an audit module row', function () {
    $user = User::factory()->create();
    $role = new Role;
    $role->forceFill([
        'name' => 'Owner',
        'is_system' => true,
    ])->save();
    $user->roles()->attach($role);

    $subject = User::factory()->create(['email' => 'old@example.com']);

    $this->actingAs($user);

    $subject->update(['email' => 'new@example.com']);

    $this->get(route('audit.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('audit/index')
            ->where('logs.data.0.action_label', 'Modificó')
            ->where('logs.data.0.actor_name', $user->name)
        );
});

test('recording a fact does not record the history row itself', function () {
    $actor = User::factory()->create();
    $subject = User::factory()->create();

    $this->actingAs($actor);

    $subject->update(['email' => 'other@example.com']);

    expect(AuditLog::query()->where('subject_type', AuditLog::class)->count())->toBe(0)
        ->and(AuditLog::query()->where('subject_type', User::class)->where('action', 'updated')->count())->toBe(1);
});

test('updating a module is recorded without naming that model in the recorder', function () {
    $actor = User::factory()->create();
    $module = Module::query()->create([
        'code' => 'pedidos',
        'name' => 'Pedidos',
        'description' => 'Tablero',
        'is_active' => true,
    ]);

    $this->actingAs($actor);

    $module->update(['name' => 'Pedidos norte']);

    $log = AuditLog::query()->where('subject_type', Module::class)->where('action', 'updated')->first();

    expect($log)->not->toBeNull()
        ->and($log->properties['name']['old'])->toBe('Pedidos')
        ->and($log->properties['name']['new'])->toBe('Pedidos norte');
});

test('saving a user without a business change does not write history', function () {
    $actor = User::factory()->create();
    $subject = User::factory()->create(['name' => 'Ana']);

    $this->actingAs($actor);

    $subject->update(['name' => 'Ana']);

    expect(AuditLog::query()->count())->toBe(0);
});
