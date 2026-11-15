<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks a route unless the logged-in user has the given role.
 * Usage in routes:  ->middleware('role:admin')  (admins + super admins)
 *                   ->middleware('role:superadmin')  (super admins only)
 *                   ->middleware('role:member')  (students + faculty: the people who claim items)
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        $allowed = $user && match ($role) {
            'admin' => $user->isAdmin(),
            'superadmin' => $user->isSuperAdmin(),
            'member' => $user->isMember(),
            default => $user->role === $role,
        };

        if (! $allowed) {
            abort(403, match ($role) {
                'admin' => 'Only admins can access this page.',
                'superadmin' => 'Only a Super Admin can manage accounts.',
                default => 'This action is only for student and faculty accounts.',
            });
        }

        return $next($request);
    }
}
