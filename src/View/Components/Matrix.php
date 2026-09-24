<?php

namespace SalvatoreCervone\PermissionToolkit\View\Components;

use Illuminate\View\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class Matrix extends Component
{
    public $roles;
    public $permissions;
    public $modules;
    public ?string $selectedModule;
    public bool $includeScripts;
    public ?string $guard;

    /**
     * Create a new component instance.
     */
    public function __construct(
        ?string $guard = null,
        ?string $module = null,
        bool $includeScripts = true
    ) {
        $this->guard = $guard;
        $this->selectedModule = $module;
        $this->includeScripts = $includeScripts;

        $roleQuery = Role::query()->with('permissions');
        $permQuery = Permission::query();

        if ($this->guard) {
            $roleQuery->where('guard_name', $this->guard);
            $permQuery->where('guard_name', $this->guard);
        }

        $this->roles = $roleQuery->orderBy('name')->get();
        $allPermissions = $permQuery->orderBy('name')->get();

        $configuredModules = config('permission-toolkit.modules', []);

        $moduleMap = [];
        $unassigned = [];

        foreach ($allPermissions as $permission) {
            $assigned = false;
            foreach ($configuredModules as $moduleName => $patterns) {
                foreach ((array) $patterns as $pattern) {
                    if (fnmatch($pattern, $permission->name)) {
                        $moduleMap[$moduleName][] = $permission;
                        $assigned = true;
                        break 2;
                    }
                }
            }
            if (! $assigned) {
                $parts = explode('.', $permission->name);
                if (count($parts) > 1) {
                    $autoModule = ucfirst($parts[0]);
                    $moduleMap[$autoModule][] = $permission;
                } else {
                    $unassigned[] = $permission;
                }
            }
        }

        if (! empty($unassigned)) {
            $generalLabel = __('permission-toolkit::messages.module_general');
            $moduleMap[$generalLabel] = array_merge($moduleMap[$generalLabel] ?? [], $unassigned);
        }

        ksort($moduleMap);

        if ($this->selectedModule && isset($moduleMap[$this->selectedModule])) {
            $this->modules = [$this->selectedModule => $moduleMap[$this->selectedModule]];
        } else {
            $this->modules = $moduleMap;
        }

        $this->permissions = $allPermissions;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('permission-toolkit::components.matrix');
    }
}
