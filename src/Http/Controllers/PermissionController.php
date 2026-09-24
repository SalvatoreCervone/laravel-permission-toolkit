<?php

namespace SalvatoreCervone\PermissionToolkit\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SalvatoreCervone\PermissionToolkit\Services\AuditLogger;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    /**
     * Store a newly created permission.
     */
    public function store(Request $request, PermissionRegistrar $registrar): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'guard_name' => 'nullable|string|max:50',
        ]);

        $guardName = $validated['guard_name'] ?: 'web';

        $permission = Permission::firstOrCreate([
            'name' => trim($validated['name']),
            'guard_name' => $guardName,
        ]);

        $registrar->forgetCachedPermissions();

        AuditLogger::log(
            targetUser: $permission,
            action: 'created',
            type: 'permission',
            targetName: "Permission: {$permission->name} ({$permission->guard_name})",
            metadata: ['permission_id' => $permission->id, 'guard_name' => $permission->guard_name]
        );

        return response()->json([
            'success' => true,
            'permission' => $permission,
            'message' => __('permission-toolkit::messages.msg_perm_created', ['name' => $permission->name]),
        ]);
    }

    /**
     * Remove the specified permission.
     */
    public function destroy(string|int $id, PermissionRegistrar $registrar): JsonResponse
    {
        $permission = Permission::findOrFail($id);
        $permName = $permission->name;

        AuditLogger::log(
            targetUser: $permission,
            action: 'deleted',
            type: 'permission',
            targetName: "Permission: {$permName}",
            metadata: ['permission_id' => $id]
        );

        $permission->delete();
        $registrar->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => __('permission-toolkit::messages.msg_perm_deleted', ['name' => $permName]),
        ]);
    }
}
