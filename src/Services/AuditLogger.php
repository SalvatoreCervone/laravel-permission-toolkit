<?php

namespace SalvatoreCervone\PermissionToolkit\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use SalvatoreCervone\PermissionToolkit\Models\PermissionAuditLog;

class AuditLogger
{
    /**
     * Determine if audit logging is active.
     */
    public static function isEnabled(): bool
    {
        return config('permission-toolkit.audit.enabled', true);
    }

    /**
     * Record a role or permission modification event.
     */
    public static function log(
        Model $targetUser,
        string $action,
        string $type,
        string $targetName,
        ?Model $causer = null,
        ?int $teamId = null,
        array $metadata = []
    ): ?PermissionAuditLog {
        if (! static::isEnabled()) {
            return null;
        }

        $tableName = config('permission-toolkit.audit.table', 'permission_audit_logs');
        if (! Schema::hasTable($tableName)) {
            return null;
        }

        $actor = $causer ?? Auth::user();

        return PermissionAuditLog::create([
            'causer_id' => $actor?->getKey(),
            'causer_type' => $actor ? get_class($actor) : null,
            'user_id' => $targetUser->getKey(),
            'user_type' => get_class($targetUser),
            'action' => $action,
            'type' => $type,
            'target_name' => $targetName,
            'team_id' => $teamId,
            'ip_address' => Request::ip(),
            'user_agent' => substr(Request::userAgent() ?? '', 0, 500),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
