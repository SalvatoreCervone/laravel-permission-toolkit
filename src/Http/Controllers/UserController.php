<?php

namespace SalvatoreCervone\PermissionToolkit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use SalvatoreCervone\PermissionToolkit\Events\UserAccessUpdated;
use SalvatoreCervone\PermissionToolkit\PermissionToolkit;
use SalvatoreCervone\PermissionToolkit\Services\AuditLogger;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserController extends Controller
{
    /**
     * Display a listing of authenticatable users.
     */
    public function index(Request $request)
    {
        $userModelClass = config('permission-toolkit.user_model')
            ?? config('auth.providers.users.model', 'App\\Models\\User');

        if (! class_exists($userModelClass)) {
            abort(500, "Configured user model [{$userModelClass}] does not exist.");
        }

        $supportsSoftDeletes = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($userModelClass));
        $roles = Role::orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();

        $query = (new $userModelClass)->newQuery()->with(['roles', 'permissions']);
        $table = (new $userModelClass)->getTable();
        $keyName = (new $userModelClass)->getKeyName();
        $displayColumns = PermissionToolkit::getUserDisplayColumns();

        $statusFilter = $request->get('status', 'all');
        if ($supportsSoftDeletes) {
            if ($statusFilter === 'trashed') {
                $query->onlyTrashed();
            } elseif ($statusFilter === 'active') {
                // Default: active non-trashed models only
            } else {
                $query->withTrashed();
            }
        }

        if ($search = trim($request->get('search', ''))) {
            $query->where(function ($q) use ($search, $table, $displayColumns) {
                $hasCondition = false;

                // Match integer ID only when search input is numeric to avoid PostgreSQL/SQLServer type mismatch
                if (is_numeric($search)) {
                    $q->where($q->getModel()->getQualifiedKeyName(), $search);
                    $hasCondition = true;
                }

                // Check all configured display columns
                foreach ($displayColumns as $col) {
                    if (is_string($col) && Schema::hasColumn($table, $col)) {
                        $hasCondition ? $q->orWhere($table . '.' . $col, 'like', "%{$search}%") : $q->where($table . '.' . $col, 'like', "%{$search}%");
                        $hasCondition = true;
                    }
                }

                // Check standard fallback columns
                foreach (['name', 'email', 'username'] as $stdCol) {
                    if (! in_array($stdCol, $displayColumns) && Schema::hasColumn($table, $stdCol)) {
                        $hasCondition ? $q->orWhere($table . '.' . $stdCol, 'like', "%{$search}%") : $q->where($table . '.' . $stdCol, 'like', "%{$search}%");
                        $hasCondition = true;
                    }
                }
            });
        }

        $selectedRole = $request->get('role');
        if ($selectedRole) {
            $query->whereHas('roles', function ($q) use ($selectedRole) {
                is_numeric($selectedRole) ? $q->where('id', $selectedRole) : $q->where('name', $selectedRole);
            });
        }

        $selectedPermission = $request->get('permission');
        if ($selectedPermission) {
            $query->where(function ($q) use ($selectedPermission) {
                $q->whereHas('permissions', function ($pq) use ($selectedPermission) {
                    is_numeric($selectedPermission) ? $pq->where('id', $selectedPermission) : $pq->where('name', $selectedPermission);
                })->orWhereHas('roles.permissions', function ($rq) use ($selectedPermission) {
                    is_numeric($selectedPermission) ? $rq->where('id', $selectedPermission) : $rq->where('name', $selectedPermission);
                });
            });
        }

        // 1. Prima tutti gli attivi (active non-trashed users always first when soft deletes are supported)
        $sortParam = $request->get('sort');
        $directionParam = strtolower($request->get('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        if ($supportsSoftDeletes) {
            $deletedAtCol = (new $userModelClass)->getDeletedAtColumn();
            $statusDir = ($sortParam === 'status' && $directionParam === 'desc') ? 'DESC' : 'ASC';
            $query->orderByRaw("CASE WHEN {$table}.{$deletedAtCol} IS NULL THEN 0 ELSE 1 END {$statusDir}");
        }

        // 2. Ordinamento colonne: basato sui parametri oppure sulla configurazione (display_columns / order_by)
        if ($sortParam) {
            if ($sortParam === 'status') {
                foreach ($displayColumns as $col) {
                    if (is_string($col) && Schema::hasColumn($table, $col)) {
                        $query->orderBy($table . '.' . $col, 'asc');
                    }
                }
            } elseif ($sortParam === 'user') {
                foreach ($displayColumns as $col) {
                    if (is_string($col) && Schema::hasColumn($table, $col)) {
                        $query->orderBy($table . '.' . $col, $directionParam);
                    }
                }
            } elseif (Schema::hasColumn($table, $sortParam)) {
                $query->orderBy($table . '.' . $sortParam, $directionParam);
            } elseif ($sortParam === 'name' && ! Schema::hasColumn($table, 'name')) {
                foreach ($displayColumns as $col) {
                    if (is_string($col) && Schema::hasColumn($table, $col)) {
                        $query->orderBy($table . '.' . $col, $directionParam);
                    }
                }
            }
        } else {
            $configuredOrderBy = config('permission-toolkit.users.order_by');

            if (! empty($configuredOrderBy)) {
                if (is_string($configuredOrderBy)) {
                    $configuredOrderBy = array_map('trim', explode(',', $configuredOrderBy));
                }
                foreach ((array) $configuredOrderBy as $col => $dir) {
                    if (is_int($col)) {
                        $col = $dir;
                        $dir = 'asc';
                    }
                    $dir = strtolower($dir) === 'desc' ? 'desc' : 'asc';
                    if (is_string($col) && Schema::hasColumn($table, $col)) {
                        $query->orderBy($table . '.' . $col, $dir);
                    }
                }
            } else {
                // Di default: ordinamento in base alle colonne configurate in display_columns (es. cognome, nome)
                foreach ($displayColumns as $col) {
                    if (is_string($col) && Schema::hasColumn($table, $col)) {
                        $query->orderBy($table . '.' . $col, 'asc');
                    }
                }
            }
        }

        // Deterministic secondary sort
        if (Schema::hasColumn($table, $keyName)) {
            $query->orderBy($table . '.' . $keyName, 'asc');
        }

        $users = $query->paginate(20)->withQueryString();

        return view('permission-toolkit::users.index', compact(
            'users',
            'roles',
            'permissions',
            'supportsSoftDeletes',
            'selectedRole',
            'selectedPermission',
            'statusFilter',
            'displayColumns',
            'sortParam',
            'directionParam'
        ));
    }

    /**
     * Show form to edit user roles, direct permissions and password.
     */
    public function edit(string|int $id)
    {
        $userModelClass = config('permission-toolkit.user_model')
            ?? config('auth.providers.users.model', 'App\\Models\\User');

        $userQuery = (new $userModelClass)->newQuery()->with(['roles.permissions', 'permissions']);
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($userModelClass))) {
            $userQuery->withTrashed();
        }
        $user = $userQuery->findOrFail($id);

        $roles = Role::with('permissions')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();

        $separator = config('permission-toolkit.matrix.group_separator', '.');
        $groupedPermissions = $permissions->groupBy(function ($p) use ($separator) {
            if (str_contains($p->name, $separator)) return explode($separator, $p->name)[0];
            if (str_contains($p->name, ':')) return explode(':', $p->name)[0];
            if (str_contains($p->name, '-')) return explode('-', $p->name)[0];
            return 'general';
        });

        $userRoleIds = $user->roles->pluck('id')->toArray();
        $userPermissionIds = $user->permissions->pluck('id')->toArray();

        // Calculate inherited permissions from user's current roles
        $inheritedPermissions = [];
        foreach ($user->roles as $role) {
            foreach ($role->permissions as $perm) {
                $inheritedPermissions[$perm->id][] = $role->name;
            }
        }

        // Map for dynamic real-time role toggle in JS
        $rolePermissionsMap = [];
        foreach ($roles as $role) {
            $rolePermissionsMap[$role->id] = [
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('id')->toArray(),
            ];
        }

        $defaultDateField = config('permission-toolkit.password_reset.date_field', null);
        $passwordResetEnabled = config('permission-toolkit.password_reset.enabled', true);

        return view('permission-toolkit::users.edit', compact(
            'user',
            'roles',
            'groupedPermissions',
            'userRoleIds',
            'userPermissionIds',
            'inheritedPermissions',
            'rolePermissionsMap',
            'defaultDateField',
            'passwordResetEnabled'
        ));
    }

    /**
     * Update roles and direct permissions for a user.
     */
    public function update(Request $request, string|int $id, PermissionRegistrar $registrar)
    {
        $userModelClass = config('permission-toolkit.user_model')
            ?? config('auth.providers.users.model', 'App\\Models\\User');

        $user = (new $userModelClass)->newQuery()->findOrFail($id);

        $selectedRoleInputs = (array) $request->input('roles', []);
        $selectedPermInputs = (array) $request->input('permissions', $request->input('direct_permissions', []));

        $roles = Role::where(function ($q) use ($selectedRoleInputs) {
            $q->whereIn('id', $selectedRoleInputs)->orWhereIn('name', $selectedRoleInputs);
        })->get();

        $permissions = Permission::where(function ($q) use ($selectedPermInputs) {
            $q->whereIn('id', $selectedPermInputs)->orWhereIn('name', $selectedPermInputs);
        })->get();

        $oldRoles = $user->roles->pluck('name')->toArray();
        $oldPerms = $user->permissions->pluck('name')->toArray();

        DB::transaction(function () use ($user, $roles, $permissions, $oldRoles, $oldPerms, $registrar) {
            // Sync roles & permissions natively with Spatie
            $user->syncRoles($roles);
            $user->syncPermissions($permissions);

            $newRoles = $roles->pluck('name')->toArray();
            $newPerms = $permissions->pluck('name')->toArray();

            // Audit Trail tracking
            $addedRoles = array_diff($newRoles, $oldRoles);
            $removedRoles = array_diff($oldRoles, $newRoles);
            $addedPerms = array_diff($newPerms, $oldPerms);
            $removedPerms = array_diff($oldPerms, $newPerms);

            foreach ($addedRoles as $r) {
                AuditLogger::log($user, 'assigned', 'role', $r);
            }
            foreach ($removedRoles as $r) {
                AuditLogger::log($user, 'revoked', 'role', $r);
            }
            foreach ($addedPerms as $p) {
                AuditLogger::log($user, 'assigned', 'permission', $p);
            }
            foreach ($removedPerms as $p) {
                AuditLogger::log($user, 'revoked', 'permission', $p);
            }

            $registrar->forgetCachedPermissions();

            event(new UserAccessUpdated($user, $addedRoles, $removedRoles, $addedPerms, $removedPerms));
        });

        $displayName = PermissionToolkit::getUserDisplayName($user);

        return redirect()
            ->route('permission-toolkit.users.edit', $id)
            ->with('status', __('permission-toolkit::messages.msg_user_access_updated', ['name' => $displayName]));
    }

    /**
     * Reset user password and optionally update an associated date/timestamp field.
     * The target date column is strictly configured via config or .env.
     */
    public function resetPassword(Request $request, string|int $id)
    {
        if (! config('permission-toolkit.password_reset.enabled', true)) {
            abort(403, __('permission-toolkit::messages.msg_pwd_reset_disabled'));
        }

        $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'date_value' => ['nullable', 'string'],
            'update_date' => ['nullable'],
        ]);

        $userModelClass = config('permission-toolkit.user_model')
            ?? config('auth.providers.users.model', 'App\\Models\\User');

        $user = (new $userModelClass)->newQuery()->findOrFail($id);

        $dateField = config('permission-toolkit.password_reset.date_field', null);
        $shouldUpdateDate = $dateField && ($request->has('update_date')
            ? $request->boolean('update_date')
            : $request->filled('date_value'));

        $dateValue = $shouldUpdateDate ? ($request->input('date_value') ?: now()->startOfDay()->toDateTimeString()) : null;
        $dateApplied = false;

        DB::transaction(function () use ($user, $request, $shouldUpdateDate, $dateField, $dateValue, &$dateApplied) {
            // Update password
            $user->password = Hash::make($request->input('password'));

            $table = $user->getTable();
            if ($shouldUpdateDate && $dateField && $dateValue && Schema::hasColumn($table, $dateField)) {
                $user->{$dateField} = Carbon::parse($dateValue);
                $dateApplied = true;
            }

            $user->save();

            // Audit Trail tracking
            AuditLogger::log(
                $user,
                'reset',
                'password',
                'password',
                null,
                null,
                array_filter([
                    'date_field' => $dateField,
                    'date_value' => $dateValue,
                    'date_applied' => $dateApplied,
                ])
            );
        });

        $displayName = PermissionToolkit::getUserDisplayName($user);
        $msg = __('permission-toolkit::messages.msg_user_password_reset', ['name' => $displayName]);
        if ($dateApplied) {
            $msg .= __('permission-toolkit::messages.msg_user_password_field_updated', ['field' => $dateField, 'value' => $dateValue]);
        }

        return redirect()
            ->route('permission-toolkit.users.edit', $id)
            ->with('status', $msg);
    }

    /**
     * Delete or deactivate the specified user.
     */
    public function destroy(Request $request, string|int $id)
    {
        $userModelClass = config('permission-toolkit.user_model')
            ?? config('auth.providers.users.model', 'App\\Models\\User');

        if (! class_exists($userModelClass)) {
            abort(500, "Configured user model [{$userModelClass}] does not exist.");
        }

        if (auth()->check() && (string) auth()->id() === (string) $id) {
            return back()->with('error', __('permission-toolkit::messages.cannot_delete_own_user'));
        }

        $supportsSoftDeletes = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($userModelClass));
        $query = (new $userModelClass)->newQuery();
        if ($supportsSoftDeletes) {
            $query->withTrashed();
        }

        $user = $query->findOrFail($id);

        // Guardrail: Super Admin check
        $superAdminConfig = config('permission-toolkit.super_admin', []);
        $superAdminRoles = (array) ($superAdminConfig['role_name'] ?? ['super-admin', 'Super Admin']);
        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole($superAdminRoles)) {
            return back()->with('error', __('permission-toolkit::messages.cannot_delete_super_admin_user'));
        }

        $isSoftDelete = $supportsSoftDeletes && ! $user->trashed();
        $displayName = PermissionToolkit::getUserDisplayName($user);

        AuditLogger::log(
            targetUser: $user,
            action: $isSoftDelete ? 'deactivated' : 'deleted',
            type: 'user',
            targetName: "User: " . ($displayName ?: "#{$user->id}"),
            metadata: ['soft_delete' => $isSoftDelete]
        );

        $user->delete();

        $msg = $isSoftDelete
            ? __('permission-toolkit::messages.msg_user_deactivated', ['name' => $displayName ?: "#{$user->id}"])
            : __('permission-toolkit::messages.msg_user_deleted', ['name' => $displayName ?: "#{$user->id}"]);

        return redirect()->route('permission-toolkit.users.index')->with('status', $msg);
    }

    /**
     * Restore a soft-deleted (deactivated) user.
     */
    public function restore(Request $request, string|int $id)
    {
        $userModelClass = config('permission-toolkit.user_model')
            ?? config('auth.providers.users.model', 'App\\Models\\User');

        $supportsSoftDeletes = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($userModelClass));
        if (! $supportsSoftDeletes) {
            abort(404, "Soft deletes are not supported by the user model.");
        }

        $user = (new $userModelClass)->onlyTrashed()->findOrFail($id);

        $user->restore();

        $displayName = PermissionToolkit::getUserDisplayName($user);

        AuditLogger::log(
            targetUser: $user,
            action: 'restored',
            type: 'user',
            targetName: "User: " . ($displayName ?: "#{$user->id}")
        );

        return back()->with('status', __('permission-toolkit::messages.msg_user_restored', ['name' => $displayName ?: "#{$user->id}"]));
    }

    /**
     * Permanently remove a user from the database.
     */
    public function forceDelete(Request $request, string|int $id)
    {
        $userModelClass = config('permission-toolkit.user_model')
            ?? config('auth.providers.users.model', 'App\\Models\\User');

        if (auth()->check() && (string) auth()->id() === (string) $id) {
            return back()->with('error', __('permission-toolkit::messages.cannot_delete_own_user'));
        }

        $supportsSoftDeletes = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($userModelClass));
        $query = (new $userModelClass)->newQuery();
        if ($supportsSoftDeletes) {
            $query->withTrashed();
        }

        $user = $query->findOrFail($id);

        // Guardrail: Super Admin check
        $superAdminConfig = config('permission-toolkit.super_admin', []);
        $superAdminRoles = (array) ($superAdminConfig['role_name'] ?? ['super-admin', 'Super Admin']);
        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole($superAdminRoles)) {
            return back()->with('error', __('permission-toolkit::messages.cannot_delete_super_admin_user'));
        }

        $displayName = PermissionToolkit::getUserDisplayName($user);

        AuditLogger::log(
            targetUser: $user,
            action: 'force_deleted',
            type: 'user',
            targetName: "User: " . ($displayName ?: "#{$user->id}")
        );

        if (method_exists($user, 'roles')) {
            $user->roles()->detach();
        }
        if (method_exists($user, 'permissions')) {
            $user->permissions()->detach();
        }

        if ($supportsSoftDeletes && method_exists($user, 'forceDelete')) {
            $user->forceDelete();
        } else {
            $user->delete();
        }

        return redirect()->route('permission-toolkit.users.index')->with('status', __('permission-toolkit::messages.msg_user_force_deleted', ['name' => $displayName ?: "#{$user->id}"]));
    }
}
