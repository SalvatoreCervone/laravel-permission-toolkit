<?php

namespace SalvatoreCervone\PermissionToolkit\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use SalvatoreCervone\PermissionToolkit\PermissionToolkit;
use Symfony\Component\HttpFoundation\Response;

class Authorize
{
    /**
     * Handle the incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        return PermissionToolkit::check($request)
            ? $next($request)
            : abort(403, __('permission-toolkit::messages.unauthorized_access') ?: 'Unauthorized action.');
    }
}
