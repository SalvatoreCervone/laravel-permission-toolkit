<?php

namespace SalvatoreCervone\PermissionToolkit\Commands;

use Illuminate\Console\Command;
use SalvatoreCervone\PermissionToolkit\Models\PermissionAuditLog;

class PruneAuditLogsCommand extends Command
{
    protected $signature = 'permission:audit-prune
                            {--days= : Custom retention days to keep (overrides config retention_days)}';

    protected $description = 'Prune stale permission audit trail logs according to retention policy';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('permission-toolkit.audit.retention_days', 90));

        if ($days <= 0) {
            $this->error('Retention days must be a positive integer.');
            return Command::FAILURE;
        }

        $cutoff = now()->subDays($days);
        $count = PermissionAuditLog::where('created_at', '<=', $cutoff)->delete();

        $this->info("✔ Pruned {$count} audit log record(s) older than {$days} days (before {$cutoff->toDateTimeString()}).");

        return Command::SUCCESS;
    }
}
