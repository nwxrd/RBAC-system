<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        $roles = Role::withCount('users')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('roles/index', [
            'roles' => $roles,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        $modules = Module::query()->where('is_active', true)->orderBy('name', 'asc')->get();

        return Inertia::render('roles/create', [
            'modules' => $modules,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:500',
            'permissions' => 'required|array',
            'permissions.*.module_id' => 'required|exists:modules,id',
            'permissions.*.can_view' => 'boolean',
            'permissions.*.can_create' => 'boolean',
            'permissions.*.can_update' => 'boolean',
            'permissions.*.can_delete' => 'boolean',
        ]);

        DB::transaction(function () use ($validated) {
            $role = Role::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            $this->syncModulePermissions($role, $validated['permissions']);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rol creado exitosamente.']);

        return redirect()->route('roles.index');
    }

    public function edit(Role $role): Response|RedirectResponse
    {
        if ($redirect = $this->rejectSystemRole($role)) {
            return $redirect;
        }

        $role->load('modules');
        $modules = Module::query()->where('is_active', true)->orderBy('name', 'asc')->get();

        return Inertia::render('roles/edit', [
            'role' => $role,
            'modules' => $modules,
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        if ($redirect = $this->rejectSystemRole($role)) {
            return $redirect;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,'.$role->id,
            'description' => 'nullable|string|max:500',
            'permissions' => 'required|array',
            'permissions.*.module_id' => 'required|exists:modules,id',
            'permissions.*.can_view' => 'boolean',
            'permissions.*.can_create' => 'boolean',
            'permissions.*.can_update' => 'boolean',
            'permissions.*.can_delete' => 'boolean',
        ]);

        DB::transaction(function () use ($role, $validated) {
            $role->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            $this->syncModulePermissions($role, $validated['permissions']);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rol actualizado exitosamente.']);

        return redirect()->route('roles.index');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($redirect = $this->rejectSystemRole($role)) {
            return $redirect;
        }

        if ($role->users()->count() > 0) {
            return redirect()->back()->withErrors([
                'error' => 'No es posible eliminar un rol que tiene usuarios asignados.',
            ]);
        }

        DB::transaction(function () use ($role) {
            $role->modules()->detach();
            $role->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rol eliminado exitosamente.']);

        return redirect()->route('roles.index');
    }

    private function rejectSystemRole(Role $role): ?RedirectResponse
    {
        if (! $role->is_system) {
            return null;
        }

        return redirect()->back()->withErrors([
            'error' => 'No es posible modificar un rol de sistema.',
        ]);
    }

    /**
     * @param  array<int, array{module_id: int, can_view?: bool, can_create?: bool, can_update?: bool, can_delete?: bool}>  $permissions
     */
    private function syncModulePermissions(Role $role, array $permissions): void
    {
        $sync = [];

        foreach ($permissions as $permission) {
            if (empty($permission['can_view'])) {
                continue;
            }

            $sync[$permission['module_id']] = [
                'can_view' => true,
                'can_create' => (bool) ($permission['can_create'] ?? false),
                'can_update' => (bool) ($permission['can_update'] ?? false),
                'can_delete' => (bool) ($permission['can_delete'] ?? false),
            ];
        }

        $preserved = $role->modules()
            ->where('modules.is_active', false)
            ->get();

        foreach ($preserved as $module) {
            $sync[$module->id] = [
                'can_view' => (bool) (int) $module->pivot->can_view,
                'can_create' => (bool) (int) $module->pivot->can_create,
                'can_update' => (bool) (int) $module->pivot->can_update,
                'can_delete' => (bool) (int) $module->pivot->can_delete,
            ];
        }

        $role->modules()->sync($sync);
    }
}
