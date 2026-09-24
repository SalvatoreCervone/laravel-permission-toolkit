<?php

namespace SalvatoreCervone\PermissionToolkit\Events;

use Illuminate\Foundation\Events\Dispatchable;

class PermissionsExported
{
    use Dispatchable;

    public function __construct(
        public int $rolesCount,
        public int $permissionsCount,
        public ?string $filePath = null
    ) {}
}
