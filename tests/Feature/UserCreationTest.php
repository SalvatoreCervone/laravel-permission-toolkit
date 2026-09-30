<?php

namespace SalvatoreCervone\PermissionToolkit\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use SalvatoreCervone\PermissionToolkit\Events\UserAccessUpdated;
use SalvatoreCervone\PermissionToolkit\Models\PermissionAuditLog;
use SalvatoreCervone\PermissionToolkit\Tests\TestCase;
use SalvatoreCervone\PermissionToolkit\Tests\User;
use Spatie\Permission\Models\Role;

class UserCreationTest extends TestCase
{
    protected User $adminUser;
    protected Role $adminRole;
    protected Role $editorRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $this->editorRole = Role::create(['name' => 'editor', 'guard_name' => 'web']);

        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->adminUser->assignRole($this->adminRole);
    }

    /** @test */
    public function it_denies_access_to_user_creation_when_feature_is_disabled()
    {
        config(['permission-toolkit.user_creation.enabled' => false]);

        $response = $this->actingAs($this->adminUser)
            ->get('/permission-manager/users/create');

        $response->assertStatus(403);

        $postResponse = $this->actingAs($this->adminUser)
            ->post('/permission-manager/users', [
                'name' => 'Marco Polo',
                'email' => 'marco@example.com',
                'password' => 'secret123',
            ]);

        $postResponse->assertStatus(403);
    }

    /** @test */
    public function it_does_not_show_create_user_button_when_disabled()
    {
        config(['permission-toolkit.user_creation.enabled' => false]);

        $response = $this->actingAs($this->adminUser)
            ->get('/permission-manager/users');

        $response->assertStatus(200);
        $response->assertDontSee(route('permission-toolkit.users.create'));
    }

    /** @test */
    public function it_shows_create_user_button_and_page_when_enabled()
    {
        config(['permission-toolkit.user_creation.enabled' => true]);

        $indexResponse = $this->actingAs($this->adminUser)
            ->get('/permission-manager/users');

        $indexResponse->assertStatus(200);
        $indexResponse->assertSee(route('permission-toolkit.users.create'));

        $createResponse = $this->actingAs($this->adminUser)
            ->get('/permission-manager/users/create');

        $createResponse->assertStatus(200);
        $createResponse->assertSee('editor');
        $createResponse->assertSee('admin');
        $createResponse->assertSee('userPasswordInput');
    }

    /** @test */
    public function it_can_create_a_new_user_with_roles_and_audit_trail()
    {
        config(['permission-toolkit.user_creation.enabled' => true]);
        Event::fake([UserAccessUpdated::class]);

        $postData = [
            'name' => 'Gianluca Rossi',
            'email' => 'gianluca@example.com',
            'password' => 'SecurePass123!',
            'roles' => [$this->editorRole->id],
        ];

        $response = $this->actingAs($this->adminUser)
            ->post('/permission-manager/users', $postData);

        $response->assertRedirect('/permission-manager/users');
        $response->assertSessionHas('status');

        // Verify User was created in DB
        $newUser = User::where('email', 'gianluca@example.com')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('Gianluca Rossi', $newUser->name);
        $this->assertTrue(Hash::check('SecurePass123!', $newUser->password));

        // Verify Role assignment
        $this->assertTrue($newUser->hasRole('editor'));

        // Verify Audit Log
        $this->assertDatabaseHas('permission_audit_logs', [
            'user_id' => $newUser->id,
            'action' => 'created',
            'type' => 'user',
        ]);

        $this->assertDatabaseHas('permission_audit_logs', [
            'user_id' => $newUser->id,
            'action' => 'assigned',
            'type' => 'role',
            'target_name' => 'editor',
        ]);

        // Verify Event was dispatched
        Event::assertDispatched(UserAccessUpdated::class, function ($event) use ($newUser) {
            return $event->user->id === $newUser->id && in_array('editor', $event->addedRoles);
        });
    }

    /** @test */
    public function it_prevents_duplicate_email_registration()
    {
        config(['permission-toolkit.user_creation.enabled' => true]);

        $response = $this->actingAs($this->adminUser)
            ->post('/permission-manager/users', [
                'name' => 'Duplicate Admin',
                'email' => 'admin@example.com', // Already used by $this->adminUser
                'password' => 'Password123',
            ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertEquals(1, User::where('email', 'admin@example.com')->count());
    }

    /** @test */
    public function it_respects_custom_creator_action_if_configured()
    {
        config(['permission-toolkit.user_creation.enabled' => true]);
        config(['permission-toolkit.user_creation.action' => CustomUserAction::class]);

        $response = $this->actingAs($this->adminUser)
            ->post('/permission-manager/users', [
                'name' => 'Custom Hook User',
                'email' => 'custom@example.com',
                'password' => 'Password123',
            ]);

        $response->assertRedirect('/permission-manager/users');

        $user = User::where('email', 'custom@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('CUSTOM: Custom Hook User', $user->name);
    }
}

class CustomUserAction
{
    public function execute(array $attributes): User
    {
        $attributes['name'] = 'CUSTOM: ' . $attributes['name'];
        return User::forceCreate($attributes);
    }
}
