<?php

namespace SalvatoreCervone\PermissionToolkit\Tests\Feature;

use SalvatoreCervone\PermissionToolkit\Services\AuthorizationSimulator;
use SalvatoreCervone\PermissionToolkit\Tests\TestCase;
use SalvatoreCervone\PermissionToolkit\Tests\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SimulatorTest extends TestCase
{
    /** @test */
    public function it_denies_user_with_no_permissions()
    {
        $user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $simulator = new AuthorizationSimulator();

        $result = $simulator->simulate($user, 'invoices.create');

        $this->assertFalse($result['is_allowed']);
        $this->assertEquals('DENIED', $result['verdict']);
    }

    /** @test */
    public function it_allows_user_with_direct_permission()
    {
        $user = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
        $permission = Permission::create(['name' => 'invoices.create']);

        $user->givePermissionTo($permission);

        $simulator = new AuthorizationSimulator();
        $result = $simulator->simulate($user, 'invoices.create');

        $this->assertTrue($result['is_allowed']);
        $this->assertEquals('ALLOWED', $result['verdict']);
    }

    /** @test */
    public function it_allows_user_via_role_inheritance()
    {
        $user = User::create(['name' => 'Bob', 'email' => 'bob@example.com']);
        $role = Role::create(['name' => 'Accountant']);
        $permission = Permission::create(['name' => 'reports.view']);

        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $simulator = new AuthorizationSimulator();
        $result = $simulator->simulate($user, 'reports.view');

        $this->assertTrue($result['is_allowed']);
        $this->assertEquals('ALLOWED', $result['verdict']);
    }

    /** @test */
    public function it_allows_super_admin_bypass()
    {
        $user = User::create(['name' => 'Boss', 'email' => 'admin@example.com']);
        $superAdminRole = Role::create(['name' => 'super-admin']);

        $user->assignRole($superAdminRole);

        $simulator = new AuthorizationSimulator();
        $result = $simulator->simulate($user, 'any.restricted.action');

        $this->assertTrue($result['is_allowed']);
        $this->assertStringContainsString('super-admin', $result['reason']);
    }

    /** @test */
    public function it_allows_super_admin_bypass_with_spaced_role_name()
    {
        $user = User::create(['name' => 'Big Boss', 'email' => 'bigboss@example.com']);
        $superAdminRole = Role::create(['name' => 'Super Admin']);

        $user->assignRole($superAdminRole);

        $simulator = new AuthorizationSimulator();
        $result = $simulator->simulate($user, 'any.restricted.action');

        $this->assertTrue($result['is_allowed']);
        $this->assertStringContainsString('Super Admin', $result['reason']);
    }
}
