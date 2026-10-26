<?php

namespace App\Listeners;

use App\Models\AuditLog;
use App\Models\Module;
use App\Models\Role;
use App\Models\RoleModule;
use App\Models\User;
use App\Models\UserRole;
use App\Traits\HasAuditColumns;
use DateTimeInterface;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;

final class RecordAudit
{
    private static bool $recording = false;

    /**
     * @var list<string>
     */
    private const IGNORED = [
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'user_id',
        'role_id',
        'module_id',
        'field_id',
    ];

    /**
     * @var list<string>
     */
    private const SECRETS = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public static function register(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            self::auth('login', $event->user);
        });

        Event::listen(Logout::class, function (Logout $event): void {
            self::auth('logout', $event->user);
        });

        foreach (['created', 'updated', 'deleted'] as $action) {
            Event::listen('eloquent.'.$action.': *', function (string $event, array $payload) use ($action): void {
                $model = $payload[0] ?? null;

                if (! $model instanceof Model || ! self::usesAuditColumns($model)) {
                    return;
                }

                self::recordModel($model, $action);
            });
        }

        Event::listen('eloquent.created: '.UserRole::class, function (UserRole $pivot): void {
            self::recordModel($pivot, 'created');
        });

        Event::listen('eloquent.deleted: '.UserRole::class, function (UserRole $pivot): void {
            self::recordModel($pivot, 'deleted');
        });

        Event::listen('eloquent.created: '.RoleModule::class, function (RoleModule $pivot): void {
            self::recordModel($pivot, 'created');
        });

        Event::listen('eloquent.updated: '.RoleModule::class, function (RoleModule $pivot): void {
            self::recordModel($pivot, 'updated');
        });

        Event::listen('eloquent.deleted: '.RoleModule::class, function (RoleModule $pivot): void {
            self::recordModel($pivot, 'deleted');
        });
    }

    private static function auth(string $action, mixed $user): void
    {
        if (! $user instanceof User) {
            return;
        }

        self::write($user, $action, $user, self::subjectId($user), self::label($user), null);
    }

    private static function recordModel(Model $model, string $action): void
    {
        $actor = Auth::user();

        if (! $actor instanceof User) {
            return;
        }

        $properties = self::properties($model, $action);

        if ($action === 'updated' && $properties === null) {
            return;
        }

        self::write($actor, $action, $model, self::subjectId($model), self::label($model), $properties);
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function properties(Model $model, string $action): ?array
    {
        if ($action === 'updated') {
            $properties = self::diff($model->getChanges(), $model->getPrevious());

            return $properties === [] ? null : $properties;
        }

        $properties = [];

        foreach ($model->getAttributes() as $key => $value) {
            if (self::skip($key, $model)) {
                continue;
            }

            if (in_array($key, self::SECRETS, true)) {
                if ($value !== null && $value !== '') {
                    $properties[$key] = ['changed' => true];
                }

                continue;
            }

            $properties[$key] = $action === 'created'
                ? ['old' => null, 'new' => self::scalar($value)]
                : ['old' => self::scalar($value), 'new' => null];
        }

        return $properties;
    }

    /**
     * @param  array<string, mixed>  $next
     * @param  array<string, mixed>  $previous
     * @return array<string, mixed>
     */
    private static function diff(array $next, array $previous): array
    {
        $properties = [];

        foreach ($next as $key => $value) {
            if (in_array($key, self::IGNORED, true)) {
                continue;
            }

            if (in_array($key, self::SECRETS, true)) {
                $properties[$key] = ['changed' => true];

                continue;
            }

            $properties[$key] = [
                'old' => self::scalar($previous[$key] ?? null),
                'new' => self::scalar($value),
            ];
        }

        return $properties;
    }

    private static function skip(string $key, Model $model): bool
    {
        return in_array($key, self::IGNORED, true) || $key === $model->getKeyName();
    }

    private static function usesAuditColumns(Model $model): bool
    {
        return in_array(HasAuditColumns::class, class_uses_recursive($model), true);
    }

    private static function subjectId(Model $model): ?string
    {
        if ($model instanceof UserRole) {
            return $model->user_id.':'.$model->role_id;
        }

        if ($model instanceof RoleModule) {
            return $model->role_id.':'.$model->module_id;
        }

        $key = $model->getKey();

        return $key === null ? null : (string) $key;
    }

    private static function label(Model $model): string
    {
        if ($model instanceof User) {
            return $model->name.' ('.$model->email.')';
        }

        if ($model instanceof UserRole) {
            $role = Role::query()->whereKey($model->role_id)->value('name') ?? 'rol';
            $user = User::query()->whereKey($model->user_id)->value('name') ?? 'usuario';

            return 'Rol '.$role.' en '.$user;
        }

        if ($model instanceof RoleModule) {
            $role = Role::query()->whereKey($model->role_id)->value('name') ?? 'rol';
            $module = Module::query()->whereKey($model->module_id)->value('name') ?? 'módulo';

            return 'Permisos de '.$role.' sobre '.$module;
        }

        foreach (['name', 'title', 'email', 'code'] as $attribute) {
            $value = $model->getAttribute($attribute);

            if (filled($value)) {
                return (string) $value;
            }
        }

        return class_basename($model).' #'.$model->getKey();
    }

    /**
     * @param  array<string, mixed>|null  $properties
     */
    private static function write(User $actor, string $action, Model $subject, ?string $subjectId, string $label, ?array $properties): void
    {
        if (self::$recording) {
            return;
        }

        self::$recording = true;

        try {
            AuditLog::query()->create([
                'actor_id' => $actor->id,
                'actor_name' => $actor->name,
                'action' => $action,
                'subject_type' => $subject::class,
                'subject_id' => $subjectId,
                'subject_label' => $label,
                'properties' => $properties,
                'ip_address' => self::ip(),
            ]);
        } finally {
            self::$recording = false;
        }
    }

    private static function ip(): ?string
    {
        if (! app()->bound('request')) {
            return null;
        }

        return request()->ip();
    }

    private static function scalar(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        if (is_bool($value) || is_int($value) || is_float($value) || is_string($value) || $value === null) {
            return $value;
        }

        return (string) $value;
    }
}
