<?php

namespace SalvatoreCervone\PermissionToolkit\Tests\Feature;

use Illuminate\Support\Facades\Hash;
use SalvatoreCervone\PermissionToolkit\Models\PermissionAuditLog;
use SalvatoreCervone\PermissionToolkit\Tests\TestCase;
use SalvatoreCervone\PermissionToolkit\Tests\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserAccessFilterAndDeleteTest extends TestCase
{
    protected User $adminUser;
    protected User $editorUser;
    protected User $regularUser;
    protected Role $adminRole;
    protected Role $editorRole;
    protected Permission $editPostsPerm;
    protected Permission $deletePostsPerm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $this->editorRole = Role::create(['name' => 'editor', 'guard_name' => 'web']);

        $this->editPostsPerm = Permission::create(['name' => 'posts.edit', 'guard_name' => 'web']);
        $this->deletePostsPerm = Permission::create(['name' => 'posts.delete', 'guard_name' => 'web']);

        $this->editorRole->givePermissionTo($this->editPostsPerm);

        $this->adminUser = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->adminUser->assignRole($this->adminRole);

        $this->editorUser = User::create([
            'name' => 'Jane Editor',
            'email' => 'editor@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->editorUser->assignRole($this->editorRole);

        $this->regularUser = User::create([
            'name' => 'Bob Regular',
            'email' => 'regular@example.com',
            'password' => Hash::make('password'),
        ]);
        // Give Bob a direct permission
        $this->regularUser->givePermissionTo($this->deletePostsPerm);
    }

    /** @test */
    public function it_can_filter_users_by_role()
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/permission-manager/users?role=editor');

        $response->assertStatus(200);
        $response->assertSee('Jane Editor');
        $response->assertDontSee('Bob Regular');
        $response->assertSee('Filtro Ruolo: editor');
    }

    /** @test */
    public function it_can_filter_users_by_permission_inherited_via_role()
    {
        // Jane has 'posts.edit' through the editor role
        $response = $this->actingAs($this->adminUser)
            ->get('/permission-manager/users?permission=posts.edit');

        $response->assertStatus(200);
        $response->assertSee('Jane Editor');
        $response->assertDontSee('Bob Regular');
        $response->assertSee('via editor');
    }

    /** @test */
    public function it_can_filter_users_by_direct_permission()
    {
        // Bob has 'posts.delete' directly assigned
        $response = $this->actingAs($this->adminUser)
            ->get('/permission-manager/users?permission=posts.delete');

        $response->assertStatus(200);
        $response->assertSee('Bob Regular');
        $response->assertDontSee('Jane Editor');
        $response->assertSee('Diretto');
    }

    /** @test */
    public function it_soft_deletes_user_and_shows_them_as_deactivated()
    {
        // Delete Bob
        $response = $this->actingAs($this->adminUser)
            ->delete("/permission-manager/users/{$this->regularUser->id}");

        $response->assertRedirect('/permission-manager/users');
        $response->assertSessionHas('status');

        $this->assertSoftDeleted('users', ['id' => $this->regularUser->id]);

        // Visit users page - should show Bob as Disattivato
        $indexResponse = $this->actingAs($this->adminUser)
            ->get('/permission-manager/users');

        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Bob Regular');
        $indexResponse->assertSee('Disattivato');
        $indexResponse->assertSee('Ripristina');
        $indexResponse->assertSee('Elimina Definitivo');

        // Audit log was recorded
        $log = PermissionAuditLog::where('user_id', $this->regularUser->id)
            ->where('action', 'deactivated')
            ->first();

        $this->assertNotNull($log);
        $this->assertTrue($log->metadata['soft_delete']);
    }

    /** @test */
    public function it_can_restore_a_soft_deleted_user()
    {
        $this->regularUser->delete();
        $this->assertTrue($this->regularUser->fresh()->trashed());

        $response = $this->actingAs($this->adminUser)
            ->post("/permission-manager/users/{$this->regularUser->id}/restore");

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertFalse($this->regularUser->fresh()->trashed());

        // Audit log
        $log = PermissionAuditLog::where('user_id', $this->regularUser->id)
            ->where('action', 'restored')
            ->first();

        $this->assertNotNull($log);
    }

    /** @test */
    public function it_can_permanently_force_delete_a_user()
    {
        $this->regularUser->delete();

        $response = $this->actingAs($this->adminUser)
            ->delete("/permission-manager/users/{$this->regularUser->id}/force");

        $response->assertRedirect('/permission-manager/users');
        $response->assertSessionHas('status');

        $this->assertDatabaseMissing('users', ['id' => $this->regularUser->id]);

        // Audit log
        $log = PermissionAuditLog::where('user_id', $this->regularUser->id)
            ->where('action', 'force_deleted')
            ->first();

        $this->assertNotNull($log);
    }

    /** @test */
    public function it_prevents_user_from_deleting_their_own_account()
    {
        $response = $this->actingAs($this->adminUser)
            ->delete("/permission-manager/users/{$this->adminUser->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertFalse($this->adminUser->fresh()->trashed());
    }

    /** @test */
    public function it_filters_users_by_soft_delete_status()
    {
        $this->regularUser->delete();

        // 1. Only active
        $activeResponse = $this->actingAs($this->adminUser)
            ->get('/permission-manager/users?status=active');
        $activeResponse->assertSee('Jane Editor');
        $activeResponse->assertDontSee('Bob Regular');

        // 2. Only trashed
        $trashedResponse = $this->actingAs($this->adminUser)
            ->get('/permission-manager/users?status=trashed');
        $trashedResponse->assertSee('Bob Regular');
        $trashedResponse->assertDontSee('Jane Editor');
    }

    /** @test */
    public function matrix_view_contains_quick_links_to_users_by_role_and_permission()
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/permission-manager/matrix');

        $response->assertStatus(200);
        $response->assertSee('/permission-manager/users?role=editor');
        $response->assertSee('/permission-manager/users?permission=posts.edit');
    }

    /** @test */
    public function it_can_reverse_simulate_permission_lookup_in_service()
    {
        $simulator = app(\SalvatoreCervone\PermissionToolkit\Services\AuthorizationSimulator::class);

        // Bob has direct permission posts.delete
        $result = $simulator->reverseSimulate('posts.delete', 'permission');

        $this->assertEquals('posts.delete', $result['target']);
        $this->assertEquals('permission', $result['type']);
        $this->assertGreaterThanOrEqual(1, $result['stats']['total']);
        $this->assertEquals(1, $result['stats']['direct']);

        $authorizedIds = collect($result['authorized_users'])->pluck('user.id')->all();
        $this->assertContains($this->regularUser->id, $authorizedIds);

        // Jane has posts.edit via editor role
        $editResult = $simulator->reverseSimulate('posts.edit', 'permission');
        $this->assertGreaterThanOrEqual(1, $editResult['stats']['role']);
        $editAuthorizedIds = collect($editResult['authorized_users'])->pluck('user.id')->all();
        $this->assertContains($this->editorUser->id, $editAuthorizedIds);
    }

    /** @test */
    public function it_can_reverse_simulate_role_lookup_in_service()
    {
        $simulator = app(\SalvatoreCervone\PermissionToolkit\Services\AuthorizationSimulator::class);

        $result = $simulator->reverseSimulate('editor', 'role');

        $this->assertEquals('editor', $result['target']);
        $this->assertEquals('role', $result['type']);
        $this->assertGreaterThanOrEqual(1, $result['stats']['total']);

        $authorizedIds = collect($result['authorized_users'])->pluck('user.id')->all();
        $this->assertContains($this->editorUser->id, $authorizedIds);
    }

    /** @test */
    public function it_can_access_simulator_reverse_lookup_web_ui()
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/permission-manager/simulator?mode=reverse&reverse_type=permission&reverse_target=posts.edit');

        $response->assertStatus(200);
        $response->assertSee('Reverse Lookup Accessi');
        $response->assertSee('posts.edit');
        $response->assertSee('Jane Editor');
        $response->assertSee('via editor');
    }
}
