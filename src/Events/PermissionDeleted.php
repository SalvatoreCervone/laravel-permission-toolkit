<?php

namespace SalvatoreCervone\PermissionToolkit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PermissionDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $permissionName,
        public string|int $permissionId,
        public ?array $causer = null
    ) {}
}
