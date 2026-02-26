<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\DesignTemplate;
use App\Models\QrAccessLog;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_employees'  => Employee::count(),
            'active_employees' => Employee::where('status', 'active')->count(),
            'templates'        => DesignTemplate::where('is_active', true)->count(),
            'qr_scans_today'   => QrAccessLog::whereDate('created_at', today())->count(),
        ];

        $recentEmployees = Employee::latest()->take(10)->get();
        $recentLogs = QrAccessLog::with('employee')->latest('created_at')->take(20)->get();

        return view('admin.dashboard', compact('stats', 'recentEmployees', 'recentLogs'));
    }
}
