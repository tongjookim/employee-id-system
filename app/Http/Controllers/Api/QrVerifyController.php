<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\QrAccessLog;
use Illuminate\Http\Request;

class QrVerifyController extends Controller
{
    /**
     * QR 웹 검증 — 유효시간 체크 포함
     */
    public function verify(Request $request, string $token)
    {
        $employee = Employee::where('qr_token', $token)
            ->where('status', 'active')
            ->first();

        // 유효시간 만료 체크
        $isExpired = $employee && $employee->isQrExpired();
        $isValid = $employee && !$isExpired;

        QrAccessLog::create([
            'employee_id' => $employee?->id ?? 0,
            'qr_token'    => $token,
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
            'access_type' => 'verify',
            'is_valid'    => $isValid,
        ]);

        if (!$employee) {
            return view('user.qr_invalid', ['reason' => 'not_found']);
        }

        if ($isExpired) {
            return view('user.qr_invalid', ['reason' => 'expired']);
        }

        return view('user.qr_verify', compact('employee'));
    }

    /**
     * QR JSON API 검증 — 유효시간 체크 포함
     */
    public function apiVerify(Request $request, string $token)
    {
        $employee = Employee::where('qr_token', $token)
            ->where('status', 'active')
            ->first();

        $isExpired = $employee && $employee->isQrExpired();
        $isValid = $employee && !$isExpired;

        QrAccessLog::create([
            'employee_id' => $employee?->id ?? 0,
            'qr_token'    => $token,
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
            'access_type' => 'scan',
            'is_valid'    => $isValid,
        ]);

        if (!$employee) {
            return response()->json([
                'valid' => false,
                'message' => '유효하지 않은 QR 코드입니다.',
            ], 404);
        }

        if ($isExpired) {
            return response()->json([
                'valid' => false,
                'message' => 'QR 코드가 만료되었습니다. 사원증 앱에서 새 QR을 발급받으세요.',
                'expired_at' => $employee->qr_expires_at?->toIso8601String(),
            ], 410);
        }

        return response()->json([
            'valid'           => true,
            'name'            => $employee->name,
            'department'      => $employee->department,
            'position'        => $employee->position,
            'employee_number' => $employee->employee_number,
            'verified_at'     => now()->format('Y-m-d H:i:s'),
        ]);
    }
}
