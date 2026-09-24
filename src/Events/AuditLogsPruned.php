<?php

namespace SalvatoreCervone\PermissionToolkit\Events;

use Illuminate\Foundation\Events\Dispatchable;

class AuditLogsPruned
{
    use Dispatchable;

    public function __construct(
        public int $prunedCount,
        public int $days
    ) {}
}
