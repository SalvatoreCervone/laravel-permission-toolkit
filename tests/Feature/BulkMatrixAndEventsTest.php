<?php

namespace SalvatoreCervone\PermissionToolkit\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use SalvatoreCervone\PermissionToolkit\Events\AuditLogsPruned;
use SalvatoreCervone\PermissionToolkit\Events\PermissionCreated;
use SalvatoreCervone\PermissionToolkit\Events\PermissionDeleted;
use SalvatoreCervone\PermissionToolkit\Events\PermissionsExported;
use SalvatoreCervone\PermissionToolkit\Events\PermissionsImported;
use SalvatoreCervone\PermissionToolkit\Events\PermissionToggled;
use SalvatoreCervone\PermissionToolkit\Events\RoleCreated;
use SalvatoreCervone\PermissionToolkit\Events\RoleDeleted;
use SalvatoreCervone\PermissionToolkit\Events\UserAccessUpdated;
use SalvatoreCervone\PermissionToolkit\Services\AuthorizationSimulator;
use SalvatoreCervone\PermissionToolkit\Tests\TestCase;
use SalvatoreCervone\PermissionToolkit\Tests\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class BulkMatrixAndEventsTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Super User',
            'email' => 'super@test.com',
            'password' => Hash::make('password123'),
        ]);
    }

    /** @test */
    public function it_can_bulk_assign_and_revoke_permissions_in_matrix()
    {
        Event::fake([PermissionToggled::class]);

        $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);
        $p1 = Permission::create(['name' => 'articles.create', 'guard_name' => 'web']);
        $p2 = Permission::create(['name' => 'articles.edit', 'guard_name' => 'web']);

        // Bulk Assign
        $response = $this->actingAs($this->user)->postJson(route('permission-toolkit.matrix.bulk-toggle'), [
            'role_id' => $role->id,
            'permission_ids' => [$p1->id, $p2->id],
            'action' => 'assign',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertTrue($role->fresh()->hasPermissionTo('articles.create'));
        $this->assertTrue($role->fresh()->hasPermissionTo('articles.edit'));

        Event::assertDispatched(PermissionToggled::class, 2);

        // Bulk Revoke
        $responseRevoke = $this->actingAs($this->user)->postJson(route('permission-toolkit.matrix.bulk-toggle'), [
            'role_id' => $role->id,
            'permission_ids' => [$p1->id, $p2->id],
            'action' => 'revoke',
        ]);

        $responseRevoke->assertStatus(200);
        $responseRevoke->assertJson(['success' => true]);
        $this->assertFalse($role->fresh()->hasPermissionTo('articles.create'));
        $this->assertFalse($role->fresh()->hasPermissionTo('articles.edit'));
    }

    /** @test */
    public function it_dispatches_domain_events_for_crud_operations()
    {
        Event::fake([
            RoleCreated::class,
            RoleDeleted::class,
            PermissionCreated::class,
            PermissionDeleted::class,
            PermissionToggled::class,
            UserAccessUpdated::class,
        ]);

        // 1. Role Create
        $this->actingAs($this->user)->post(route('permission-toolkit.roles.store'), [
            'name' => 'Auditor',
            'guard_name' => 'web',
        ]);
        Event::assertDispatched(RoleCreated::class, function ($e) {
            return $e->role->name === 'Auditor';
        });

        $role = Role::where('name', 'Auditor')->first();

        // 2. Permission Create
        $this->actingAs($this->user)->post(route('permission-toolkit.permissions.store'), [
            'name' => 'audit.view',
            'guard_name' => 'web',
        ]);
        Event::assertDispatched(PermissionCreated::class, function ($e) {
            return $e->permission->name === 'audit.view';
        });

        $perm = Permission::where('name', 'audit.view')->first();

        // 3. Matrix Single Toggle
        $this->actingAs($this->user)->postJson(route('permission-toolkit.matrix.toggle'), [
            'role_id' => $role->id,
            'permission_id' => $perm->id,
            'assigned' => true,
        ]);
        Event::assertDispatched(PermissionToggled::class, function ($e) use ($role, $perm) {
            return $e->role->id === $role->id && $e->permission->id === $perm->id && $e->action === 'assigned';
        });

        // 4. User Access Update
        $this->actingAs($this->user)->put(route('permission-toolkit.users.update', $this->user->id), [
            'roles' => [$role->name],
            'direct_permissions' => [$perm->name],
        ]);
        Event::assertDispatched(UserAccessUpdated::class, function ($e) {
            return $e->user->id === $this->user->id;
        });

        // 5. Role Delete (guardrail prevents deleting own role, so create another role to delete)
        $roleToDelete = Role::create(['name' => 'TemporaryRole', 'guard_name' => 'web']);
        $this->actingAs($this->user)->delete(route('permission-toolkit.roles.destroy', $roleToDelete->id));
        Event::assertDispatched(RoleDeleted::class, function ($e) use ($roleToDelete) {
            return $e->roleId == $roleToDelete->id;
        });

        // 6. Permission Delete
        $this->actingAs($this->user)->delete(route('permission-toolkit.permissions.destroy', $perm->id));
        Event::assertDispatched(PermissionDeleted::class, function ($e) use ($perm) {
            return $e->permissionId == $perm->id;
        });
    }

    /** @test */
    public function it_can_export_permissions_and_roles_via_web_stream()
    {
        Event::fake([PermissionsExported::class]);

        Role::create(['name' => 'Manager', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage.reports', 'guard_name' => 'web']);

        $response = $this->actingAs($this->user)->get(route('permission-toolkit.export'));

        $response->assertStatus(200);
        $this->assertStringContainsString('permissions_export_', $response->headers->get('content-disposition'));

        Event::assertDispatched(PermissionsExported::class);
    }

    /** @test */
    public function it_can_import_permissions_via_web_upload()
    {
        Event::fake([PermissionsImported::class]);

        $payload = [
            'exported_at' => now()->toISOString(),
            'roles' => [
                [
                    'name' => 'Inspector',
                    'guard_name' => 'web',
                    'permissions' => ['inspect.site'],
                ],
            ],
            'direct_permissions' => [],
        ];

        $file = UploadedFile::fake()->createWithContent('backup.json', json_encode($payload));

        $response = $this->actingAs($this->user)->post(route('permission-toolkit.import'), [
            'file' => $file,
            'fresh' => '0',
        ]);

        $response->assertRedirect(route('permission-toolkit.matrix'));
        $this->assertDatabaseHas('roles', ['name' => 'Inspector']);
        $this->assertDatabaseHas('permissions', ['name' => 'inspect.site']);

        Event::assertDispatched(PermissionsImported::class);
    }

    /** @test */
    public function it_can_render_embeddable_blade_component()
    {
        Role::create(['name' => 'Staff', 'guard_name' => 'web']);
        Permission::create(['name' => 'staff.access', 'guard_name' => 'web']);

        $html = Blade::render('<x-permission-toolkit-matrix />');

        $this->assertStringContainsString('permission-matrix-widget', $html);
        $this->assertStringContainsString('Staff', $html);
        $this->assertStringContainsString('staff.access', $html);
    }

    /** @test */
    public function it_supports_spatie_teams_in_simulator()
    {
        $simulator = app(AuthorizationSimulator::class);

        $result = $simulator->simulate($this->user, 'view-dashboard', null, 'team-99');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('steps', $result);
        $this->assertStringContainsString('Team #team-99', $result['steps'][0]['detail']);
    }
}
