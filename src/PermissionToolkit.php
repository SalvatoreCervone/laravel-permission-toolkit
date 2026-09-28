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

    /**
     * Get the configured column(s) used to display the user name/identifier.
     *
     * @return array<int, string>
     */
    public static function getUserDisplayColumns(): array
    {
        $columns = config('permission-toolkit.users.display_columns');

        if (empty($columns)) {
            return ['name'];
        }

        if (is_string($columns)) {
            $columns = array_map('trim', explode(',', $columns));
        }

        $filtered = array_values(array_filter((array) $columns));

        return ! empty($filtered) ? $filtered : ['name'];
    }

    /**
     * Get the formatted display name for a given user model instance.
     */
    public static function getUserDisplayName(mixed $user): string
    {
        if (! $user) {
            return '';
        }

        $columns = static::getUserDisplayColumns();
        $parts = [];

        foreach ($columns as $column) {
            if (is_string($column)) {
                $val = trim((string) ($user->{$column} ?? ''));
                if ($val !== '') {
                    $parts[] = $val;
                }
            }
        }

        if (! empty($parts)) {
            $separator = (string) config('permission-toolkit.users.display_separator', ' ');
            return implode($separator, $parts);
        }

        // Fallbacks
        foreach (['name', 'email', 'username'] as $fallback) {
            $val = trim((string) ($user->{$fallback} ?? ''));
            if ($val !== '') {
                return $val;
            }
        }

        $key = method_exists($user, 'getKey') ? $user->getKey() : ($user->id ?? '');

        return __('permission-toolkit::messages.users_th_user') . ($key !== '' ? " #{$key}" : '');
    }
}
