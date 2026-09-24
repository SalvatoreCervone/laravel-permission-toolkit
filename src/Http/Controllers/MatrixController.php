<?php

namespace SalvatoreCervone\PermissionToolkit\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
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
        // Extract all available guards in the system
        $availableGuards = Role::select('guard_name')
            ->union(Permission::select('guard_name'))
            ->distinct()
            ->pluck('guard_name')
            ->filter()
            ->values()
            ->toArray();

        if (empty($availableGuards)) {
            $availableGuards = ['web'];
        }

        $selectedGuard = $request->get('guard', $availableGuards[0] ?? 'web');

        $rolesQuery = Role::with('permissions')->orderBy('name');
        $permsQuery = Permission::orderBy('name');

        if ($selectedGuard !== 'all') {
            $rolesQuery->where('guard_name', $selectedGuard);
            $permsQuery->where('guard_name', $selectedGuard);
        }

        $roles = $rolesQuery->get();
        $permissions = $permsQuery->get();

        if ($search = trim($request->get('search', ''))) {
            $permissions = $permissions->filter(function ($p) use ($search) {
                return str_contains(strtolower($p->name), strtolower($search));
            });
        }

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

        return view('permission-toolkit::matrix.index', compact(
            'roles',
            'groupedPermissions',
            'permissions',
            'availableGuards',
            'selectedGuard'
        ));
    }

    /**
     * Asynchronously toggle a permission on a role.
     */
    public function toggle(Request $request, PermissionRegistrar $registrar): JsonResponse
    {
        $validated = $request->validate([
            'role_id' => 'required',
            'permission_id' => 'required',
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $permission = Permission::findOrFail($validated['permission_id']);

        // Prevent Guard mismatch exception from crashing Spatie
        if ($role->guard_name !== $permission->guard_name) {
            return response()->json([
                'success' => false,
                'message' => __('permission-toolkit::messages.guard_mismatch_error', [
                    'role' => $role->name,
                    'role_guard' => $role->guard_name,
                    'permission' => $permission->name,
                    'perm_guard' => $permission->guard_name,
                ]),
            ], 422);
        }

        $action = '';
        $hasPermission = false;

        DB::transaction(function () use ($role, $permission, $registrar, &$action, &$hasPermission) {
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
                metadata: [
                    'role' => $role->name,
                    'permission' => $permission->name,
                    'guard_name' => $role->guard_name,
                ]
            );
        });

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
