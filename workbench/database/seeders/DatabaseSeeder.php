<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use SalvatoreCervone\PermissionToolkit\Models\PermissionAuditLog;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Workbench\App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Create Spatie Permissions
        $permissions = [
            // Users module
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            // Invoices module
            'invoices.view',
            'invoices.create',
            'invoices.edit',
            'invoices.delete',

            // Reports module
            'reports.view',
            'reports.export',
            'reports.special-audit',

            // Settings module
            'settings.view',
            'settings.update',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // 2. Create Spatie Roles
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $manager    = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $accountant = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);
        $viewer     = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);

        // 3. Assign Permissions to Roles
        $manager->syncPermissions([
            'users.view', 'users.create', 'users.edit',
            'invoices.view', 'invoices.create', 'invoices.edit',
            'reports.view',
            'settings.view',
        ]);

        $accountant->syncPermissions([
            'invoices.view', 'invoices.create', 'invoices.edit',
            'reports.view', 'reports.export',
        ]);

        $viewer->syncPermissions([
            'users.view',
            'invoices.view',
            'reports.view',
        ]);

        // 4. Create Demo Users
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@demo.test'],
            ['name' => 'Mario Rossi (Super Admin)', 'password' => bcrypt('password')]
        );
        $adminUser->syncRoles([$superAdmin]);

        $managerUser = User::firstOrCreate(
            ['email' => 'manager@demo.test'],
            ['name' => 'Laura Bianchi (Operations Manager)', 'password' => bcrypt('password')]
        );
        $managerUser->syncRoles([$manager]);

        $accountantUser = User::firstOrCreate(
            ['email' => 'accountant@demo.test'],
            ['name' => 'Giuseppe Verdi (Senior Accountant)', 'password' => bcrypt('password')]
        );
        $accountantUser->syncRoles([$accountant]);
        // Direct permission for testing direct checks in simulator!
        $accountantUser->givePermissionTo('reports.special-audit');

        $viewerUser = User::firstOrCreate(
            ['email' => 'viewer@demo.test'],
            ['name' => 'Anna Neri (Guest Viewer)', 'password' => bcrypt('password')]
        );
        $viewerUser->syncRoles([$viewer]);

        // 5. Seed Realistic Security Audit Logs
        if (\Illuminate\Support\Facades\Schema::hasTable(config('permission-toolkit.audit.table', 'permission_audit_logs'))) {
            PermissionAuditLog::firstOrCreate([
                'action' => 'assigned',
                'type' => 'role',
                'target_name' => 'super-admin',
                'user_id' => $adminUser->id,
                'user_type' => get_class($adminUser),
            ], [
                'causer_id' => null,
                'causer_type' => null,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Seeder / Initial Setup',
                'created_at' => now()->subDays(3),
            ]);

            PermissionAuditLog::firstOrCreate([
                'action' => 'assigned',
                'type' => 'role',
                'target_name' => 'manager',
                'user_id' => $managerUser->id,
                'user_type' => get_class($managerUser),
            ], [
                'causer_id' => $adminUser->id,
                'causer_type' => get_class($adminUser),
                'ip_address' => '192.168.1.15',
                'user_agent' => 'Mozilla/5.0 (Workbench Demo)',
                'created_at' => now()->subDays(2),
            ]);

            PermissionAuditLog::firstOrCreate([
                'action' => 'assigned',
                'type' => 'permission',
                'target_name' => 'reports.special-audit',
                'user_id' => $accountantUser->id,
                'user_type' => get_class($accountantUser),
            ], [
                'causer_id' => $adminUser->id,
                'causer_type' => get_class($adminUser),
                'ip_address' => '192.168.1.15',
                'user_agent' => 'Mozilla/5.0 (Workbench Demo)',
                'created_at' => now()->subDay(),
            ]);
        }
    }
}
