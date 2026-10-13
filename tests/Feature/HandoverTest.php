<?php

namespace SalvatoreCervone\PermissionToolkit\Tests\Feature;

use Illuminate\Support\Facades\Hash;
use SalvatoreCervone\PermissionToolkit\Models\HandoverTemplate;
use SalvatoreCervone\PermissionToolkit\Models\PermissionAuditLog;
use SalvatoreCervone\PermissionToolkit\Tests\TestCase;
use SalvatoreCervone\PermissionToolkit\Tests\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class HandoverTest extends TestCase
{
    protected User $admin;
    protected User $targetUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Operator',
            'email' => 'admin@enterprise.test',
            'password' => Hash::make('secret'),
        ]);

        $this->targetUser = User::create([
            'name' => 'Mario Rossi',
            'email' => 'mario.rossi@enterprise.test',
            'password' => Hash::make('secret'),
        ]);
    }

    /** @test */
    public function it_can_list_handover_templates()
    {
        HandoverTemplate::create([
            'name' => 'Direzione IT',
            'company_name' => 'Acme Corp',
            'department' => 'IT Department',
            'signer_title' => 'CISO',
            'signer_name' => 'Roberto Mancini',
            'disclaimer_text' => 'Test disclaimer for IT',
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->admin)->get('/permission-manager/handover-templates');

        $response->assertStatus(200);
        $response->assertSee('Direzione IT');
        $response->assertSee('Roberto Mancini');
        $response->assertSee('Acme Corp');
    }

    /** @test */
    public function it_can_create_a_new_handover_template()
    {
        $response = $this->actingAs($this->admin)->post('/permission-manager/handover-templates', [
            'name' => 'Direzione Commerciale',
            'company_name' => 'Acme S.p.A.',
            'department' => 'Sales',
            'signer_title' => 'Chief Commercial Officer',
            'signer_name' => 'Alessandro Galli',
            'disclaimer_text' => 'Dichiaro di aver ricevuto le credenziali per CRM e vendite.',
            'is_default' => '1',
        ]);

        $response->assertRedirect('/permission-manager/handover-templates');
        $this->assertDatabaseHas('permission_handover_templates', [
            'name' => 'Direzione Commerciale',
            'signer_name' => 'Alessandro Galli',
            'is_default' => 1,
        ]);
    }

    /** @test */
    public function it_can_update_an_existing_handover_template()
    {
        $template = HandoverTemplate::create([
            'name' => 'Direzione HR',
            'company_name' => 'Acme Corp',
            'department' => 'HR',
            'signer_title' => 'HR Manager',
            'signer_name' => 'Laura Corti',
            'disclaimer_text' => 'Original disclaimer text',
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->admin)->put("/permission-manager/handover-templates/{$template->id}", [
            'name' => 'Direzione Risorse Umane & Organizzazione',
            'company_name' => 'Acme Corp',
            'department' => 'People & Org',
            'signer_title' => 'Chief People Officer',
            'signer_name' => 'Laura Corti',
            'disclaimer_text' => 'Updated disclaimer text',
            'is_default' => '1',
        ]);

        $response->assertRedirect('/permission-manager/handover-templates');
        $template->refresh();

        $this->assertEquals('Direzione Risorse Umane & Organizzazione', $template->name);
        $this->assertEquals('Chief People Officer', $template->signer_title);
        $this->assertEquals('Updated disclaimer text', $template->disclaimer_text);
        $this->assertTrue($template->is_default);
    }

    /** @test */
    public function it_can_set_a_template_as_default()
    {
        $tmpl1 = HandoverTemplate::create([
            'name' => 'Template 1',
            'signer_title' => 'Director 1',
            'signer_name' => 'Signer 1',
            'disclaimer_text' => 'Disclaimer 1',
            'is_default' => true,
        ]);

        $tmpl2 = HandoverTemplate::create([
            'name' => 'Template 2',
            'signer_title' => 'Director 2',
            'signer_name' => 'Signer 2',
            'disclaimer_text' => 'Disclaimer 2',
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->admin)->post("/permission-manager/handover-templates/{$tmpl2->id}/default");

        $response->assertRedirect('/permission-manager/handover-templates');

        $tmpl1->refresh();
        $tmpl2->refresh();

        $this->assertFalse($tmpl1->is_default);
        $this->assertTrue($tmpl2->is_default);
    }

    /** @test */
    public function it_can_delete_a_handover_template()
    {
        $template = HandoverTemplate::create([
            'name' => 'To Delete',
            'signer_title' => 'Title',
            'signer_name' => 'Name',
            'disclaimer_text' => 'Text',
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->admin)->delete("/permission-manager/handover-templates/{$template->id}");

        $response->assertRedirect('/permission-manager/handover-templates');
        $this->assertDatabaseMissing('permission_handover_templates', ['id' => $template->id]);
    }

    /** @test */
    public function it_renders_printable_handover_sheet_with_default_template()
    {
        $tmpl = HandoverTemplate::create([
            'name' => 'Default Directive',
            'company_name' => 'Acme Security S.p.A.',
            'department' => 'Cyber Operations',
            'signer_title' => 'Security Lead',
            'signer_name' => 'Marco Ferrari',
            'disclaimer_text' => 'Custom text acknowledging GDPR Art 29 compliance.',
            'is_default' => true,
        ]);

        $role = Role::create(['name' => 'auditor', 'guard_name' => 'web']);
        $perm1 = Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);
        $perm2 = Permission::create(['name' => 'users.direct-audit', 'guard_name' => 'web']);

        $role->givePermissionTo($perm1);
        $this->targetUser->assignRole($role);
        $this->targetUser->givePermissionTo($perm2);

        $response = $this->actingAs($this->admin)->get("/permission-manager/users/{$this->targetUser->id}/handover");

        $response->assertStatus(200);
        $response->assertSee('Acme Security S.p.A.');
        $response->assertSee('Marco Ferrari');
        $response->assertSee('Security Lead');
        $response->assertSee('Mario Rossi');
        $response->assertSee('auditor');
        $response->assertSee('reports.view');
        $response->assertSee('users.direct-audit');
        $response->assertSee('Custom text acknowledging GDPR Art 29 compliance.');
    }

    /** @test */
    public function it_can_switch_template_via_query_parameter()
    {
        $tmpl1 = HandoverTemplate::create([
            'name' => 'IT Template',
            'company_name' => 'Company A',
            'signer_title' => 'IT Director',
            'signer_name' => 'John IT',
            'disclaimer_text' => 'IT text',
            'is_default' => true,
        ]);

        $tmpl2 = HandoverTemplate::create([
            'name' => 'Legal Template',
            'company_name' => 'Company Legal',
            'signer_title' => 'Chief Legal Officer',
            'signer_name' => 'Jane Lawyer',
            'disclaimer_text' => 'Legal text',
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->admin)->get("/permission-manager/users/{$this->targetUser->id}/handover?template_id={$tmpl2->id}");

        $response->assertStatus(200);
        $response->assertSee('Jane Lawyer');
        $response->assertSee('Chief Legal Officer');
        $response->assertSee('Legal text');
        $response->assertDontSee('IT Director');
    }

    /** @test */
    public function it_supports_custom_template_resolver_closure()
    {
        $specialTmpl = HandoverTemplate::create([
            'name' => 'Special Unit',
            'company_name' => 'Special Branch',
            'signer_title' => 'Unit Commander',
            'signer_name' => 'James Bond',
            'disclaimer_text' => 'Classified access text',
            'is_default' => false,
        ]);

        config()->set('permission-toolkit.handover.template_resolver', function ($user) use ($specialTmpl) {
            return $specialTmpl->id;
        });

        $response = $this->actingAs($this->admin)->get("/permission-manager/users/{$this->targetUser->id}/handover");

        $response->assertStatus(200);
        $response->assertSee('James Bond');
        $response->assertSee('Unit Commander');
        $response->assertSee('Classified access text');
    }

    /** @test */
    public function it_logs_audit_trail_when_handover_sheet_is_generated()
    {
        $response = $this->actingAs($this->admin)->get("/permission-manager/users/{$this->targetUser->id}/handover");

        $response->assertStatus(200);

        $this->assertDatabaseHas('permission_audit_logs', [
            'user_id' => $this->targetUser->id,
            'action' => 'generated',
            'type' => 'handover_sheet',
        ]);
    }

    /** @test */
    public function it_returns_404_when_handover_feature_is_disabled()
    {
        config()->set('permission-toolkit.handover.enabled', false);

        $responseTemplates = $this->actingAs($this->admin)->get('/permission-manager/handover-templates');
        $responseTemplates->assertStatus(404);

        $responseSheet = $this->actingAs($this->admin)->get("/permission-manager/users/{$this->targetUser->id}/handover");
        $responseSheet->assertStatus(404);
    }
}
