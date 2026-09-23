<?php

namespace SalvatoreCervone\PermissionToolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ImportPermissionsCommand extends Command
{
    protected $signature = 'permission:import
                            {--file= : Source file path (defaults to storage/app/permissions_export.json)}
                            {--fresh : Clear existing roles and permissions before importing}';

    protected $description = 'Import Spatie roles, permissions, and their associations from a JSON artifact';

    public function handle(PermissionRegistrar $registrar): int
    {
        $filePath = $this->option('file') ?: storage_path('app/permissions_export.json');

        if (! file_exists($filePath)) {
            $this->error("Import file not found: [{$filePath}]");
            return Command::FAILURE;
        }

        $content = file_get_contents($filePath);
        $data = json_decode($content, true);

        if (! is_array($data) || ! isset($data['permissions'], $data['roles'])) {
            $this->error("Invalid file structure. Missing 'permissions' or 'roles' keys.");
            return Command::FAILURE;
        }

        DB::transaction(function () use ($data, $registrar) {
            if ($this->option('fresh')) {
                $this->warn("Clearing existing role and permission assignments...");
                Permission::query()->delete();
                Role::query()->delete();
            }

            // 1. Sync permissions
            foreach ($data['permissions'] as $permData) {
                Permission::firstOrCreate([
                    'name' => $permData['name'],
                    'guard_name' => $permData['guard_name'] ?? 'web',
                ]);
            }

            // 2. Sync roles and attach permissions
            foreach ($data['roles'] as $roleData) {
                $role = Role::firstOrCreate([
                    'name' => $roleData['name'],
                    'guard_name' => $roleData['guard_name'] ?? 'web',
                ]);

                if (! empty($roleData['permissions'])) {
                    $role->syncPermissions($roleData['permissions']);
                }
            }

            // Invalidate Spatie cache
            $registrar->forgetCachedPermissions();
        });

        $this->info("✔ Successfully imported " . count($data['roles']) . " roles and " . count($data['permissions']) . " permissions.");
        $this->info("✔ Spatie cache cleared automatically.");

        return Command::SUCCESS;
    }
}
