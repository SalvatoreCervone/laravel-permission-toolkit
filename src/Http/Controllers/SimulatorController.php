<?php

namespace SalvatoreCervone\PermissionToolkit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use SalvatoreCervone\PermissionToolkit\PermissionToolkit;
use SalvatoreCervone\PermissionToolkit\Services\AuthorizationSimulator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SimulatorController extends Controller
{
    /**
     * Show simulator interface and optional evaluation output.
     */
    public function index(Request $request, AuthorizationSimulator $simulator)
    {
        $userModelClass = config('permission-toolkit.user_model')
            ?? config('auth.providers.users.model', 'App\\Models\\User');

        $users = collect();
        if (class_exists($userModelClass)) {
            $userQuery = (new $userModelClass)->newQuery();
            $table = (new $userModelClass)->getTable();
            $displayColumns = PermissionToolkit::getUserDisplayColumns();
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
                        $userQuery->orderBy($table . '.' . $col, $dir);
                    }
                }
            } else {
                foreach ($displayColumns as $col) {
                    if (is_string($col) && Schema::hasColumn($table, $col)) {
                        $userQuery->orderBy($table . '.' . $col, 'asc');
                    }
                }
            }

            $userQuery->orderBy((new $userModelClass)->getQualifiedKeyName(), 'asc');

            $usersLimit = config('permission-toolkit.simulator.users_limit');
            if ($usersLimit && is_numeric($usersLimit) && (int) $usersLimit > 0) {
                $userQuery->limit((int) $usersLimit);
            }

            $users = $userQuery->get();
        }

        $permissions = Permission::orderBy('name')->get();
        $roles = Role::orderBy('name')->get();

        $mode = $request->get('mode', 'forward');
        $selectedUserId = $request->get('user_id');
        $selectedAbility = $request->get('ability');
        $modelClass = $request->get('model_class');
        $modelId = $request->get('model_id');

        $reverseTarget = $request->get('reverse_target');
        $reverseType = $request->get('reverse_type', 'permission');

        $simulationResult = null;
        $reverseResult = null;

        if ($mode === 'reverse' && $reverseTarget) {
            $reverseResult = $simulator->reverseSimulate($reverseTarget, $reverseType);
        } elseif ($selectedUserId && class_exists($userModelClass)) {
            $userQuery = (new $userModelClass)->newQuery();
            if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($userModelClass))) {
                $userQuery->withTrashed();
            }
            $user = $userQuery->find($selectedUserId);

            if ($user) {
                // Ensure selected user is always present in dropdown
                if (! $users->contains(fn ($u) => (string) $u->getKey() === (string) $user->getKey())) {
                    $users->prepend($user);
                }

                if ($selectedAbility) {
                    $targetInstance = null;
                    if ($modelClass && class_exists($modelClass)) {
                        $modelQuery = (new $modelClass)->newQuery();
                        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($modelClass))) {
                            $modelQuery->withTrashed();
                        }
                        $targetInstance = $modelId ? $modelQuery->find($modelId) : $modelClass;
                    }

                    $simulationResult = $simulator->simulate($user, $selectedAbility, $targetInstance);
                }
            }
        }

        return view('permission-toolkit::simulator.index', compact(
            'users',
            'permissions',
            'roles',
            'mode',
            'selectedUserId',
            'selectedAbility',
            'modelClass',
            'modelId',
            'simulationResult',
            'reverseTarget',
            'reverseType',
            'reverseResult'
        ));
    }
}
