<?php

namespace SalvatoreCervone\PermissionToolkit\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ExportPermissionsCommand extends Command
{
    protected $signature = 'permission:export
                            {--file= : Destination file path (defaults to storage/app/permissions_export.json)}';

    protected $description = 'Export all Spatie roles, permissions, and their associations to a JSON artifact';

    public function handle(): int
    {
        $filePath = $this->option('file') ?: storage_path('app/permissions_export.json');

        $permissions = Permission::all(['name', 'guard_name'])->toArray();
        $roles = Role::with('permissions:name,guard_name')->get()->map(function ($role) {
            return [
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions->pluck('name')->toArray(),
            ];
        })->toArray();

        $payload = [
            'exported_at' => now()->toIso8601String(),
            'version' => '1.0',
            'permissions' => $permissions,
            'roles' => $roles,
        ];

        $directory = dirname($filePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($filePath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info("✔ Successfully exported " . count($roles) . " roles and " . count($permissions) . " permissions to:");
        $this->line("  [{$filePath}]");

        return Command::SUCCESS;
    }
}
