<?php

namespace SalvatoreCervone\PermissionToolkit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use SalvatoreCervone\PermissionToolkit\Models\PermissionAuditLog;

class AuditLogController extends Controller
{
    /**
     * Display a paginated listing of permission modifications.
     */
    public function index(Request $request)
    {
        $tableName = config('permission-toolkit.audit.table', 'permission_audit_logs');

        if (! Schema::hasTable($tableName)) {
            return view('permission-toolkit::audit.index', [
                'logs' => collect(),
                'tableExists' => false,
            ]);
        }

        $query = PermissionAuditLog::with(['causer', 'user'])
            ->orderBy('id', 'desc');

        if ($action = $request->get('action')) {
            $query->where('action', $action);
        }

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('target_name', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(25)->withQueryString();

        return view('permission-toolkit::audit.index', [
            'logs' => $logs,
            'tableExists' => true,
        ]);
    }
}
