<?php

namespace SalvatoreCervone\PermissionToolkit\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use SalvatoreCervone\PermissionToolkit\PermissionToolkitServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app)
    {
        return [
            PermissionServiceProvider::class,
            PermissionToolkitServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('cache.default', 'array');
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('permission-toolkit.user_model', User::class);
    }

    protected function setUpDatabase()
    {
        // 1. Create Users table
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->timestamps();
        });

        // 2. Run Spatie Permission migration
        $spatieMigration = require __DIR__ . '/../vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub';
        $spatieMigration->up();

        // 3. Run Audit Log migration
        $auditMigration = require __DIR__ . '/../database/migrations/create_permission_audit_logs_table.php.stub';
        $auditMigration->up();
    }
}
