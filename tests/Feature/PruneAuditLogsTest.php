<?php

namespace SalvatoreCervone\PermissionToolkit\Tests\Feature;

use Illuminate\Support\Carbon;
use SalvatoreCervone\PermissionToolkit\Models\PermissionAuditLog;
use SalvatoreCervone\PermissionToolkit\Tests\TestCase;
use SalvatoreCervone\PermissionToolkit\Tests\User;

class PruneAuditLogsTest extends TestCase
{
    /** @test */
    public function it_prunes_audit_logs_older_than_retention_days()
    {
        $user = User::create(['name' => 'Actor', 'email' => 'actor@test.com']);

        // Create log from 120 days ago (should be pruned)
        PermissionAuditLog::create([
            'user_type' => get_class($user),
            'user_id' => $user->id,
            'causer_type' => get_class($user),
            'causer_id' => $user->id,
            'action' => 'assigned',
            'type' => 'role',
            'target_name' => 'old-role',
            'created_at' => Carbon::now()->subDays(120),
        ]);

        // Create log from 10 days ago (should be kept with default 90 days)
        PermissionAuditLog::create([
            'user_type' => get_class($user),
            'user_id' => $user->id,
            'causer_type' => get_class($user),
            'causer_id' => $user->id,
            'action' => 'assigned',
            'type' => 'role',
            'target_name' => 'recent-role',
            'created_at' => Carbon::now()->subDays(10),
        ]);

        $this->assertEquals(2, PermissionAuditLog::count());

        $this->artisan('permission:audit-prune')
            ->expectsOutputToContain('Pruned 1 audit log record(s)')
            ->assertSuccessful();

        $this->assertEquals(1, PermissionAuditLog::count());
        $this->assertDatabaseHas('permission_audit_logs', ['target_name' => 'recent-role']);
        $this->assertDatabaseMissing('permission_audit_logs', ['target_name' => 'old-role']);
    }

    /** @test */
    public function it_supports_custom_days_option()
    {
        $user = User::create(['name' => 'Actor', 'email' => 'actor2@test.com']);

        PermissionAuditLog::create([
            'user_type' => get_class($user),
            'user_id' => $user->id,
            'causer_type' => get_class($user),
            'causer_id' => $user->id,
            'action' => 'assigned',
            'type' => 'role',
            'target_name' => 'five-days-old',
            'created_at' => Carbon::now()->subDays(5),
        ]);

        // Pruning with --days=3 should delete the record
        $this->artisan('permission:audit-prune', ['--days' => 3])
            ->expectsOutputToContain('Pruned 1 audit log record(s)')
            ->assertSuccessful();

        $this->assertEquals(0, PermissionAuditLog::count());
    }
}
