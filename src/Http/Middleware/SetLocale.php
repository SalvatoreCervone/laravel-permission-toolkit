<?php

namespace SalvatoreCervone\PermissionToolkit\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request and set the active locale.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check query parameter ?lang= or ?locale=
        $queryLocale = $request->query('lang') ?? $request->query('locale');
        if ($queryLocale && in_array(strtolower($queryLocale), ['it', 'en'])) {
            if ($request->hasSession()) {
                $request->session()->put('permission_toolkit_locale', strtolower($queryLocale));
            }
        }

        // 2. Check session
        $locale = $request->hasSession() ? $request->session()->get('permission_toolkit_locale') : null;

        // 3. Fallback to package config
        if (! $locale) {
            $locale = config('permission-toolkit.locale');
        }

        // 4. Fallback to app locale
        if (! $locale) {
            $appLocale = app()->getLocale();
            $locale = str_starts_with($appLocale, 'it') ? 'it' : (str_starts_with($appLocale, 'en') ? 'en' : 'it');
        }

        if (in_array(strtolower($locale), ['it', 'en'])) {
            app()->setLocale(strtolower($locale));
        }

        return $next($request);
    }
}
