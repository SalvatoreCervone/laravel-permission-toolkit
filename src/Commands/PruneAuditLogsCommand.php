<?php

namespace SalvatoreCervone\PermissionToolkit\Commands;

use Illuminate\Console\Command;
use SalvatoreCervone\PermissionToolkit\Events\AuditLogsPruned;
use SalvatoreCervone\PermissionToolkit\Models\PermissionAuditLog;

class PruneAuditLogsCommand extends Command
{
    protected $signature = 'permission:audit-prune
                            {--days= : Custom retention days to keep (overrides config retention_days)}';

    protected $description = 'Prune stale permission audit trail logs according to retention policy';

    public function handle(): int
    {
        $optionDays = $this->option('days');
        $days = $optionDays !== null ? (int) $optionDays : config('permission-toolkit.audit.retention_days', 90);

        if ($days === null || (int) $days <= 0) {
            $this->info('Audit log retention is set to indefinite (retention_days is null or 0). No records were pruned.');
            return Command::SUCCESS;
        }

        $cutoff = now()->subDays((int) $days);
        $count = PermissionAuditLog::where('created_at', '<=', $cutoff)->delete();

        event(new AuditLogsPruned($count, (int) $days));

        $this->info("✔ Pruned {$count} audit log record(s) older than {$days} days (before {$cutoff->toDateTimeString()}).");

        return Command::SUCCESS;
    }
}
