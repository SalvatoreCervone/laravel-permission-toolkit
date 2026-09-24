<?php

namespace SalvatoreCervone\PermissionToolkit\Services;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class IntegrityChecker
{
    /**
     * Run all health and consistency checks on the Spatie database.
     */
    public function diagnose(): array
    {
        return [
            'orphaned_pivot_records' => $this->checkOrphanedPivots(),
            'unused_permissions' => $this->checkUnusedPermissions(),
            'empty_roles' => $this->checkEmptyRoles(),
            'guard_mismatches' => $this->checkGuardMismatches(),
            'naming_inconsistencies' => $this->checkNamingInconsistencies(),
            'summary' => [
                'total_roles' => Role::count(),
                'total_permissions' => Permission::count(),
                'status' => 'OK',
            ],
        ];
    }

    /**
     * Check for pivot table records pointing to deleted roles or permissions.
     */
    protected function checkOrphanedPivots(): array
    {
        $tableNames = config('permission.table_names');
        $orphans = [];

        if (isset($tableNames['role_has_permissions'])) {
            $invalidRolePerms = DB::table($tableNames['role_has_permissions'])
                ->leftJoin($tableNames['permissions'], "{$tableNames['role_has_permissions']}.permission_id", '=', "{$tableNames['permissions']}.id")
                ->whereNull("{$tableNames['permissions']}.id")
                ->count();

            if ($invalidRolePerms > 0) {
                $orphans[] = [
                    'table' => $tableNames['role_has_permissions'],
                    'issue' => __('permission-toolkit::messages.doctor_issue_orphaned', ['count' => $invalidRolePerms]),
                ];
            }
        }

        return $orphans;
    }

    /**
     * Find permissions that are neither assigned to any role nor to any model directly.
     */
    protected function checkUnusedPermissions(): array
    {
        $tableNames = config('permission.table_names');
        $unused = [];

        $roleHasPerms = $tableNames['role_has_permissions'] ?? 'role_has_permissions';
        $modelHasPerms = $tableNames['model_has_permissions'] ?? 'model_has_permissions';

        $permissions = Permission::all();

        foreach ($permissions as $permission) {
            $assignedToRole = DB::table($roleHasPerms)->where('permission_id', $permission->id)->exists();
            $assignedToModel = DB::table($modelHasPerms)->where('permission_id', $permission->id)->exists();

            if (! $assignedToRole && ! $assignedToModel) {
                $unused[] = [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'guard_name' => $permission->guard_name,
                ];
            }
        }

        return $unused;
    }

    /**
     * Find roles that have 0 permissions and 0 users assigned.
     */
    protected function checkEmptyRoles(): array
    {
        $tableNames = config('permission.table_names');
        $empty = [];

        $roleHasPerms = $tableNames['role_has_permissions'] ?? 'role_has_permissions';
        $modelHasRoles = $tableNames['model_has_roles'] ?? 'model_has_roles';

        $roles = Role::all();

        foreach ($roles as $role) {
            $hasPermissions = DB::table($roleHasPerms)->where('role_id', $role->id)->exists();
            $hasUsers = DB::table($modelHasRoles)->where('role_id', $role->id)->exists();

            if (! $hasPermissions && ! $hasUsers) {
                $empty[] = [
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                ];
            }
        }

        return $empty;
    }

    /**
     * Detect guard mismatches between roles and attached permissions.
     */
    protected function checkGuardMismatches(): array
    {
        $tableNames = config('permission.table_names');
        $mismatches = [];

        $roleHasPerms = $tableNames['role_has_permissions'] ?? 'role_has_permissions';
        $rolesTable = $tableNames['roles'] ?? 'roles';
        $permsTable = $tableNames['permissions'] ?? 'permissions';

        $records = DB::table($roleHasPerms)
            ->join($rolesTable, "{$roleHasPerms}.role_id", '=', "{$rolesTable}.id")
            ->join($permsTable, "{$roleHasPerms}.permission_id", '=', "{$permsTable}.id")
            ->whereColumn("{$rolesTable}.guard_name", '!=', "{$permsTable}.guard_name")
            ->select(
                "{$rolesTable}.name as role_name",
                "{$rolesTable}.guard_name as role_guard",
                "{$permsTable}.name as permission_name",
                "{$permsTable}.guard_name as permission_guard"
            )
            ->get();

        foreach ($records as $record) {
            $mismatches[] = [
                'role' => "{$record->role_name} ({$record->role_guard})",
                'permission' => "{$record->permission_name} ({$record->permission_guard})",
                'issue' => __('permission-toolkit::messages.doctor_issue_guard_mismatch'),
            ];
        }

        return $mismatches;
    }

    /**
     * Check for potential naming convention clashes (e.g. mix of dots and dashes).
     */
    protected function checkNamingInconsistencies(): array
    {
        $permissions = Permission::pluck('name')->toArray();
        $hasDots = false;
        $hasDashes = false;
        $hasColons = false;

        foreach ($permissions as $name) {
            if (str_contains($name, '.')) $hasDots = true;
            if (str_contains($name, '-')) $hasDashes = true;
            if (str_contains($name, ':')) $hasColons = true;
        }

        $styles = array_filter([
            'dot notation (e.g. users.create)' => $hasDots,
            'kebab-case (e.g. users-create)' => $hasDashes,
            'colon notation (e.g. users:create)' => $hasColons,
        ]);

        if (count($styles) > 1) {
            return [
                'warning' => __('permission-toolkit::messages.doctor_warning_naming'),
                'detected_styles' => array_keys($styles),
                'recommendation' => __('permission-toolkit::messages.doctor_rec_naming'),
            ];
        }

        return [];
    }
}
