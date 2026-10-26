<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->with('roles:id,name,is_system')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('users/index', [
            'users' => $users,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('users/create', [
            'roles' => $this->assignableRoles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
            'roles' => ['required', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        DB::transaction(function () use ($validated) {
            $user = new User;
            $user->forceFill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'is_active' => true,
                'email_verified_at' => now(),
            ])->save();

            $user->roles()->sync($validated['roles']);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario creado exitosamente.']);

        return redirect()->route('users.index');
    }

    public function edit(User $user): Response
    {
        $user->load('roles:id,name,is_system');

        return Inertia::render('users/edit', [
            'user' => $user,
            'roles' => $this->assignableRoles(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', Password::default(), 'confirmed'],
            'is_active' => ['required', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        $isActive = $request->boolean('is_active');

        if (! $isActive && $this->cannotDeactivate($request, $user)) {
            return redirect()->back()->withErrors([
                'error' => 'No es posible desactivar esta cuenta.',
            ]);
        }

        DB::transaction(function () use ($user, $validated, $isActive) {
            $attributes = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'is_active' => $isActive,
            ];

            if (! empty($validated['password'])) {
                $attributes['password'] = $validated['password'];
            }

            $user->update($attributes);
            $user->roles()->sync($this->roleIdsForSync($user, $validated['roles'] ?? []));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario actualizado exitosamente.']);

        return redirect()->route('users.index');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($this->cannotDeactivate($request, $user)) {
            return redirect()->back()->withErrors([
                'error' => 'No es posible desactivar esta cuenta.',
            ]);
        }

        $user->update(['is_active' => false]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario desactivado exitosamente.']);

        return redirect()->route('users.index');
    }

    /**
     * @return Collection<int, Role>
     */
    private function assignableRoles()
    {
        return Role::query()
            ->where('is_system', false)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @param  array<int, int>  $roleIds
     * @return array<int, int>
     */
    private function roleIdsForSync(User $user, array $roleIds): array
    {
        $systemRoleId = $user->roles()->where('is_system', true)->value('id');

        if ($systemRoleId !== null) {
            $roleIds[] = (int) $systemRoleId;
        }

        return array_values(array_unique(array_map(intval(...), $roleIds)));
    }

    private function cannotDeactivate(Request $request, User $user): bool
    {
        return $user->hasSystemRole() || $request->user()?->is($user);
    }
}
