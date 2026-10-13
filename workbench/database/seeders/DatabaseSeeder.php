<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use SalvatoreCervone\PermissionToolkit\Models\PermissionAuditLog;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Workbench\App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Create Spatie Permissions
        $permissions = [
            // Users module
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            // Invoices module
            'invoices.view',
            'invoices.create',
            'invoices.edit',
            'invoices.delete',

            // Reports module
            'reports.view',
            'reports.export',
            'reports.special-audit',

            // Settings module
            'settings.view',
            'settings.update',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // 2. Create Spatie Roles
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $manager    = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $accountant = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);
        $viewer     = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);

        // 3. Assign Permissions to Roles
        $manager->syncPermissions([
            'users.view', 'users.create', 'users.edit',
            'invoices.view', 'invoices.create', 'invoices.edit',
            'reports.view',
            'settings.view',
        ]);

        $accountant->syncPermissions([
            'invoices.view', 'invoices.create', 'invoices.edit',
            'reports.view', 'reports.export',
        ]);

        $viewer->syncPermissions([
            'users.view',
            'invoices.view',
            'reports.view',
        ]);

        // 4. Create Demo Users
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@demo.test'],
            ['name' => 'Mario Rossi (Super Admin)', 'password' => bcrypt('password')]
        );
        $adminUser->syncRoles([$superAdmin]);

        $managerUser = User::firstOrCreate(
            ['email' => 'manager@demo.test'],
            ['name' => 'Laura Bianchi (Operations Manager)', 'password' => bcrypt('password')]
        );
        $managerUser->syncRoles([$manager]);

        $accountantUser = User::firstOrCreate(
            ['email' => 'accountant@demo.test'],
            ['name' => 'Giuseppe Verdi (Senior Accountant)', 'password' => bcrypt('password')]
        );
        $accountantUser->syncRoles([$accountant]);
        // Direct permission for testing direct checks in simulator!
        $accountantUser->givePermissionTo('reports.special-audit');

        $viewerUser = User::firstOrCreate(
            ['email' => 'viewer@demo.test'],
            ['name' => 'Anna Neri (Guest Viewer)', 'password' => bcrypt('password')]
        );
        $viewerUser->syncRoles([$viewer]);

        // 5. Seed Realistic Security Audit Logs
        if (\Illuminate\Support\Facades\Schema::hasTable(config('permission-toolkit.audit.table', 'permission_audit_logs'))) {
            PermissionAuditLog::firstOrCreate([
                'action' => 'assigned',
                'type' => 'role',
                'target_name' => 'super-admin',
                'user_id' => $adminUser->id,
                'user_type' => get_class($adminUser),
            ], [
                'causer_id' => null,
                'causer_type' => null,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Seeder / Initial Setup',
                'created_at' => now()->subDays(3),
            ]);

            PermissionAuditLog::firstOrCreate([
                'action' => 'assigned',
                'type' => 'role',
                'target_name' => 'manager',
                'user_id' => $managerUser->id,
                'user_type' => get_class($managerUser),
            ], [
                'causer_id' => $adminUser->id,
                'causer_type' => get_class($adminUser),
                'ip_address' => '192.168.1.15',
                'user_agent' => 'Mozilla/5.0 (Workbench Demo)',
                'created_at' => now()->subDays(2),
            ]);

            PermissionAuditLog::firstOrCreate([
                'action' => 'assigned',
                'type' => 'permission',
                'target_name' => 'reports.special-audit',
                'user_id' => $accountantUser->id,
                'user_type' => get_class($accountantUser),
            ], [
                'causer_id' => $adminUser->id,
                'causer_type' => get_class($adminUser),
                'ip_address' => '192.168.1.15',
                'user_agent' => 'Mozilla/5.0 (Workbench Demo)',
                'created_at' => now()->subDay(),
            ]);
        }

        // 6. Seed Realistic Handover Signoff Templates
        $handoverTable = config('permission-toolkit.handover.table', 'permission_handover_templates');
        if (\Illuminate\Support\Facades\Schema::hasTable($handoverTable)) {
            \SalvatoreCervone\PermissionToolkit\Models\HandoverTemplate::updateOrCreate([
                'name' => 'Direzione IT & Cybersecurity',
            ], [
                'application_name' => 'Enterprise Infrastructure & Cloud',
                'company_name' => 'Acme Enterprise S.p.A.',
                'department' => 'Sistemi Informativi & Cybersecurity',
                'signer_title' => 'Chief Information Security Officer (CISO)',
                'signer_name' => 'Ing. Roberto Mancini',
                'second_signer_title' => null,
                'second_signer_name' => null,
                'reference_code' => 'DOC-IT-SEC',
                'status_label' => 'Conforme ISO 27001',
                'disclaimer_text' => "Il sottoscritto dichiara di aver ricevuto le credenziali di autenticazione al sistema informativo e la presente scheda di attribuzione privilegi. Si impegna ad osservare rigorosamente le istruzioni impartite dal Titolare del trattamento ai sensi dell'art. 29 del Regolamento UE 2016/679 (GDPR), custodendo con la massima diligenza e segretezza le chiavi di accesso personali e segnalando tempestivamente al Team di Sicurezza qualsiasi anomalia o violazione rilevata.",
                'notes' => 'Da archiviare nel fascicolo di conformità IT e conservare per tutta la durata del rapporto contrattuale.',
                'footer_text' => 'Acme Enterprise S.p.A. • Divisione Sicurezza Informatica & IT Governance',
                'is_default' => true,
            ]);

            \SalvatoreCervone\PermissionToolkit\Models\HandoverTemplate::updateOrCreate([
                'name' => 'Direzione Risorse Umane',
            ], [
                'application_name' => 'HR Portal & Payroll Suite',
                'company_name' => 'Acme Enterprise S.p.A.',
                'department' => 'People & Organization / HR',
                'signer_title' => 'Head of Human Resources (Direttore Utente)',
                'signer_name' => 'Dott.ssa Laura Corti',
                'second_signer_title' => 'Chief Information Security Officer (CISO)',
                'second_signer_name' => 'Ing. Roberto Mancini',
                'reference_code' => 'DOC-HR-PRIVACY',
                'status_label' => 'Conforme GDPR Art. 29',
                'disclaimer_text' => "Con la firma del presente documento, l'incaricato attesta la consegna del profilo di accesso informatico autorizzato per le sole mansioni lavorative contrattualmente concordate, impegnandosi a non cedere né divulgare a terzi le credenziali ricevute in conformità con il Regolamento Aziendale.",
                'notes' => 'Copia destinata al fascicolo personale del dipendente con duplice firma della Direzione HR e del CISO.',
                'footer_text' => 'Acme Enterprise S.p.A. • Ufficio Risorse Umane & DPO Compliance',
                'is_default' => false,
            ]);

            \SalvatoreCervone\PermissionToolkit\Models\HandoverTemplate::updateOrCreate([
                'name' => 'Direzione Commerciale & Marketing',
            ], [
                'application_name' => 'CRM & Sales Cloud',
                'company_name' => 'Acme Enterprise S.p.A.',
                'department' => 'Sales & Business Development',
                'signer_title' => 'Chief Commercial Officer (Direttore Utente)',
                'signer_name' => 'Dott. Alessandro Galli',
                'second_signer_title' => 'IT Operations & Infrastructure Manager',
                'second_signer_name' => 'Ing. Marco Bianchi',
                'reference_code' => 'DOC-CRM',
                'status_label' => 'Attivo / Certificato',
                'disclaimer_text' => "Il sottoscritto dichiara di aver preso visione dei ruoli e permessi associati alla propria utenza sul CRM e sui database aziendali, assumendosi l'onere della corretta custodia dei dati di clienti e partner commerciali nel rispetto delle norme sulla riservatezza.",
                'notes' => 'Valido per la sede centrale e le filiali regionali.',
                'footer_text' => null,
                'is_default' => false,
            ]);
        }
    }
}
