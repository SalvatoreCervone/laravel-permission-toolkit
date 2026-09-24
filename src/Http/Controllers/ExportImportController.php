<?php

namespace SalvatoreCervone\PermissionToolkit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use SalvatoreCervone\PermissionToolkit\Events\PermissionsExported;
use SalvatoreCervone\PermissionToolkit\Events\PermissionsImported;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportImportController extends Controller
{
    /**
     * Download a JSON artifact containing all roles, permissions, and relationships.
     */
    public function export(): StreamedResponse
    {
        $permissions = Permission::all(['name', 'guard_name'])->toArray();
        $roles = Role::with('permissions:name,guard_name')->get()->map(function ($role) {
            return [
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions->pluck('name')->toArray(),
            ];
        })->toArray();

        $payload = [
            'exported_at' => now()->toIso8601String(),
            'version' => '1.0',
            'permissions' => $permissions,
            'roles' => $roles,
        ];

        event(new PermissionsExported(count($roles), count($permissions)));

        $fileName = 'spatie_permissions_export_' . date('Y_m_d_His') . '.json';

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }, $fileName, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Upload and import a JSON artifact to synchronize roles and permissions.
     */
    public function import(Request $request, PermissionRegistrar $registrar)
    {
        $request->validate([
            'file' => 'required|file',
            'fresh' => 'nullable|boolean',
        ]);

        $content = file_get_contents($request->file('file')->getRealPath());
        $data = json_decode($content, true);

        $permsKey = isset($data['permissions']) ? 'permissions' : (isset($data['direct_permissions']) ? 'direct_permissions' : null);
        if (! is_array($data) || ! isset($data['roles']) || ! $permsKey) {
            return redirect()->back(fallback: route('permission-toolkit.matrix'))->withErrors([
                'file' => __('permission-toolkit::messages.import_invalid_structure'),
            ]);
        }

        $fresh = $request->boolean('fresh');

        DB::transaction(function () use ($data, $permsKey, $fresh, $registrar) {
            if ($fresh) {
                Permission::query()->delete();
                Role::query()->delete();
            }

            foreach ($data[$permsKey] as $permData) {
                $permName = is_array($permData) ? $permData['name'] : $permData;
                $guardName = is_array($permData) ? ($permData['guard_name'] ?? 'web') : 'web';

                Permission::firstOrCreate([
                    'name' => $permName,
                    'guard_name' => $guardName,
                ]);
            }

            foreach ($data['roles'] as $roleData) {
                $guard = $roleData['guard_name'] ?? 'web';
                $role = Role::firstOrCreate([
                    'name' => $roleData['name'],
                    'guard_name' => $guard,
                ]);

                if (! empty($roleData['permissions'])) {
                    foreach ($roleData['permissions'] as $permName) {
                        Permission::firstOrCreate([
                            'name' => $permName,
                            'guard_name' => $guard,
                        ]);
                    }
                    $role->syncPermissions($roleData['permissions']);
                }
            }

            $registrar->forgetCachedPermissions();
        });

        event(new PermissionsImported(count($data['roles']), count($data[$permsKey]), $fresh));

        return redirect()->back(fallback: route('permission-toolkit.matrix'))->with('status', __('permission-toolkit::messages.import_success', [
            'roles' => count($data['roles']),
            'permissions' => count($data[$permsKey]),
        ]));
    }
}
