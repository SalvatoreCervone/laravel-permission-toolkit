<?php

namespace SalvatoreCervone\PermissionToolkit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SalvatoreCervone\PermissionToolkit\Services\AuthorizationSimulator;
use Spatie\Permission\Models\Permission;

class SimulatorController extends Controller
{
    /**
     * Show simulator interface and optional evaluation output.
     */
    public function index(Request $request, AuthorizationSimulator $simulator)
    {
        $userModelClass = config('permission-toolkit.user_model')
            ?? config('auth.providers.users.model', 'App\\Models\\User');

        $users = class_exists($userModelClass)
            ? (new $userModelClass)->newQuery()->orderBy('id')->limit(50)->get()
            : collect();

        $permissions = Permission::orderBy('name')->get();

        $selectedUserId = $request->get('user_id');
        $selectedAbility = $request->get('ability');
        $modelClass = $request->get('model_class');
        $modelId = $request->get('model_id');

        $simulationResult = null;

        if ($selectedUserId && $selectedAbility && class_exists($userModelClass)) {
            $user = (new $userModelClass)->newQuery()->find($selectedUserId);

            if ($user) {
                $targetInstance = null;
                if ($modelClass && class_exists($modelClass)) {
                    $targetInstance = $modelId ? (new $modelClass)->newQuery()->find($modelId) : $modelClass;
                }

                $simulationResult = $simulator->simulate($user, $selectedAbility, $targetInstance);
            }
        }

        return view('permission-toolkit::simulator.index', compact(
            'users',
            'permissions',
            'selectedUserId',
            'selectedAbility',
            'modelClass',
            'modelId',
            'simulationResult'
        ));
    }
}
