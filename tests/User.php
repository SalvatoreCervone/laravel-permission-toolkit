<?php

namespace SalvatoreCervone\PermissionToolkit\Tests;

use Illuminate\Foundation\Auth\User as Authenticatable;
use SalvatoreCervone\PermissionToolkit\Traits\LogsPermissionChanges;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles, LogsPermissionChanges;

    protected $guarded = [];

    protected $table = 'users';
}
