<?php

namespace SalvatoreCervone\PermissionToolkit\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use SalvatoreCervone\PermissionToolkit\PermissionToolkit;
use SalvatoreCervone\PermissionToolkit\Tests\TestCase;
use SalvatoreCervone\PermissionToolkit\Tests\User;
use Spatie\Permission\Models\Role;

class AuthorizationGateTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Regular User',
            'email' => 'regular@test.com',
        ]);
    }

    /** @test */
    public function it_denies_access_when_gate_returns_false()
    {
        Gate::define('viewPermissionToolkit', fn ($user) => false);

        $response = $this->actingAs($this->user)->get('/permission-manager/matrix');

        $response->assertStatus(403);
    }

    /** @test */
    public function it_allows_access_when_gate_returns_true()
    {
        Gate::define('viewPermissionToolkit', fn ($user) => true);

        $response = $this->actingAs($this->user)->get('/permission-manager/matrix');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_respects_custom_auth_callback()
    {
        PermissionToolkit::auth(function ($request) {
            return $request->user()?->email === 'special@admin.com';
        });

        // Regular user should be denied
        $response = $this->actingAs($this->user)->get('/permission-manager/matrix');
        $response->assertStatus(403);

        // Special user should be allowed
        $specialUser = User::create([
            'name' => 'Special Admin',
            'email' => 'special@admin.com',
        ]);

        $response = $this->actingAs($specialUser)->get('/permission-manager/matrix');
        $response->assertStatus(200);

        // Reset callback to avoid side effects
        PermissionToolkit::auth(function () {
            return true;
        });
    }

    /** @test */
    public function it_protects_super_admin_role_from_deletion()
    {
        $role = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        $response = $this->actingAs($this->user)->deleteJson("/permission-manager/roles/{$role->id}");

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => __('permission-toolkit::messages.cannot_delete_super_admin_role'),
        ]);

        $this->assertDatabaseHas('roles', ['name' => 'super-admin']);
    }

    /** @test */
    public function it_prevents_authenticated_user_from_deleting_their_own_role()
    {
        $role = Role::create(['name' => 'manager', 'guard_name' => 'web']);
        $this->user->assignRole($role);

        $response = $this->actingAs($this->user)->deleteJson("/permission-manager/roles/{$role->id}");

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => __('permission-toolkit::messages.cannot_delete_own_role'),
        ]);

        $this->assertDatabaseHas('roles', ['name' => 'manager']);
    }
}
