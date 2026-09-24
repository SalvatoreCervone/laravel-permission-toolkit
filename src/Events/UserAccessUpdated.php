<?php

namespace SalvatoreCervone\PermissionToolkit\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserAccessUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Authenticatable|Model $user,
        public array $addedRoles,
        public array $removedRoles,
        public array $addedPermissions,
        public array $removedPermissions,
        public ?array $causer = null
    ) {}
}
