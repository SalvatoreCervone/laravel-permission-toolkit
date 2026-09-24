<?php

namespace SalvatoreCervone\PermissionToolkit\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SalvatoreCervone\PermissionToolkit\Services\AuditLogger;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class MatrixController extends Controller
{
    /**
     * Display the Role-Permission pivot matrix.
     */
    public function index(Request $request)
    {
        $roles = Role::with('permissions')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();

        $separator = config('permission-toolkit.matrix.group_separator', '.');

        // Group permissions by prefix (e.g. "users.create" -> "users")
        $groupedPermissions = $permissions->groupBy(function ($permission) use ($separator) {
            if (str_contains($permission->name, $separator)) {
                return explode($separator, $permission->name)[0];
            }
            if (str_contains($permission->name, ':')) {
                return explode(':', $permission->name)[0];
            }
            if (str_contains($permission->name, '-')) {
                return explode('-', $permission->name)[0];
            }
            return 'general';
        });

        return view('permission-toolkit::matrix.index', compact('roles', 'groupedPermissions', 'permissions'));
    }

    /**
     * Asynchronously toggle a permission on a role.
     */
    public function toggle(Request $request, PermissionRegistrar $registrar): JsonResponse
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:' . config('permission.table_names.roles', 'roles') . ',id',
            'permission_id' => 'required|exists:' . config('permission.table_names.permissions', 'permissions') . ',id',
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $permission = Permission::findOrFail($validated['permission_id']);

        $hasPermission = $role->hasPermissionTo($permission->name);

        if ($hasPermission) {
            $role->revokePermissionTo($permission);
            $action = 'revoked';
        } else {
            $role->givePermissionTo($permission);
            $action = 'assigned';
        }

        // Clear Spatie permission cache
        $registrar->forgetCachedPermissions();

        // Audit log
        AuditLogger::log(
            targetUser: $role,
            action: $action,
            type: 'role_permission',
            targetName: "{$role->name} ➔ {$permission->name}",
            metadata: ['role' => $role->name, 'permission' => $permission->name]
        );

        return response()->json([
            'success' => true,
            'action' => $action,
            'role' => $role->name,
            'permission' => $permission->name,
            'has_permission' => ! $hasPermission,
            'message' => __('permission-toolkit::messages.msg_perm_toggled', [
                'permission' => $permission->name,
                'action' => $action,
                'role' => $role->name,
            ]),
        ]);
    }
}
