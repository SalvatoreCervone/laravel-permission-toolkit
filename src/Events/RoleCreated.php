<?php

namespace SalvatoreCervone\PermissionToolkit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spatie\Permission\Contracts\Role;

class RoleCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Role $role,
        public ?array $causer = null
    ) {}
}
