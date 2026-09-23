<?php

namespace SalvatoreCervone\PermissionToolkit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PermissionAuditLog extends Model
{
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
