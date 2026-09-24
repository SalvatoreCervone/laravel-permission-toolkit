<?php

namespace SalvatoreCervone\PermissionToolkit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoleDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $roleName,
        public string|int $roleId,
        public ?array $causer = null
    ) {}
}
