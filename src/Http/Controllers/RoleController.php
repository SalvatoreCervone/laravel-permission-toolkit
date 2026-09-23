<?php

namespace SalvatoreCervone\PermissionToolkit\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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

        return response()->json([
            'success' => true,
            'role' => $role,
            'message' => "Ruolo [{$role->name}] creato con successo!",
        ]);
    }

    /**
     * Remove the specified role.
     */
    public function destroy(int $id, PermissionRegistrar $registrar): JsonResponse
    {
        $role = Role::findOrFail($id);
        $roleName = $role->name;

        AuditLogger::log(
            targetUser: $role,
            action: 'deleted',
            type: 'role',
            targetName: "Role: {$roleName}",
            metadata: ['role_id' => $id]
        );

        $role->delete();
        $registrar->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => "Ruolo [{$roleName}] eliminato.",
        ]);
    }
}
