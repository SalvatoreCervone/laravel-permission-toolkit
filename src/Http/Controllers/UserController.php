<?php

namespace SalvatoreCervone\PermissionToolkit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
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

        $query = (new $userModelClass)->newQuery()->with(['roles', 'permissions']);

        if ($search = trim($request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $table = $q->getModel()->getTable();
                $hasCondition = false;

                // Match integer ID only when search input is numeric to avoid PostgreSQL/SQLServer type mismatch
                if (is_numeric($search)) {
                    $q->where($q->getModel()->getKeyName(), $search);
                    $hasCondition = true;
                }

                if (Schema::hasColumn($table, 'name')) {
                    $hasCondition ? $q->orWhere('name', 'like', "%{$search}%") : $q->where('name', 'like', "%{$search}%");
                    $hasCondition = true;
                }

                if (Schema::hasColumn($table, 'email')) {
                    $hasCondition ? $q->orWhere('email', 'like', "%{$search}%") : $q->where('email', 'like', "%{$search}%");
                    $hasCondition = true;
                }

                if (Schema::hasColumn($table, 'username')) {
                    $hasCondition ? $q->orWhere('username', 'like', "%{$search}%") : $q->where('username', 'like', "%{$search}%");
                }
            });
        }

        $users = $query->paginate(20)->withQueryString();

        return view('permission-toolkit::users.index', compact('users'));
    }

    /**
     * Show form to edit user roles and direct permissions.
     */
    public function edit(int $id)
    {
        $userModelClass = config('permission-toolkit.user_model')
            ?? config('auth.providers.users.model', 'App\\Models\\User');

        $user = (new $userModelClass)->newQuery()->with(['roles', 'permissions'])->findOrFail($id);
        $roles = Role::orderBy('name')->get();
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

        return view('permission-toolkit::users.edit', compact(
            'user',
            'roles',
            'groupedPermissions',
            'userRoleIds',
            'userPermissionIds'
        ));
    }

    /**
     * Update roles and direct permissions for a user.
     */
    public function update(Request $request, int $id, PermissionRegistrar $registrar)
    {
        $userModelClass = config('permission-toolkit.user_model')
            ?? config('auth.providers.users.model', 'App\\Models\\User');

        $user = (new $userModelClass)->newQuery()->findOrFail($id);

        $selectedRoleIds = $request->input('roles', []);
        $selectedPermIds = $request->input('permissions', []);

        $roles = Role::whereIn('id', $selectedRoleIds)->get();
        $permissions = Permission::whereIn('id', $selectedPermIds)->get();

        $oldRoles = $user->roles->pluck('name')->toArray();
        $oldPerms = $user->permissions->pluck('name')->toArray();

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

        return redirect()
            ->route('permission-toolkit.users.edit', $id)
            ->with('status', "Accessi per l'utente [{$user->name}] aggiornati con successo.");
    }
}
