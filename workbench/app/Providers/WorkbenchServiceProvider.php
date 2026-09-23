<?php

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;
use Workbench\App\Http\Middleware\AutoLoginDemoUser;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $dbPath = __DIR__ . '/../../database/test.sqlite';
        if (! file_exists($dbPath)) {
            @mkdir(dirname($dbPath), 0755, true);
            touch($dbPath);
        }

        $this->app['config']->set('database.default', 'sqlite');
        $this->app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'url' => null,
            'database' => $dbPath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $this->app['config']->set('auth.providers.users.model', \Workbench\App\Models\User::class);
        $this->app['config']->set('permission-toolkit.user_model', \Workbench\App\Models\User::class);

        // Auto-login demo user and provide web session in workbench
        $this->app['config']->set('permission-toolkit.middleware', [
            'web',
            AutoLoginDemoUser::class,
            'auth',
        ]);
    }
}
