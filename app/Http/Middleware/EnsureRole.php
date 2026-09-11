<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Usage: ->middleware('role:admin,super_admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->user()?->role;

        abort_unless(
            $role instanceof Role && in_array($role->value, $roles, true),
            403,
            'Anda tidak memiliki akses ke halaman ini.'
        );

        return $next($request);
    }
}
