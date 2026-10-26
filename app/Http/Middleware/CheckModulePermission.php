<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModulePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $moduleCode, string $action): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->canOnModule($moduleCode, $action)) {
            abort(403);
        }

        return $next($request);
    }
}
