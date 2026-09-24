<?php

namespace SalvatoreCervone\PermissionToolkit\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use SalvatoreCervone\PermissionToolkit\Services\AuthorizationSimulator;
use SalvatoreCervone\PermissionToolkit\Tests\TestCase;
use SalvatoreCervone\PermissionToolkit\Tests\User;

class SimulatorGlobalGateTest extends TestCase
{
    /** @test */
    public function it_evaluates_global_gates_without_target_in_simulator()
    {
        $user = User::create([
            'name' => 'Report Viewer',
            'email' => 'viewer@test.com',
        ]);

        Gate::define('export-financial-reports', function ($u) {
            return $u->email === 'viewer@test.com';
        });

        $simulator = new AuthorizationSimulator();
        $result = $simulator->simulate($user, 'export-financial-reports', null);

        $this->assertEquals('ALLOWED', $result['verdict']);
        $this->assertTrue($result['is_allowed']);

        // Check that Policy/Gate step was PASS (not SKIP)
        $gateStep = collect($result['steps'])->firstWhere('step', 'Policy / Gate Evaluation');
        $this->assertNotNull($gateStep);
        $this->assertEquals('PASS', $gateStep['status']);
    }
}
