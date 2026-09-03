<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        if ($user->role !== UserRole::from($role)) {
            // Send an authenticated user to their own area rather than a dead end.
            return redirect($user->homePath());
        }

        return $next($request);
    }
}
