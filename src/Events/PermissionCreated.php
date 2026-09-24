<?php

namespace SalvatoreCervone\PermissionToolkit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spatie\Permission\Contracts\Permission;

class PermissionCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Permission $permission,
        public ?array $causer = null
    ) {}
}
