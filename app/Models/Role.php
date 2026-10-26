<?php

namespace App\Models;

use App\Traits\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasAuditColumns;

    protected $fillable = [
        'name',
        'description',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->withPivot('created_by')
            ->using(UserRole::class);
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'role_modules')
            ->withPivot(['can_view', 'can_create', 'can_update', 'can_delete', 'created_by', 'updated_by'])
            ->withTimestamps()
            ->using(RoleModule::class);
    }

    public function fields(): BelongsToMany
    {
        return $this->belongsToMany(ModuleField::class, 'role_fields')
            ->withPivot(['can_view', 'can_edit', 'created_by', 'updated_by'])
            ->withTimestamps();
    }
}
