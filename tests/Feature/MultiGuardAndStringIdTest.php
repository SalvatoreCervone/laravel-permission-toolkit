<?php

namespace SalvatoreCervone\PermissionToolkit\Tests\Feature;

use SalvatoreCervone\PermissionToolkit\Tests\TestCase;
use SalvatoreCervone\PermissionToolkit\Tests\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MultiGuardAndStringIdTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@guard.com',
        ]);
    }

    /** @test */
    public function it_rejects_cross_guard_toggle_with_clean_422_response()
    {
        $roleWeb = Role::create(['name' => 'web-role', 'guard_name' => 'web']);
        $permApi = Permission::create(['name' => 'api-perm', 'guard_name' => 'api']);

        $response = $this->actingAs($this->user)->postJson('/permission-manager/matrix/toggle', [
            'role_id' => $roleWeb->id,
            'permission_id' => $permApi->id,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $response->assertJsonFragment([
            'message' => __('permission-toolkit::messages.guard_mismatch_error', [
                'role' => 'web-role',
                'role_guard' => 'web',
                'permission' => 'api-perm',
                'perm_guard' => 'api',
            ]),
        ]);
    }

    /** @test */
    public function it_can_filter_matrix_by_guard()
    {
        Role::create(['name' => 'web-role', 'guard_name' => 'web']);
        Role::create(['name' => 'api-role', 'guard_name' => 'api']);
        Permission::create(['name' => 'web-perm', 'guard_name' => 'web']);
        Permission::create(['name' => 'api-perm', 'guard_name' => 'api']);

        // Query with guard=api
        $response = $this->actingAs($this->user)->get('/permission-manager/matrix?guard=api');

        $response->assertStatus(200);
        $response->assertSee('api-role');
        $response->assertDontSee('web-role');
    }
}
