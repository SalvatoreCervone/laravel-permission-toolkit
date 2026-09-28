<?php

namespace SalvatoreCervone\PermissionToolkit\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use SalvatoreCervone\PermissionToolkit\PermissionToolkit;
use SalvatoreCervone\PermissionToolkit\Tests\TestCase;
use SalvatoreCervone\PermissionToolkit\Tests\User;
use Spatie\Permission\Models\Role;

class UserDisplayAndOrderTest extends TestCase
{
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Add cognome and nome columns to test table
        if (! Schema::hasColumn('users', 'cognome')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('cognome')->nullable();
                $table->string('nome')->nullable();
            });
        }

        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);

        $this->adminUser = User::create([
            'name' => 'Admin User',
            'cognome' => 'Admin',
            'nome' => 'Super',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->adminUser->assignRole($adminRole);
    }

    /** @test */
    public function it_lists_all_active_users_before_deactivated_users()
    {
        // User A: Active, ID 2
        $activeUser1 = User::create([
            'name' => 'Pietro Active',
            'email' => 'pietro@example.com',
            'password' => Hash::make('password'),
        ]);

        // User B: Trashed, ID 3 (would naturally be before User C by ID)
        $trashedUser1 = User::create([
            'name' => 'Sergio Deactivated',
            'email' => 'sergio@example.com',
            'password' => Hash::make('password'),
        ]);
        $trashedUser1->delete(); // Soft-deleted

        // User C: Active, ID 4
        $activeUser2 = User::create([
            'name' => 'Cristiano Active',
            'email' => 'cristiano@example.com',
            'password' => Hash::make('password'),
        ]);

        // User D: Trashed, ID 5
        $trashedUser2 = User::create([
            'name' => 'Danilo Deactivated',
            'email' => 'danilo@example.com',
            'password' => Hash::make('password'),
        ]);
        $trashedUser2->delete(); // Soft-deleted

        $response = $this->actingAs($this->adminUser)
            ->get('/permission-manager/users');

        $response->assertStatus(200);

        // Fetch users from view data
        $usersInView = $response->viewData('users');
        $userIds = $usersInView->pluck('id')->all();

        // Trashed user IDs should all appear AFTER active user IDs
        $firstTrashedIndex = min(
            array_search($trashedUser1->id, $userIds),
            array_search($trashedUser2->id, $userIds)
        );

        $lastActiveIndex = max(
            array_search($this->adminUser->id, $userIds),
            array_search($activeUser1->id, $userIds),
            array_search($activeUser2->id, $userIds)
        );

        $this->assertGreaterThan($lastActiveIndex, $firstTrashedIndex, 'Active users must appear before deactivated users');
    }

    /** @test */
    public function it_displays_custom_configured_columns_instead_of_just_name()
    {
        config(['permission-toolkit.users.display_columns' => ['cognome', 'nome']]);

        $user = User::create([
            'name' => 'ShouldNotBeShownFirst',
            'cognome' => 'Rossi',
            'nome' => 'Mario',
            'email' => 'mario.rossi@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->assertEquals('Rossi Mario', PermissionToolkit::getUserDisplayName($user));

        $response = $this->actingAs($this->adminUser)
            ->get('/permission-manager/users');

        $response->assertStatus(200);
        $response->assertSee('Rossi Mario');
    }

    /** @test */
    public function it_orders_users_by_configured_display_columns_in_given_order()
    {
        // When display_columns = ['cognome', 'nome'], sorting is: cognome ASC, then nome ASC
        config(['permission-toolkit.users.display_columns' => ['cognome', 'nome']]);
        config(['permission-toolkit.users.order_by' => null]);

        $u1 = User::create([
            'cognome' => 'Bianchi',
            'nome' => 'Zaccaria',
            'email' => 'bz@example.com',
            'password' => Hash::make('password'),
        ]);

        $u2 = User::create([
            'cognome' => 'Bianchi',
            'nome' => 'Andrea',
            'email' => 'ba@example.com',
            'password' => Hash::make('password'),
        ]);

        $u3 = User::create([
            'cognome' => 'Alberti',
            'nome' => 'Carlo',
            'email' => 'ac@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('/permission-manager/users');

        $response->assertStatus(200);

        $usersInView = $response->viewData('users');
        $userIds = $usersInView->pluck('id')->all();

        $posAlberti = array_search($u3->id, $userIds);
        $posBianchiAndrea = array_search($u2->id, $userIds);
        $posBianchiZaccaria = array_search($u1->id, $userIds);

        // Alberti Carlo < Bianchi Andrea < Bianchi Zaccaria
        $this->assertLessThan($posBianchiAndrea, $posAlberti);
        $this->assertLessThan($posBianchiZaccaria, $posBianchiAndrea);
    }

    /** @test */
    public function it_supports_custom_order_by_configuration()
    {
        config(['permission-toolkit.users.display_columns' => ['cognome', 'nome']]);
        config(['permission-toolkit.users.order_by' => ['cognome' => 'desc', 'nome' => 'asc']]);

        $u1 = User::create([
            'cognome' => 'Alberti',
            'nome' => 'Carlo',
            'email' => 'alberti@example.com',
            'password' => Hash::make('password'),
        ]);

        $u2 = User::create([
            'cognome' => 'Zanetti',
            'nome' => 'Marco',
            'email' => 'zanetti@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('/permission-manager/users');

        $response->assertStatus(200);

        $usersInView = $response->viewData('users');
        $userIds = $usersInView->pluck('id')->all();

        $posZanetti = array_search($u2->id, $userIds);
        $posAlberti = array_search($u1->id, $userIds);

        // In DESC order for cognome, Zanetti comes before Alberti
        $this->assertLessThan($posAlberti, $posZanetti);
    }

    /** @test */
    public function it_searches_in_configured_display_columns()
    {
        config(['permission-toolkit.users.display_columns' => ['cognome', 'nome']]);

        $u = User::create([
            'cognome' => 'Esposito',
            'nome' => 'Gennaro',
            'email' => 'gennaro@example.com',
            'password' => Hash::make('password'),
        ]);

        // Search by cognome
        $res1 = $this->actingAs($this->adminUser)
            ->get('/permission-manager/users?search=Esposito');
        $res1->assertSee('Esposito Gennaro');

        // Search by nome
        $res2 = $this->actingAs($this->adminUser)
            ->get('/permission-manager/users?search=Gennaro');
        $res2->assertSee('Esposito Gennaro');
    }

    /** @test */
    public function it_supports_clickable_column_sorting_while_keeping_active_users_first()
    {
        config(['permission-toolkit.users.display_columns' => ['cognome', 'nome']]);

        $uActiveZ = User::create([
            'cognome' => 'Zeta',
            'nome' => 'Zeno',
            'email' => 'zz@example.com',
            'password' => Hash::make('password'),
        ]);

        $uActiveA = User::create([
            'cognome' => 'Alfa',
            'nome' => 'Antonio',
            'email' => 'aa@example.com',
            'password' => Hash::make('password'),
        ]);

        $uTrashedA = User::create([
            'cognome' => 'Alfa',
            'nome' => 'Alberto',
            'email' => 'alberto.trashed@example.com',
            'password' => Hash::make('password'),
        ]);
        $uTrashedA->delete();

        // Sort by user desc
        $response = $this->actingAs($this->adminUser)
            ->get('/permission-manager/users?sort=user&direction=desc');

        $response->assertStatus(200);

        $usersInView = $response->viewData('users');
        $userIds = $usersInView->pluck('id')->all();

        // Active Zeta must come before Active Alfa (descending by cognome)
        $posActiveZ = array_search($uActiveZ->id, $userIds);
        $posActiveA = array_search($uActiveA->id, $userIds);
        $this->assertLessThan($posActiveA, $posActiveZ);

        // And Trashed Alfa must still be AFTER active users, even though in desc order
        $posTrashedA = array_search($uTrashedA->id, $userIds);
        $this->assertGreaterThan($posActiveA, $posTrashedA);
        $this->assertGreaterThan($posActiveZ, $posTrashedA);
    }

    /** @test */
    public function it_renders_dedicated_status_column_with_aligned_pills_and_supports_status_sorting()
    {
        $activeUser = User::create([
            'cognome' => 'Rossi',
            'nome' => 'Mario',
            'email' => 'active.mario@example.com',
            'password' => Hash::make('password'),
        ]);

        $trashedUser = User::create([
            'cognome' => 'Bianchi',
            'nome' => 'Luigi',
            'email' => 'trashed.luigi@example.com',
            'password' => Hash::make('password'),
        ]);
        $trashedUser->delete();

        $response = $this->actingAs($this->adminUser)
            ->get('/permission-manager/users');

        $response->assertStatus(200);
        // Assert dedicated status column header exists
        $response->assertSee('Stato');
        // Assert pills with indicator dots exist
        $response->assertSee('user-status-pill status-active');
        $response->assertSee('user-status-pill status-deactivated');
        $response->assertSee('status-indicator-dot');

        // Test sorting by status desc (trashed first)
        $descResponse = $this->actingAs($this->adminUser)
            ->get('/permission-manager/users?sort=status&direction=desc');

        $descUsers = $descResponse->viewData('users')->pluck('id')->all();
        $this->assertLessThan(
            array_search($activeUser->id, $descUsers),
            array_search($trashedUser->id, $descUsers)
        );
    }
}
