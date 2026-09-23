<?php

namespace SalvatoreCervone\PermissionToolkit\Traits;

use SalvatoreCervone\PermissionToolkit\Services\AuditLogger;

trait LogsPermissionChanges
{
    /**
     * Assign the given role to the model and log to audit trail.
     */
    public function assignRoleWithAudit(...$roles)
    {
        $result = parent::assignRole(...$roles);

        foreach ($roles as $role) {
            $roleName = is_string($role) ? $role : ($role->name ?? '');
            AuditLogger::log(
                targetUser: $this,
                action: 'assigned',
                type: 'role',
                targetName: $roleName
            );
        }

        return $result;
    }

    /**
     * Remove the given role from the model and log to audit trail.
     */
    public function removeRoleWithAudit($role)
    {
        $roleName = is_string($role) ? $role : ($role->name ?? '');
        $result = parent::removeRole($role);

        AuditLogger::log(
            targetUser: $this,
            action: 'revoked',
            type: 'role',
            targetName: $roleName
        );

        return $result;
    }

    /**
     * Grant the given permission to the model and log to audit trail.
     */
    public function givePermissionToWithAudit(...$permissions)
    {
        $result = parent::givePermissionTo(...$permissions);

        foreach ($permissions as $permission) {
            $permName = is_string($permission) ? $permission : ($permission->name ?? '');
            AuditLogger::log(
                targetUser: $this,
                action: 'assigned',
                type: 'permission',
                targetName: $permName
            );
        }

        return $result;
    }

    /**
     * Revoke the given permission from the model and log to audit trail.
     */
    public function revokePermissionToWithAudit($permission)
    {
        $permName = is_string($permission) ? $permission : ($permission->name ?? '');
        $result = parent::revokePermissionTo($permission);

        AuditLogger::log(
            targetUser: $this,
            action: 'revoked',
            type: 'permission',
            targetName: $permName
        );

        return $result;
    }
}
