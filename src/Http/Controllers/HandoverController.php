<?php

namespace SalvatoreCervone\PermissionToolkit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SalvatoreCervone\PermissionToolkit\Models\HandoverTemplate;
use SalvatoreCervone\PermissionToolkit\Services\AuditLogger;

class HandoverController extends Controller
{
    /**
     * Display a printable access handover and signoff report for the user.
     */
    public function show(Request $request, $id)
    {
        if (! config('permission-toolkit.handover.enabled', true)) {
            abort(404);
        }

        $userModelClass = config('permission-toolkit.user_model')
            ?? config('auth.providers.users.model', 'App\\Models\\User');

        if (! class_exists($userModelClass)) {
            abort(500, "Configured user model [{$userModelClass}] does not exist.");
        }

        $user = (new $userModelClass)->newQuery()
            ->with(['roles.permissions', 'permissions'])
            ->findOrFail($id);

        $templates = HandoverTemplate::orderByDesc('is_default')->orderBy('name')->get();

        // 1. Resolve template by request param
        $template = null;
        if ($templateId = $request->get('template_id')) {
            $template = HandoverTemplate::find($templateId);
        }

        // 2. Resolve template via custom closure if not explicitly set
        if (! $template) {
            $resolver = config('permission-toolkit.handover.template_resolver');
            if (is_callable($resolver)) {
                $resolved = call_user_func($resolver, $user);
                if ($resolved instanceof HandoverTemplate) {
                    $template = $resolved;
                } elseif (is_numeric($resolved)) {
                    $template = HandoverTemplate::find($resolved);
                } elseif (is_string($resolved)) {
                    $template = HandoverTemplate::where('name', $resolved)->first();
                }
            }
        }

        // 3. Fallback to default template or virtual instance
        if (! $template) {
            $template = HandoverTemplate::getDefault();
        }

        // Separate direct permissions vs permissions inherited through roles
        $directPermissions = $user->permissions->sortBy('name');

        $inheritedMap = [];
        foreach ($user->roles as $role) {
            foreach ($role->permissions as $perm) {
                if (! isset($inheritedMap[$perm->name])) {
                    $inheritedMap[$perm->name] = [
                        'permission' => $perm,
                        'roles' => [$role->name],
                    ];
                } else {
                    $inheritedMap[$perm->name]['roles'][] = $role->name;
                }
            }
        }
        $inheritedPermissions = collect($inheritedMap)->sortBy(fn ($item) => $item['permission']->name);

        // Record compliance audit log
        if (AuditLogger::isEnabled()) {
            AuditLogger::log(
                targetUser: $user,
                action: 'generated',
                type: 'handover_sheet',
                targetName: $template->name ?? 'Standard',
                metadata: [
                    'template_id' => $template->id,
                    'signer_name' => $template->signer_name,
                    'signer_title' => $template->signer_title,
                ]
            );
        }

        return view('permission-toolkit::handover.sheet', compact(
            'user',
            'template',
            'templates',
            'directPermissions',
            'inheritedPermissions'
        ));
    }
}
