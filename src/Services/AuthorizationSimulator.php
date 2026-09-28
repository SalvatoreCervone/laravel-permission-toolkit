<?php

namespace SalvatoreCervone\PermissionToolkit\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use SalvatoreCervone\PermissionToolkit\PermissionToolkit;

class AuthorizationSimulator
{
    /**
     * Run a comprehensive diagnostic simulation of an authorization check.
     */
    public function simulate(
        Authenticatable|Model $user,
        string $ability,
        mixed $target = null,
        int|string|null $teamId = null
    ): array {
        $steps = [];
        $isAllowed = false;
        $decisionReason = '';

        // Handle Spatie Teams context if provided
        $previousTeamId = null;
        $registrar = null;
        if ($teamId !== null && class_exists('Spatie\Permission\PermissionRegistrar')) {
            $registrar = app('Spatie\Permission\PermissionRegistrar');
            if (method_exists($registrar, 'getPermissionsTeamId')) {
                $previousTeamId = $registrar->getPermissionsTeamId();
                $registrar->setPermissionsTeamId($teamId);
            }
        }

        try {
            // Step 1: User Identity Verification
            $userClass = get_class($user);
            $userId = $user->getAuthIdentifier();
            $userName = PermissionToolkit::getUserDisplayName($user);

        $identityDetail = __('permission-toolkit::messages.sim_step_detail_identity', [
            'class' => $userClass,
            'id' => $userId,
            'name' => $userName,
        ]);

        if ($teamId !== null) {
            $identityDetail .= " • Team #{$teamId}";
        }

        $steps[] = [
            'step' => 'User Identity',
            'status' => 'PASS',
            'detail' => $identityDetail,
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
        } elseif (Gate::has($ability)) {
            $gateResponse = Gate::forUser($user)->inspect($ability);

            if ($gateResponse->allowed()) {
                $steps[] = [
                    'step' => 'Policy / Gate Evaluation',
                    'status' => 'PASS',
                    'detail' => __('permission-toolkit::messages.sim_step_detail_gate_pass', [
                        'ability' => $ability,
                    ]),
                ];
                $isAllowed = true;
                $decisionReason = __('permission-toolkit::messages.sim_reason_gate_allowed', [
                    'ability' => $ability,
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
        } finally {
            if ($teamId !== null && $registrar && method_exists($registrar, 'setPermissionsTeamId')) {
                $registrar->setPermissionsTeamId($previousTeamId);
            }
        }
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
                'name' => PermissionToolkit::getUserDisplayName($user),
                'roles' => method_exists($user, 'getRoleNames') ? $user->getRoleNames()->toArray() : [],
            ],
            'steps' => $steps,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Run a reverse diagnostic simulation: find which users have a specific permission or role,
     * and trace the exact path granting access (direct, role inheritance, or super admin bypass).
     */
    public function reverseSimulate(string $target, string $type = 'permission', int|string|null $teamId = null): array
    {
        $userModelClass = config('permission-toolkit.user_model')
            ?? config('auth.providers.users.model', 'App\\Models\\User');

        if (! class_exists($userModelClass)) {
            return [
                'target' => $target,
                'type' => $type,
                'authorized_users' => [],
                'stats' => ['total' => 0, 'direct' => 0, 'role' => 0, 'super_admin' => 0],
            ];
        }

        $superAdminConfig = config('permission-toolkit.super_admin', []);
        $superAdminEnabled = $superAdminConfig['enabled'] ?? true;
        $superAdminRoles = (array) ($superAdminConfig['role_name'] ?? ['super-admin', 'Super Admin']);

        // Handle Spatie Teams context if provided
        $previousTeamId = null;
        $registrar = null;
        if ($teamId !== null && class_exists('Spatie\Permission\PermissionRegistrar')) {
            $registrar = app('Spatie\Permission\PermissionRegistrar');
            if (method_exists($registrar, 'getPermissionsTeamId')) {
                $previousTeamId = $registrar->getPermissionsTeamId();
                $registrar->setPermissionsTeamId($teamId);
            }
        }

        try {
            $allUsers = (new $userModelClass)->newQuery()->with(['roles', 'permissions'])->get();
            $authorizedUsers = [];
            $directCount = 0;
            $roleCount = 0;
            $superAdminCount = 0;

            foreach ($allUsers as $user) {
                $userRoleNames = method_exists($user, 'getRoleNames')
                    ? $user->getRoleNames()->toArray()
                    : ($user->roles ? $user->roles->pluck('name')->toArray() : []);

                $hasSuperAdmin = false;
                $matchingSuperAdmin = null;
                if ($superAdminEnabled) {
                    $matchingSuperAdmin = $this->findMatchingSuperAdminRole($user, $superAdminRoles, $userRoleNames);
                    if ($matchingSuperAdmin !== null) {
                        $hasSuperAdmin = true;
                    }
                }

                if ($type === 'role') {
                    $hasRole = in_array($target, $userRoleNames, true);
                    if ($hasRole || $hasSuperAdmin) {
                        if ($hasRole) {
                            $roleCount++;
                        }
                        if ($hasSuperAdmin && ! $hasRole) {
                            $superAdminCount++;
                        }
                        $authorizedUsers[] = [
                            'user' => [
                                'id' => $user->getAuthIdentifier(),
                                'name' => PermissionToolkit::getUserDisplayName($user),
                                'email' => $user->email ?? 'N/D',
                                'roles' => $userRoleNames,
                            ],
                            'is_direct' => false,
                            'grant_type' => $hasRole ? 'ROLE' : 'SUPER_ADMIN',
                            'roles_granting' => $hasRole ? [$target] : [],
                            'super_admin_bypass' => $hasSuperAdmin,
                            'reason' => $hasRole
                                ? __('permission-toolkit::messages.sim_reason_role', ['role' => $target])
                                : __('permission-toolkit::messages.sim_reason_super_admin', ['role' => $matchingSuperAdmin]),
                        ];
                    }
                } else {
                    // Type is permission / ability
                    $hasDirect = false;
                    if ($user->permissions) {
                        $hasDirect = $user->permissions->contains('name', $target) || $user->permissions->contains('id', $target);
                    }

                    $grantingRoles = [];
                    if ($user->roles) {
                        foreach ($user->roles as $role) {
                            if (method_exists($role, 'hasPermissionTo') && $role->hasPermissionTo($target)) {
                                $grantingRoles[] = $role->name;
                            }
                        }
                    }

                    $isAuthorized = $hasDirect || count($grantingRoles) > 0 || $hasSuperAdmin;

                    if ($isAuthorized) {
                        if ($hasDirect) {
                            $directCount++;
                        }
                        if (count($grantingRoles) > 0) {
                            $roleCount++;
                        }
                        if ($hasSuperAdmin && ! $hasDirect && empty($grantingRoles)) {
                            $superAdminCount++;
                        }

                        $reasons = [];
                        if ($hasSuperAdmin) {
                            $reasons[] = __('permission-toolkit::messages.sim_step_super_admin') . " ({$matchingSuperAdmin})";
                        }
                        if ($hasDirect) {
                            $reasons[] = __('permission-toolkit::messages.sim_step_direct_permission');
                        }
                        if (! empty($grantingRoles)) {
                            $reasons[] = __('permission-toolkit::messages.sim_step_role_inheritance') . ' (' . implode(', ', $grantingRoles) . ')';
                        }

                        $grantType = 'ROLE';
                        if ($hasDirect && ! empty($grantingRoles)) {
                            $grantType = 'BOTH';
                        } elseif ($hasDirect) {
                            $grantType = 'DIRECT';
                        } elseif ($hasSuperAdmin && empty($grantingRoles)) {
                            $grantType = 'SUPER_ADMIN';
                        }

                        $authorizedUsers[] = [
                            'user' => [
                                'id' => $user->getAuthIdentifier(),
                                'name' => PermissionToolkit::getUserDisplayName($user),
                                'email' => $user->email ?? 'N/D',
                                'roles' => $userRoleNames,
                            ],
                            'is_direct' => $hasDirect,
                            'grant_type' => $grantType,
                            'roles_granting' => $grantingRoles,
                            'super_admin_bypass' => $hasSuperAdmin,
                            'reason' => implode(' + ', $reasons),
                        ];
                    }
                }
            }

            return [
                'target' => $target,
                'type' => $type,
                'total_scanned' => $allUsers->count(),
                'authorized_users' => $authorizedUsers,
                'stats' => [
                    'total' => count($authorizedUsers),
                    'direct' => $directCount,
                    'role' => $roleCount,
                    'super_admin' => $superAdminCount,
                ],
                'timestamp' => now()->toIso8601String(),
            ];
        } finally {
            if ($previousTeamId !== null && isset($registrar) && method_exists($registrar, 'setPermissionsTeamId')) {
                $registrar->setPermissionsTeamId($previousTeamId);
            }
        }
    }
}
