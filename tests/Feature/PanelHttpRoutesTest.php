<?php

namespace SalvatoreCervone\PermissionToolkit\Tests\Feature;

use Illuminate\Support\Facades\Hash;
use SalvatoreCervone\PermissionToolkit\Models\PermissionAuditLog;
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
            'password' => Hash::make('initial_password'),
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
    public function it_can_access_user_edit_page_with_password_reset_section()
    {
        $response = $this->actingAs($this->user)->get("/permission-manager/users/{$this->user->id}");

        $response->assertStatus(200);
        $response->assertSee('Reimposta Password');
    }

    /** @test */
    public function it_can_reset_user_password()
    {
        $response = $this->actingAs($this->user)->post("/permission-manager/users/{$this->user->id}/password", [
            'password' => 'newSecretPass123',
        ]);

        $response->assertRedirect("/permission-manager/users/{$this->user->id}");
        $this->user->refresh();

        $this->assertTrue(Hash::check('newSecretPass123', $this->user->password));

        $log = PermissionAuditLog::where('user_id', $this->user->id)
            ->where('action', 'reset')
            ->where('type', 'password')
            ->first();

        $this->assertNotNull($log);
    }

    /** @test */
    public function it_can_reset_password_and_update_date_field()
    {
        $dateValue = '2026-10-15T10:30';

        $response = $this->actingAs($this->user)->post("/permission-manager/users/{$this->user->id}/password", [
            'password' => 'newSecretPass456',
            'date_value' => $dateValue,
            'date_field' => 'password_reset',
        ]);

        $response->assertRedirect("/permission-manager/users/{$this->user->id}");
        $this->user->refresh();

        $this->assertTrue(Hash::check('newSecretPass456', $this->user->password));
        $this->assertNotNull($this->user->password_reset);
        $this->assertStringContainsString('2026-10-15', (string)$this->user->password_reset);

        $log = PermissionAuditLog::where('user_id', $this->user->id)
            ->where('action', 'reset')
            ->where('type', 'password')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('password_reset', $log->metadata['date_field']);
    }

    /** @test */
    public function it_handles_missing_date_column_gracefully()
    {
        $response = $this->actingAs($this->user)->post("/permission-manager/users/{$this->user->id}/password", [
            'password' => 'safePass789',
            'date_value' => '2026-10-15T10:30',
            'date_field' => 'non_existent_column',
        ]);

        $response->assertRedirect("/permission-manager/users/{$this->user->id}");
        $this->user->refresh();

        $this->assertTrue(Hash::check('safePass789', $this->user->password));
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
