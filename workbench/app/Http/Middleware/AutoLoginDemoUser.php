<?php

namespace Workbench\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Workbench\App\Models\User;

class AutoLoginDemoUser
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check()) {
            try {
                $user = User::first();
                if ($user) {
                    Auth::login($user);
                }
            } catch (\Throwable) {
                // Ignore if DB not yet initialized
            }
        }

        return $next($request);
    }
}
