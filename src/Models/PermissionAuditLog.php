<?php

namespace SalvatoreCervone\PermissionToolkit\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PermissionAuditLog extends Model
{
    use MassPrunable;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function getTable()
    {
        return config('permission-toolkit.audit.table', 'permission_audit_logs');
    }

    /**
     * Get the prunable model query.
     */
    public function prunable(): Builder
    {
        $days = config('permission-toolkit.audit.retention_days', 90);

        if ($days === null || (int) $days <= 0) {
            // Indefinite retention: never prune any records
            return static::whereRaw('1 = 0');
        }

        return static::where('created_at', '<=', now()->subDays((int) $days));
    }

    /**
     * The user or entity who performed the modification.
     */
    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The user whose roles or permissions were altered.
     */
    public function user(): MorphTo
    {
        return $this->morphTo();
    }
}
