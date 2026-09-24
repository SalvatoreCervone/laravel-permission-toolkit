<?php

namespace SalvatoreCervone\PermissionToolkit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Contracts\Role;

class PermissionToggled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Role $role,
        public Permission $permission,
        public string $action, // 'assigned' | 'revoked'
        public ?array $causer = null
    ) {}
}
