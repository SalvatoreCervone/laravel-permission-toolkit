<?php

namespace SalvatoreCervone\PermissionToolkit;

use Illuminate\Support\ServiceProvider;
use SalvatoreCervone\PermissionToolkit\Commands\DoctorPermissionCommand;
use SalvatoreCervone\PermissionToolkit\Commands\ExportPermissionsCommand;
use SalvatoreCervone\PermissionToolkit\Commands\ImportPermissionsCommand;
use SalvatoreCervone\PermissionToolkit\Commands\PruneAuditLogsCommand;
use SalvatoreCervone\PermissionToolkit\Commands\SimulatePermissionCommand;
use SalvatoreCervone\PermissionToolkit\Services\AuthorizationSimulator;
use SalvatoreCervone\PermissionToolkit\Services\IntegrityChecker;

class PermissionToolkitServiceProvider extends ServiceProvider
{
    /**
     * Register services in the container.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/permission-toolkit.php', 'permission-toolkit');

        $this->app->singleton(AuthorizationSimulator::class, function () {
            return new AuthorizationSimulator();
        });

        $this->app->singleton(IntegrityChecker::class, function () {
            return new IntegrityChecker();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'permission-toolkit');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'permission-toolkit');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        if ($this->app->runningInConsole()) {
            // Publish Config
            $this->publishes([
                __DIR__ . '/../config/permission-toolkit.php' => config_path('permission-toolkit.php'),
            ], 'permission-toolkit-config');

            // Publish Translations
            $langPath = method_exists($this->app, 'langPath')
                ? $this->app->langPath('vendor/permission-toolkit')
                : resource_path('lang/vendor/permission-toolkit');

            $this->publishes([
                __DIR__ . '/../resources/lang' => $langPath,
            ], 'permission-toolkit-translations');

            // Publish Views
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/permission-toolkit'),
            ], 'permission-toolkit-views');

            // Publish Migrations
            if (! class_exists('CreatePermissionAuditLogsTable')) {
                $this->publishes([
                    __DIR__ . '/../database/migrations/create_permission_audit_logs_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_permission_audit_logs_table.php'),
                ], 'permission-toolkit-migrations');
            }

            // Register Commands
            $this->commands([
                SimulatePermissionCommand::class,
                DoctorPermissionCommand::class,
                ExportPermissionsCommand::class,
                ImportPermissionsCommand::class,
                PruneAuditLogsCommand::class,
            ]);
        }
    }
}
