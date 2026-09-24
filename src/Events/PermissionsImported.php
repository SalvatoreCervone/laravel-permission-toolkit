<?php

namespace SalvatoreCervone\PermissionToolkit\Events;

use Illuminate\Foundation\Events\Dispatchable;

class PermissionsImported
{
    use Dispatchable;

    public function __construct(
        public int $rolesCount,
        public int $permissionsCount,
        public bool $fresh = false
    ) {}
}
