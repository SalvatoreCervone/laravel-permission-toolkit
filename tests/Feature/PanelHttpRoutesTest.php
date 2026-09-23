<?php

namespace SalvatoreCervone\PermissionToolkit\Tests\Feature;

use SalvatoreCervone\PermissionToolkit\Tests\TestCase;
use SalvatoreCervone\PermissionToolkit\Tests\User;

class PanelHttpRoutesTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
        ]);
    }

    /** @test */
    public function it_can_access_matrix_page()
    {
        $response = $this->actingAs($this->user)->get('/permission-manager/matrix');

        $response->assertStatus(200);
        $response->assertSee('Matrice Ruoli');
    }

    /** @test */
    public function it_can_access_users_page()
    {
        $response = $this->actingAs($this->user)->get('/permission-manager/users');

        $response->assertStatus(200);
        $response->assertSee('Gestione Accesso Utenti');
    }

    /** @test */
    public function it_can_search_users_with_string_without_sql_error()
    {
        $response = $this->actingAs($this->user)->get('/permission-manager/users?search=cuva');

        $response->assertStatus(200);
        $response->assertSee('Gestione Accesso Utenti');
    }

    /** @test */
    public function it_can_access_simulator_page()
    {
        $response = $this->actingAs($this->user)->get('/permission-manager/simulator');

        $response->assertStatus(200);
        $response->assertSee('Diagnostic Simulator');
    }

    /** @test */
    public function it_can_access_audit_page()
    {
        $response = $this->actingAs($this->user)->get('/permission-manager/audit-logs');

        $response->assertStatus(200);
        $response->assertSee('Audit Trail');
    }

    /** @test */
    public function it_can_access_doctor_page()
    {
        $response = $this->actingAs($this->user)->get('/permission-manager/doctor');

        $response->assertStatus(200);
        $response->assertSee('Integrity Doctor');
    }
}
