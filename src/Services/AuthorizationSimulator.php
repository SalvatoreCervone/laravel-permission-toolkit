<?php

namespace SalvatoreCervone\PermissionToolkit\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

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
        $superAdminEnabled = $superAdminConfig['enabled'] ?? true;
        $superAdminRoles = (array) ($superAdminConfig['role_name'] ?? ['super-admin', 'Super Admin']);

        if ($superAdminEnabled) {
            $userRoleNames = method_exists($user, 'getRoleNames') ? $user->getRoleNames()->toArray() : [];

            $matchingRole = $this->findMatchingSuperAdminRole($user, $superAdminRoles, $userRoleNames);

            if ($matchingRole !== null) {
                $steps[] = [
                    'step' => 'Super Admin Bypass',
                    'status' => 'PASS',
                    'detail' => "User possesses the bypass role '{$matchingRole}'. Unrestricted access granted.",
                ];
                $isAllowed = true;
                $decisionReason = "Bypassed via Super Admin role ({$matchingRole})";

                return $this->formatResult(true, $decisionReason, $steps, $user, $ability, $target);
            } else {
                $rolesRequiredStr = implode(', ', $superAdminRoles);
                $steps[] = [
                    'step' => 'Super Admin Bypass',
                    'status' => 'SKIP',
                    'detail' => "User does not hold the bypass role [{$rolesRequiredStr}]. Proceeding to fine-grained checks.",
                ];
            }
        } else {
            $steps[] = [
                'step' => 'Super Admin Bypass',
                'status' => 'SKIP',
                'detail' => "Super Admin bypass is disabled in configuration.",
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
     * Robust check for super admin role (exact and normalized match).
     */
    protected function findMatchingSuperAdminRole(Authenticatable $user, array $configuredRoles, array $actualRoles): ?string
    {
        // 1. Direct match with hasRole
        foreach ($configuredRoles as $role) {
            if (method_exists($user, 'hasRole') && $user->hasRole($role)) {
                return $role;
            }
        }

        // 2. Normalized match (handles 'Super Admin' vs 'super-admin' vs 'super_admin' vs 'SuperAdmin')
        foreach ($configuredRoles as $cfgRole) {
            $normCfg = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $cfgRole));
            foreach ($actualRoles as $actRole) {
                $normAct = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $actRole));
                if ($normCfg === $normAct) {
                    return $actRole;
                }
            }
        }

        return null;
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
