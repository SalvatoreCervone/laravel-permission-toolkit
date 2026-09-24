<?php

namespace SalvatoreCervone\PermissionToolkit\Tests\Feature;

use Illuminate\Support\Facades\Hash;
use SalvatoreCervone\PermissionToolkit\Tests\TestCase;
use SalvatoreCervone\PermissionToolkit\Tests\User;

class LocalizationTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Admin Locale Test',
            'email' => 'locale@test.com',
            'password' => Hash::make('password123'),
        ]);
    }

    /** @test */
    public function it_renders_panel_in_italian_by_default()
    {
        $response = $this->actingAs($this->user)->get('/permission-manager/matrix');

        $response->assertStatus(200);
        $response->assertSee('Matrice Ruoli & Permessi');
        $response->assertSee('+ Nuovo Ruolo');
        $response->assertSee('+ Nuovo Permesso');
        $response->assertSee('Gestione Utenti');
        $response->assertSee('Diagnostic Simulator');
        $response->assertSee('Audit Trail');
        $response->assertSee('Integrity Doctor');
        $response->assertSee('🇮🇹 IT');
        $response->assertSee('🇬🇧 EN');
    }

    /** @test */
    public function it_can_switch_language_to_english_via_locale_route()
    {
        $response = $this->actingAs($this->user)
            ->from('/permission-manager/matrix')
            ->get('/permission-manager/locale/en');

        $response->assertRedirect('/permission-manager/matrix');
        $response->assertSessionHas('permission_toolkit_locale', 'en');

        // Subsequent request using session reflects English
        $pageResponse = $this->actingAs($this->user)
            ->withSession(['permission_toolkit_locale' => 'en'])
            ->get('/permission-manager/matrix');

        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('Role & Permission Matrix');
        $pageResponse->assertSee('+ New Role');
        $pageResponse->assertSee('+ New Permission');
        $pageResponse->assertSee('User Management');
    }

    /** @test */
    public function it_can_switch_language_via_query_parameter()
    {
        $response = $this->actingAs($this->user)->get('/permission-manager/matrix?lang=en');

        $response->assertStatus(200);
        $response->assertSee('Role & Permission Matrix');
        $response->assertSee('+ New Role');
        $response->assertSee('+ New Permission');
        $response->assertSee('User Management');
    }

    /** @test */
    public function it_translates_users_page_in_both_languages()
    {
        // Italian
        $itResponse = $this->actingAs($this->user)
            ->withSession(['permission_toolkit_locale' => 'it'])
            ->get('/permission-manager/users');

        $itResponse->assertStatus(200);
        $itResponse->assertSee('Gestione Accesso Utenti');
        $itResponse->assertSee('Ruoli Spatie Assegnati');
        $itResponse->assertSee('Permessi Diretti');
        $itResponse->assertSee('Cerca');

        // English
        $enResponse = $this->actingAs($this->user)
            ->withSession(['permission_toolkit_locale' => 'en'])
            ->get('/permission-manager/users');

        $enResponse->assertStatus(200);
        $enResponse->assertSee('User Access Management');
        $enResponse->assertSee('Assigned Spatie Roles');
        $enResponse->assertSee('Direct Permissions');
        $enResponse->assertSee('Search');
    }

    /** @test */
    public function it_translates_simulator_page_in_both_languages()
    {
        // Italian
        $itResponse = $this->actingAs($this->user)
            ->withSession(['permission_toolkit_locale' => 'it'])
            ->get('/permission-manager/simulator');

        $itResponse->assertStatus(200);
        $itResponse->assertSee('Configura Test');
        $itResponse->assertSee('Utente di Test');
        $itResponse->assertSee('Esegui Simulazione');
        $itResponse->assertSee('Nessuna simulazione attiva');

        // English
        $enResponse = $this->actingAs($this->user)
            ->withSession(['permission_toolkit_locale' => 'en'])
            ->get('/permission-manager/simulator');

        $enResponse->assertStatus(200);
        $enResponse->assertSee('Configure Test');
        $enResponse->assertSee('Test User');
        $enResponse->assertSee('Run Simulation');
        $enResponse->assertSee('No active simulation');
    }

    /** @test */
    public function it_translates_doctor_page_in_both_languages()
    {
        // Italian
        $itResponse = $this->actingAs($this->user)
            ->withSession(['permission_toolkit_locale' => 'it'])
            ->get('/permission-manager/doctor');

        $itResponse->assertStatus(200);
        $itResponse->assertSee('Ruoli a Database');
        $itResponse->assertSee('Permessi a Database');
        $itResponse->assertSee('Stato Integrità');

        // English
        $enResponse = $this->actingAs($this->user)
            ->withSession(['permission_toolkit_locale' => 'en'])
            ->get('/permission-manager/doctor');

        $enResponse->assertStatus(200);
        $enResponse->assertSee('Roles in Database');
        $enResponse->assertSee('Permissions in Database');
        $enResponse->assertSee('Integrity Status');
    }

    /** @test */
    public function it_translates_audit_page_in_both_languages()
    {
        // Italian
        $itResponse = $this->actingAs($this->user)
            ->withSession(['permission_toolkit_locale' => 'it'])
            ->get('/permission-manager/audit-logs');

        $itResponse->assertStatus(200);
        $itResponse->assertSee('Audit Trail di Sicurezza');
        $itResponse->assertSee('Filtra');

        // English
        $enResponse = $this->actingAs($this->user)
            ->withSession(['permission_toolkit_locale' => 'en'])
            ->get('/permission-manager/audit-logs');

        $enResponse->assertStatus(200);
        $enResponse->assertSee('Security Audit Trail');
        $enResponse->assertSee('Filter');
    }
}
