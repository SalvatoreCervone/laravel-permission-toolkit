<?php

namespace SalvatoreCervone\PermissionToolkit;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PermissionToolkit
{
    /**
     * The callback that should be used to authenticate Permission Toolkit users.
     */
    protected static ?Closure $authUsing = null;

    /**
     * Determine if the given request can access the Permission Toolkit dashboard.
     */
    public static function check(Request $request): bool
    {
        if (static::$authUsing !== null) {
            return (bool) call_user_func(static::$authUsing, $request);
        }

        $gateName = config('permission-toolkit.gate', 'viewPermissionToolkit');

        if (Gate::has($gateName)) {
            return Gate::check($gateName);
        }

        // In local or testing environments, allow access if no gate is explicitly defined
        if (app()->environment('local', 'testing')) {
            return true;
        }

        // In production, require explicit gate definition or callback to prevent privilege escalation
        return false;
    }

    /**
     * Set the callback that should be used to authenticate Permission Toolkit users.
     */
    public static function auth(Closure $callback): void
    {
        static::$authUsing = $callback;
    }
}
