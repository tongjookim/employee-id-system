<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\QrAccessLog;
use Illuminate\Http\Request;

class QrVerifyController extends Controller
{
    public function verify(Request $request, string $token)
    {
        $employee = Employee::where('qr_token', $token)->where('status', 'active')->first();

        QrAccessLog::create([
            'employee_id' => $employee?->id ?? 0,
            'qr_token'    => $token,
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
            'access_type' => 'verify',
            'is_valid'    => (bool) $employee,
        ]);

        if (!$employee) {
            return view('user.qr_invalid');
        }

        return view('user.qr_verify', compact('employee'));
    }

    public function apiVerify(Request $request, string $token)
    {
        $employee = Employee::where('qr_token', $token)->where('status', 'active')->first();

        QrAccessLog::create([
            'employee_id' => $employee?->id ?? 0,
            'qr_token'    => $token,
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
            'access_type' => 'scan',
            'is_valid'    => (bool) $employee,
        ]);

        if (!$employee) {
            return response()->json(['valid' => false, 'message' => '유효하지 않은 QR 코드입니다.'], 404);
        }

        return response()->json([
            'valid' => true, 'name' => $employee->name,
            'department' => $employee->department, 'position' => $employee->position,
            'employee_number' => $employee->employee_number,
            'verified_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }
}
