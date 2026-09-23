<?php

namespace SalvatoreCervone\PermissionToolkit\Http\Controllers;

use Illuminate\Routing\Controller;
use SalvatoreCervone\PermissionToolkit\Services\IntegrityChecker;

class DoctorController extends Controller
{
    /**
     * Display the system integrity diagnosis report.
     */
    public function index(IntegrityChecker $checker)
    {
        $report = $checker->diagnose();

        return view('permission-toolkit::doctor.index', compact('report'));
    }
}
