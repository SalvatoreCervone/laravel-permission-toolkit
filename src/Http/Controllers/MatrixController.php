<?php

namespace SalvatoreCervone\PermissionToolkit\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use SalvatoreCervone\PermissionToolkit\Events\PermissionToggled;
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
        $allGrouped = $permissions->groupBy(function ($permission) use ($separator) {
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

        $availableModules = array_keys($allGrouped->toArray());
        sort($availableModules);

        $selectedModule = $request->get('module', 'all');

        $groupedPermissions = $allGrouped;
        if ($selectedModule !== 'all' && isset($allGrouped[$selectedModule])) {
            $groupedPermissions = collect([$selectedModule => $allGrouped[$selectedModule]]);
            $permissions = $allGrouped[$selectedModule];
        }

        return view('permission-toolkit::matrix.index', compact(
            'roles',
            'groupedPermissions',
            'permissions',
            'availableGuards',
            'selectedGuard',
            'availableModules',
            'selectedModule'
        ));
    }

    /**
     * Asynchronously toggle a single permission on a role.
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

            // Dispatch Domain Event
            event(new PermissionToggled($role, $permission, $action));
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

    /**
     * Bulk assign or revoke multiple permissions for a role in one transaction.
     */
    public function bulkToggle(Request $request, PermissionRegistrar $registrar): JsonResponse
    {
        $validated = $request->validate([
            'role_id' => 'required',
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'required',
            'action' => 'required|in:assign,revoke,toggle',
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $permissions = Permission::whereIn('id', $validated['permission_ids'])->get();

        $action = $validated['action'];
        $processed = 0;

        DB::transaction(function () use ($role, $permissions, $action, $registrar, &$processed) {
            foreach ($permissions as $permission) {
                // Skip if guard mismatches
                if ($role->guard_name !== $permission->guard_name) {
                    continue;
                }

                $has = $role->hasPermissionTo($permission->name);
                $appliedAction = null;

                if ($action === 'assign' && ! $has) {
                    $role->givePermissionTo($permission);
                    $appliedAction = 'assigned';
                } elseif ($action === 'revoke' && $has) {
                    $role->revokePermissionTo($permission);
                    $appliedAction = 'revoked';
                } elseif ($action === 'toggle') {
                    if ($has) {
                        $role->revokePermissionTo($permission);
                        $appliedAction = 'revoked';
                    } else {
                        $role->givePermissionTo($permission);
                        $appliedAction = 'assigned';
                    }
                }

                if ($appliedAction !== null) {
                    $processed++;

                    AuditLogger::log(
                        targetUser: $role,
                        action: $appliedAction,
                        type: 'role_permission_bulk',
                        targetName: "{$role->name} ➔ {$permission->name}",
                        metadata: [
                            'role' => $role->name,
                            'permission' => $permission->name,
                            'bulk' => true,
                        ]
                    );

                    event(new PermissionToggled($role, $permission, $appliedAction));
                }
            }

            $registrar->forgetCachedPermissions();
        });

        return response()->json([
            'success' => true,
            'processed' => $processed,
            'role' => $role->name,
            'message' => __('permission-toolkit::messages.bulk_processed', [
                'count' => $processed,
                'role' => $role->name,
            ]),
        ]);
    }
}
