<?php

namespace SalvatoreCervone\PermissionToolkit\Commands;

use Illuminate\Console\Command;
use SalvatoreCervone\PermissionToolkit\Services\AuthorizationSimulator;

class SimulatePermissionCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'permission:simulate
                            {user : User ID, Email, or Username}
                            {ability : Permission or ability name to evaluate}
                            {--model= : Optional target Eloquent model class (e.g. App\\Models\\Invoice)}
                            {--id= : Optional target Eloquent model record ID}';

    /**
     * The console command description.
     */
    protected $description = 'Simulate and diagnose why a user is ALLOWED or DENIED a specific permission or ability';

    /**
     * Execute the console command.
     */
    public function handle(AuthorizationSimulator $simulator): int
    {
        $userIdentifier = $this->argument('user');
        $ability = $this->argument('ability');
        $modelClass = $this->option('model');
        $modelId = $this->option('id');

        $userModelClass = config('permission-toolkit.user_model')
            ?? config('auth.providers.users.model', 'App\\Models\\User');

        if (! class_exists($userModelClass)) {
            $this->error("Configured user model [{$userModelClass}] does not exist.");
            return Command::FAILURE;
        }

        $userQuery = (new $userModelClass)->newQuery();
        if (is_numeric($userIdentifier)) {
            $userQuery->where('id', $userIdentifier);
        } else {
            $userQuery->where(function ($q) use ($userIdentifier) {
                $q->where('email', $userIdentifier);
                if (\Illuminate\Support\Facades\Schema::hasColumn($q->getModel()->getTable(), 'username')) {
                    $q->orWhere('username', $userIdentifier);
                }
            });
        }

        $user = $userQuery->first();

        if (! $user) {
            $this->error("User not found matching [{$userIdentifier}].");
            return Command::FAILURE;
        }

        $targetInstance = null;
        if ($modelClass) {
            if (! class_exists($modelClass)) {
                $this->error("Target model class [{$modelClass}] does not exist.");
                return Command::FAILURE;
            }

            if ($modelId) {
                $targetInstance = (new $modelClass)->newQuery()->find($modelId);
                if (! $targetInstance) {
                    $this->error("Target model instance [{$modelClass} #{$modelId}] not found.");
                    return Command::FAILURE;
                }
            } else {
                $targetInstance = $modelClass;
            }
        }

        $this->info("===============================================================");
        $this->info(" 🛡️  PERMISSIONS DIAGNOSTIC SIMULATOR (AWS IAM STYLE)");
        $this->info("===============================================================");
        $this->line("Target User  : " . ($user->name ?? $user->email) . " (ID: {$user->id})");
        $this->line("User Roles   : " . implode(', ', method_exists($user, 'getRoleNames') ? $user->getRoleNames()->toArray() : ['N/A']));
        $this->line("Testing Ability : <comment>{$ability}</comment>");
        if ($targetInstance) {
            $this->line("Target Model : " . (is_object($targetInstance) ? get_class($targetInstance) . " #{$targetInstance->id}" : $targetInstance));
        }
        $this->newLine();

        $result = $simulator->simulate($user, $ability, $targetInstance);

        $this->line("<options=bold>Trace Breakdown:</options=bold>");
        $tableRows = [];
        foreach ($result['steps'] as $index => $step) {
            $badge = match ($step['status']) {
                'PASS' => '<fg=green;options=bold> ✔ PASS </fg=green;options=bold>',
                'FAIL' => '<fg=red;options=bold> ✘ FAIL </fg=red;options=bold>',
                default => '<fg=yellow> — SKIP </fg=yellow>',
            };

            $tableRows[] = [
                ($index + 1),
                $step['step'],
                $badge,
                $step['detail'],
            ];
        }

        $this->table(['#', 'Inspection Step', 'Status', 'Diagnostic Detail'], $tableRows);

        $this->newLine();
        if ($result['is_allowed']) {
            $this->info("===============================================================");
            $this->info(" FINAL VERDICT: [ ALLOWED ]");
            $this->line(" Reason: {$result['reason']}");
            $this->info("===============================================================");
        } else {
            $this->error("===============================================================");
            $this->error(" FINAL VERDICT: [ DENIED ]");
            $this->line(" Reason: {$result['reason']}");
            $this->error("===============================================================");
        }

        return $result['is_allowed'] ? Command::SUCCESS : Command::FAILURE;
    }
}
