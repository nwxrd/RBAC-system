<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Modules\ModuleCatalog;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'brand' => [
                'logo' => config('brand.logo'),
            ],
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_super_admin' => $user->hasSystemRole(),
                    'roles' => $user->roles->pluck('name'),
                    'permissions' => $user->getAllPermissions(),
                ] : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'navigation' => $user instanceof User ? $this->navigation($user) : [],
        ];
    }

    /**
     * @return list<array{title: string, href: string, icon: string}>
     */
    private function navigation(User $user): array
    {
        $items = [];

        foreach (ModuleCatalog::definitions() as $module) {
            if (! $user->canOnModule($module->code(), 'can_view')) {
                continue;
            }

            $entry = $module->navigation();

            $items[] = [
                'title' => $entry['title'],
                'href' => route($entry['route']),
                'icon' => $entry['icon'],
            ];
        }

        return $items;
    }
}
