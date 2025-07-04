<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            abort(401, 'Unauthorized');
        }

        $userRole = $request->user()->role ?? 'passenger';

        if (!in_array($userRole, $roles)) {
            abort(403, 'Access denied. Required roles: ' . implode(', ', $roles));
        }

        return $next($request);
    }
}
