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
        $userName = $user->name ?? $user->email ?? __('permission-toolkit::messages.sim_anonymous_user', ['id' => $userId]);

        $steps[] = [
            'step' => 'User Identity',
            'status' => 'PASS',
            'detail' => __('permission-toolkit::messages.sim_step_detail_identity', [
                'class' => $userClass,
                'id' => $userId,
                'name' => $userName,
            ]),
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
                    'detail' => __('permission-toolkit::messages.sim_step_detail_super_admin_pass', [
                        'role' => $matchingRole,
                    ]),
                ];
                $isAllowed = true;
                $decisionReason = __('permission-toolkit::messages.sim_reason_super_admin', [
                    'role' => $matchingRole,
                ]);

                return $this->formatResult(true, $decisionReason, $steps, $user, $ability, $target);
            } else {
                $rolesRequiredStr = implode(', ', $superAdminRoles);
                $steps[] = [
                    'step' => 'Super Admin Bypass',
                    'status' => 'SKIP',
                    'detail' => __('permission-toolkit::messages.sim_step_detail_super_admin_skip', [
                        'roles' => $rolesRequiredStr,
                    ]),
                ];
            }
        } else {
            $steps[] = [
                'step' => 'Super Admin Bypass',
                'status' => 'SKIP',
                'detail' => __('permission-toolkit::messages.sim_step_detail_super_admin_disabled'),
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
                'detail' => __('permission-toolkit::messages.sim_step_detail_direct_pass', [
                    'ability' => $ability,
                ]),
            ];
            $isAllowed = true;
            $decisionReason = __('permission-toolkit::messages.sim_reason_direct_permission');
        } else {
            $steps[] = [
                'step' => 'Direct Permission',
                'status' => 'FAIL',
                'detail' => __('permission-toolkit::messages.sim_step_detail_direct_fail', [
                    'ability' => $ability,
                ]),
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
                'detail' => __('permission-toolkit::messages.sim_step_detail_role_pass', [
                    'ability' => $ability,
                    'roles' => $rolesList,
                ]),
            ];
            if (! $isAllowed) {
                $isAllowed = true;
                $decisionReason = __('permission-toolkit::messages.sim_reason_role_inheritance', [
                    'roles' => $rolesList,
                ]);
            }
        } else {
            $currentRoles = empty($userRoles) ? __('permission-toolkit::messages.sim_none') : implode(', ', $userRoles);
            $steps[] = [
                'step' => 'Role Permission Inheritance',
                'status' => 'FAIL',
                'detail' => __('permission-toolkit::messages.sim_step_detail_role_fail', [
                    'roles' => $currentRoles,
                    'ability' => $ability,
                ]),
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
                    'detail' => __('permission-toolkit::messages.sim_step_detail_policy_pass', [
                        'ability' => $ability,
                        'target' => $targetDesc,
                    ]),
                ];
                $isAllowed = true;
                $decisionReason = __('permission-toolkit::messages.sim_reason_policy_allowed', [
                    'target' => $targetDesc,
                ]);
            } else {
                $policyMsg = $gateResponse->message() ?: __('permission-toolkit::messages.sim_policy_forbidden');
                $steps[] = [
                    'step' => 'Policy / Gate Evaluation',
                    'status' => 'FAIL',
                    'detail' => __('permission-toolkit::messages.sim_step_detail_policy_fail', [
                        'message' => $policyMsg,
                    ]),
                ];
                $isAllowed = false;
                $decisionReason = __('permission-toolkit::messages.sim_reason_policy_denied', [
                    'message' => $gateResponse->message() ?: __('permission-toolkit::messages.sim_policy_refusal'),
                ]);
            }
        } else {
            $steps[] = [
                'step' => 'Policy / Gate Evaluation',
                'status' => 'SKIP',
                'detail' => __('permission-toolkit::messages.sim_step_detail_policy_skip'),
            ];
        }

        if (! $isAllowed && empty($decisionReason)) {
            $decisionReason = __('permission-toolkit::messages.sim_reason_no_grant', [
                'ability' => $ability,
            ]);
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
