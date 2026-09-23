<?php

namespace SalvatoreCervone\PermissionToolkit\Commands;

use Illuminate\Console\Command;
use SalvatoreCervone\PermissionToolkit\Services\IntegrityChecker;

class DoctorPermissionCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'permission:doctor';

    /**
     * The console command description.
     */
    protected $description = 'Inspect database integrity for Spatie permissions, roles, and orphaned pivot relations';

    /**
     * Execute the console command.
     */
    public function handle(IntegrityChecker $checker): int
    {
        $this->info("===============================================================");
        $this->info(" 🩺  ROLE & PERMISSION INTEGRITY DOCTOR");
        $this->info("===============================================================");

        $report = $checker->diagnose();
        $hasIssues = false;

        $this->line("Total Roles in DB       : {$report['summary']['total_roles']}");
        $this->line("Total Permissions in DB : {$report['summary']['total_permissions']}");
        $this->newLine();

        // 1. Orphaned Pivots
        if (! empty($report['orphaned_pivot_records'])) {
            $hasIssues = true;
            $this->error("✘ Orphaned Pivot Records Found:");
            foreach ($report['orphaned_pivot_records'] as $orphan) {
                $this->line("  • Table [{$orphan['table']}]: {$orphan['issue']}");
            }
            $this->newLine();
        } else {
            $this->info("✔ No orphaned pivot records detected.");
        }

        // 2. Unused Permissions
        if (! empty($report['unused_permissions'])) {
            $this->warn("⚠ Unused Permissions (" . count($report['unused_permissions']) . " detected - not assigned to any role or user):");
            $rows = [];
            foreach (array_slice($report['unused_permissions'], 0, 10) as $p) {
                $rows[] = [$p['id'], $p['name'], $p['guard_name']];
            }
            $this->table(['ID', 'Permission Name', 'Guard'], $rows);
            if (count($report['unused_permissions']) > 10) {
                $this->line("  ... and " . (count($report['unused_permissions']) - 10) . " more.");
            }
            $this->newLine();
        } else {
            $this->info("✔ All permissions are actively assigned.");
        }

        // 3. Empty Roles
        if (! empty($report['empty_roles'])) {
            $this->warn("⚠ Empty Roles (" . count($report['empty_roles']) . " roles with 0 permissions and 0 users):");
            $rows = [];
            foreach ($report['empty_roles'] as $r) {
                $rows[] = [$r['id'], $r['name'], $r['guard_name']];
            }
            $this->table(['ID', 'Role Name', 'Guard'], $rows);
            $this->newLine();
        } else {
            $this->info("✔ No completely empty roles found.");
        }

        // 4. Guard Mismatches
        if (! empty($report['guard_mismatches'])) {
            $hasIssues = true;
            $this->error("✘ Guard Mismatches Detected:");
            $rows = [];
            foreach ($report['guard_mismatches'] as $m) {
                $rows[] = [$m['role'], $m['permission'], $m['issue']];
            }
            $this->table(['Role', 'Permission', 'Diagnosis'], $rows);
            $this->newLine();
        } else {
            $this->info("✔ Guard names match seamlessly across roles and permissions.");
        }

        // 5. Naming Inconsistencies
        if (! empty($report['naming_inconsistencies'])) {
            $this->comment("ℹ " . $report['naming_inconsistencies']['warning']);
            $this->line("  Detected Styles: " . implode(', ', $report['naming_inconsistencies']['detected_styles']));
            $this->line("  Recommendation : " . $report['naming_inconsistencies']['recommendation']);
            $this->newLine();
        }

        $this->info("===============================================================");
        if ($hasIssues) {
            $this->warn(" Doctor diagnosis completed with warnings or integrity anomalies.");
            return Command::FAILURE;
        } else {
            $this->info(" Doctor diagnosis complete. All authorization tables in healthy state! ✔");
            return Command::SUCCESS;
        }
    }
}
