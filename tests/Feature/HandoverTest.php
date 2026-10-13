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
        $response->assertSee('Modifica');
    }

    /** @test */
    public function it_can_create_a_new_handover_template()
    {
        $response = $this->actingAs($this->admin)->post('/permission-manager/handover-templates', [
            'name' => 'Direzione Commerciale',
            'application_name' => 'CRM Cloud Suite',
            'company_name' => 'Acme S.p.A.',
            'department' => 'Sales',
            'signer_title' => 'Chief Commercial Officer',
            'signer_name' => 'Alessandro Galli',
            'second_signer_title' => 'CISO',
            'second_signer_name' => 'Roberto Mancini',
            'reference_code' => 'DOC-CRM-2026',
            'status_label' => 'Certificato',
            'disclaimer_text' => 'Dichiaro di aver ricevuto le credenziali per CRM e vendite.',
            'footer_text' => 'Acme S.p.A. • Divisione Commerciale & Sicurezza',
            'is_default' => '1',
        ]);

        $response->assertRedirect('/permission-manager/handover-templates');
        $this->assertDatabaseHas('permission_handover_templates', [
            'name' => 'Direzione Commerciale',
            'application_name' => 'CRM Cloud Suite',
            'signer_name' => 'Alessandro Galli',
            'second_signer_name' => 'Roberto Mancini',
            'reference_code' => 'DOC-CRM-2026',
            'status_label' => 'Certificato',
            'footer_text' => 'Acme S.p.A. • Divisione Commerciale & Sicurezza',
            'is_default' => 1,
        ]);
    }

    /** @test */
    public function it_can_update_an_existing_handover_template()
    {
        $template = HandoverTemplate::create([
            'name' => 'Direzione HR',
            'application_name' => 'HR App',
            'company_name' => 'Acme Corp',
            'department' => 'HR',
            'signer_title' => 'HR Manager',
            'signer_name' => 'Laura Corti',
            'disclaimer_text' => 'Original disclaimer text',
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->admin)->put("/permission-manager/handover-templates/{$template->id}", [
            'name' => 'Direzione Risorse Umane & Organizzazione',
            'application_name' => 'HR Portal Global',
            'company_name' => 'Acme Corp',
            'department' => 'People & Org',
            'signer_title' => 'Chief People Officer',
            'signer_name' => 'Laura Corti',
            'second_signer_title' => 'Security Director',
            'second_signer_name' => 'Marco Ferrari',
            'reference_code' => 'DOC-HR-001',
            'status_label' => 'Approvato',
            'disclaimer_text' => 'Updated disclaimer text',
            'footer_text' => 'Custom Footer HR',
            'is_default' => '1',
        ]);

        $response->assertRedirect('/permission-manager/handover-templates');
        $template->refresh();

        $this->assertEquals('Direzione Risorse Umane & Organizzazione', $template->name);
        $this->assertEquals('HR Portal Global', $template->application_name);
        $this->assertEquals('Chief People Officer', $template->signer_title);
        $this->assertEquals('Marco Ferrari', $template->second_signer_name);
        $this->assertEquals('DOC-HR-001', $template->reference_code);
        $this->assertEquals('Custom Footer HR', $template->footer_text);
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
            'application_name' => 'SAP Enterprise Suite',
            'company_name' => 'Acme Security S.p.A.',
            'department' => 'Cyber Operations',
            'signer_title' => 'Security Lead',
            'signer_name' => 'Marco Ferrari',
            'second_signer_title' => 'DPO',
            'second_signer_name' => 'Avv. Andrea Conti',
            'reference_code' => 'DOC-SEC-99',
            'status_label' => 'Valido ISO',
            'disclaimer_text' => 'Custom text acknowledging GDPR Art 29 compliance.',
            'footer_text' => 'Acme Corp • Stampa Ufficiale di Sicurezza',
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
        $response->assertSee('SAP Enterprise Suite');
        $response->assertSee('Acme Security S.p.A.');
        $response->assertSee('Marco Ferrari');
        $response->assertSee('Avv. Andrea Conti');
        $response->assertSee('DPO');
        $response->assertSee('DOC-SEC-99');
        $response->assertSee('Valido ISO');
        $response->assertSee('Acme Corp • Stampa Ufficiale di Sicurezza');
        $response->assertSee('Mario Rossi');
        $response->assertSee('auditor');
        $response->assertSee('reports.view');
        $response->assertSee('users.direct-audit');
    }

    /** @test */
    public function it_does_not_render_ref_or_status_if_they_are_empty()
    {
        $tmpl = HandoverTemplate::create([
            'name' => 'Clean Template',
            'company_name' => 'Simple Corp',
            'signer_title' => 'Director',
            'signer_name' => 'Boss',
            'reference_code' => null,
            'status_label' => null,
            'disclaimer_text' => 'Clean test text',
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->admin)->get("/permission-manager/users/{$this->targetUser->id}/handover?template_id={$tmpl->id}");

        $response->assertStatus(200);
        $response->assertDontSee('<strong>REF:</strong>', false);
        $response->assertDontSee('<strong>Status:</strong>', false);
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
    public function it_renders_dynamic_labels_and_titles_when_provided()
    {
        $tmpl = HandoverTemplate::create([
            'name' => 'Custom Labels Template',
            'doc_title' => 'Verbale Straordinario di Assegnazione',
            'doc_subtitle' => 'Sottotitolo Personalizzato GDPR',
            'signer_label' => 'Direzione Generale:',
            'signer_name' => 'Dott. Mario Draghi',
            'signer_title' => 'Direttore Generale',
            'second_signer_label' => 'Responsabile Conformità:',
            'second_signer_name' => 'Avv. Giulia De Rossi',
            'second_signer_title' => 'DPO Aziendale',
            'user_signer_label' => 'Dipendente Ricevente:',
            'user_role_label' => 'Specialista Contabile',
            'disclaimer_text' => 'Dichiarazione personalizzata.',
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->admin)->get("/permission-manager/users/{$this->targetUser->id}/handover?template_id={$tmpl->id}");

        $response->assertStatus(200);
        $response->assertSee('Verbale Straordinario di Assegnazione');
        $response->assertSee('Sottotitolo Personalizzato GDPR');
        $response->assertSee('Direzione Generale:');
        $response->assertSee('Dott. Mario Draghi');
        $response->assertSee('Responsabile Conformità:');
        $response->assertSee('Avv. Giulia De Rossi');
        $response->assertSee('DPO Aziendale');
        $response->assertSee('Dipendente Ricevente:');
        $response->assertSee('Specialista Contabile');
    }

    /** @test */
    public function it_suppresses_labels_and_elements_when_fields_are_null_or_empty()
    {
        $tmpl = HandoverTemplate::create([
            'name' => 'Minimal Template',
            'doc_title' => null,
            'doc_subtitle' => null,
            'company_name' => null,
            'department' => null,
            'signer_name' => 'Solo Nome',
            'signer_title' => null,
            'second_signer_name' => null,
            'second_signer_title' => null,
            'reference_code' => null,
            'status_label' => null,
            'notes' => null,
            'footer_text' => null,
            'disclaimer_text' => 'Testo essenziale.',
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->admin)->get("/permission-manager/users/{$this->targetUser->id}/handover?template_id={$tmpl->id}");

        $response->assertStatus(200);
        $response->assertDontSee('<div class="doc-subtitle">', false);
        $response->assertDontSee('<div class="doc-dept">', false);
        $response->assertDontSee('<strong>REF:</strong>', false);
        $response->assertDontSee('<strong>Status:</strong>', false);
        $response->assertDontSee('<strong>Note:</strong>', false);
        $response->assertDontSee('Compliance & Access Governance System');
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
