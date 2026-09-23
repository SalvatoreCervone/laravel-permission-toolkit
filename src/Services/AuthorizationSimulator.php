<?php

namespace SalvatoreCervone\PermissionToolkit\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Contracts\Role;
use Spatie\Permission\Contracts\Permission;

class AuthorizationSimulator
{
    /**
     * Run a comprehensive diagnostic simulation of an authorization check.
     */
    public function simulate(
        Authenticatable|Model $user,
        string $ability,
        mixed $target = null
    ): array {
        $steps = [];
        $isAllowed = false;
        $decisionReason = '';

        // Step 1: User Identity Verification
        $userClass = get_class($user);
        $userId = $user->getAuthIdentifier();
        $userName = $user->name ?? $user->email ?? "User #{$userId}";

        $steps[] = [
            'step' => 'User Identity',
            'status' => 'PASS',
            'detail' => "Authenticated target: [{$userClass}] ID: {$userId} ({$userName})",
        ];

        // Step 2: Super Admin Check
        $superAdminConfig = config('permission-toolkit.super_admin', []);
        $superAdminRole = $superAdminConfig['role_name'] ?? 'super-admin';
        $hasSuperAdminRole = false;

        if (method_exists($user, 'hasRole') && $user->hasRole($superAdminRole)) {
            $hasSuperAdminRole = true;
            $steps[] = [
                'step' => 'Super Admin Bypass',
                'status' => 'PASS',
                'detail' => "User possesses the bypass role '{$superAdminRole}'. Unrestricted access granted.",
            ];
            $isAllowed = true;
            $decisionReason = "Bypassed via Super Admin role ({$superAdminRole})";

            return $this->formatResult(true, $decisionReason, $steps, $user, $ability, $target);
        } else {
            $steps[] = [
                'step' => 'Super Admin Bypass',
                'status' => 'SKIP',
                'detail' => "User does not hold the bypass role '{$superAdminRole}'. Proceeding to fine-grained checks.",
            ];
        }

        // Step 3: Spatie Direct Permission Check
        $hasDirect = false;
        if (method_exists($user, 'hasDirectPermission')) {
            try {
                $hasDirect = $user->hasDirectPermission($ability);
            } catch (\Throwable) {
                $hasDirect = false;
            }
        }

        if ($hasDirect) {
            $steps[] = [
                'step' => 'Direct Permission',
                'status' => 'PASS',
                'detail' => "Ability '{$ability}' is directly assigned to the user record.",
            ];
            $isAllowed = true;
            $decisionReason = "Allowed via Direct Permission assignment";
        } else {
            $steps[] = [
                'step' => 'Direct Permission',
                'status' => 'FAIL',
                'detail' => "Ability '{$ability}' is not directly assigned to this user.",
            ];
        }

        // Step 4: Spatie Role Inheritance Check
        $rolesWithAbility = [];
        $userRoles = method_exists($user, 'getRoleNames') ? $user->getRoleNames()->toArray() : [];

        if (method_exists($user, 'roles')) {
            foreach ($user->roles as $role) {
                if ($role->hasPermissionTo($ability)) {
                    $rolesWithAbility[] = $role->name;
                }
            }
        }

        if (! empty($rolesWithAbility)) {
            $rolesList = implode(', ', $rolesWithAbility);
            $steps[] = [
                'step' => 'Role Permission Inheritance',
                'status' => 'PASS',
                'detail' => "Ability '{$ability}' is granted via active role(s): [{$rolesList}].",
            ];
            if (! $isAllowed) {
                $isAllowed = true;
                $decisionReason = "Allowed via Role inheritance [{$rolesList}]";
            }
        } else {
            $currentRoles = empty($userRoles) ? 'None' : implode(', ', $userRoles);
            $steps[] = [
                'step' => 'Role Permission Inheritance',
                'status' => 'FAIL',
                'detail' => "None of the user's assigned roles ({$currentRoles}) contain the permission '{$ability}'.",
            ];
        }

        // Step 5: Native Laravel Gate & Policy Check
        if ($target !== null) {
            $targetDesc = is_object($target) ? get_class($target) . ' #' . ($target->id ?? '') : (string) $target;
            $gateResponse = Gate::forUser($user)->inspect($ability, $target);

            if ($gateResponse->allowed()) {
                $steps[] = [
                    'step' => 'Policy / Gate Evaluation',
                    'status' => 'PASS',
                    'detail' => "Gate::forUser()->inspect('{$ability}', {$targetDesc}) evaluated to ALLOWED.",
                ];
                $isAllowed = true;
                $decisionReason = "Allowed by Laravel Policy/Gate condition for {$targetDesc}";
            } else {
                $steps[] = [
                    'step' => 'Policy / Gate Evaluation',
                    'status' => 'FAIL',
                    'detail' => "Gate evaluated to DENIED. Message: " . ($gateResponse->message() ?: 'Forbidden by policy'),
                ];
                // Policy has veto power if specifically evaluated
                $isAllowed = false;
                $decisionReason = "Denied by Laravel Policy: " . ($gateResponse->message() ?: 'Explicit policy refusal');
            }
        } else {
            $steps[] = [
                'step' => 'Policy / Gate Evaluation',
                'status' => 'SKIP',
                'detail' => "No specific model/resource instance was supplied. Evaluated purely at RBAC level.",
            ];
        }

        if (! $isAllowed && empty($decisionReason)) {
            $decisionReason = "No matching direct permission, role permission, or policy grant found for '{$ability}'";
        }

        return $this->formatResult($isAllowed, $decisionReason, $steps, $user, $ability, $target);
    }

    /**
     * Structure the diagnostic output array.
     */
    protected function formatResult(
        bool $allowed,
        string $reason,
        array $steps,
        Authenticatable $user,
        string $ability,
        mixed $target
    ): array {
        return [
            'verdict' => $allowed ? 'ALLOWED' : 'DENIED',
            'is_allowed' => $allowed,
            'reason' => $reason,
            'ability' => $ability,
            'target' => $target ? (is_object($target) ? get_class($target) : $target) : null,
            'user' => [
                'id' => $user->getAuthIdentifier(),
                'class' => get_class($user),
                'name' => $user->name ?? $user->email ?? 'User #' . $user->getAuthIdentifier(),
                'roles' => method_exists($user, 'getRoleNames') ? $user->getRoleNames()->toArray() : [],
            ],
            'steps' => $steps,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
