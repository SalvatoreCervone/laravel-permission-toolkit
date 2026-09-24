<?php

namespace SalvatoreCervone\PermissionToolkit\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SalvatoreCervone\PermissionToolkit\Events\RoleCreated;
use SalvatoreCervone\PermissionToolkit\Events\RoleDeleted;
use SalvatoreCervone\PermissionToolkit\Services\AuditLogger;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    /**
     * Store a newly created role.
     */
    public function store(Request $request, PermissionRegistrar $registrar): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'guard_name' => 'nullable|string|max:50',
        ]);

        $guardName = $validated['guard_name'] ?: 'web';

        $role = Role::firstOrCreate([
            'name' => trim($validated['name']),
            'guard_name' => $guardName,
        ]);

        $registrar->forgetCachedPermissions();

        AuditLogger::log(
            targetUser: $role,
            action: 'created',
            type: 'role',
            targetName: "Role: {$role->name} ({$role->guard_name})",
            metadata: ['role_id' => $role->id, 'guard_name' => $role->guard_name]
        );

        event(new RoleCreated($role));

        return response()->json([
            'success' => true,
            'role' => $role,
            'message' => __('permission-toolkit::messages.msg_role_created', ['name' => $role->name]),
        ]);
    }

    /**
     * Remove the specified role.
     */
    public function destroy(string|int $id, PermissionRegistrar $registrar): JsonResponse
    {
        $role = Role::findOrFail($id);
        $roleName = $role->name;

        // Guardrail: Protect configured Super Admin roles from accidental deletion
        $superAdminConfig = config('permission-toolkit.super_admin', []);
        $superAdminRoles = (array) ($superAdminConfig['role_name'] ?? ['super-admin', 'Super Admin']);

        if (in_array($roleName, $superAdminRoles, true)) {
            return response()->json([
                'success' => false,
                'message' => __('permission-toolkit::messages.cannot_delete_super_admin_role'),
            ], 403);
        }

        // Guardrail: Prevent currently authenticated user from deleting a role they belong to
        if (auth()->check() && method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole($roleName)) {
            return response()->json([
                'success' => false,
                'message' => __('permission-toolkit::messages.cannot_delete_own_role'),
            ], 403);
        }

        AuditLogger::log(
            targetUser: $role,
            action: 'deleted',
            type: 'role',
            targetName: "Role: {$roleName}",
            metadata: ['role_id' => $id]
        );

        $role->delete();
        $registrar->forgetCachedPermissions();

        event(new RoleDeleted($roleName, $id));

        return response()->json([
            'success' => true,
            'message' => __('permission-toolkit::messages.msg_role_deleted', ['name' => $roleName]),
        ]);
    }
}
