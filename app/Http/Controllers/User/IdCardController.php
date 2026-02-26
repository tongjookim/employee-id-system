<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\IdCardRenderService;
use App\Services\QrCodeService;
use Illuminate\Http\Request;

class IdCardController extends Controller
{
    protected IdCardRenderService $renderer;
    protected QrCodeService $qrService;

    public function __construct(IdCardRenderService $renderer, QrCodeService $qrService)
    {
        $this->renderer = $renderer;
        $this->qrService = $qrService;
    }

    public function show()
    {
        $employeeId = session('employee_id');
        $ttl = (int) config('idcard.qr_ttl_seconds', 10);
        
        // ★ 1) 기존 모델 save() 대신 즉시 DB 업데이트(Atomic) 실행 - 2번 새로고침 문제 해결
        Employee::where('id', $employeeId)->update([
            'qr_token' => Employee::generateQrToken(),
            'qr_generated_at' => now(),
            'qr_expires_at' => now()->addSeconds($ttl),
        ]);

        // ★ 2) 완전한 최신 데이터로 로드
        $employee = Employee::with('designTemplate.fieldMappings')->findOrFail($employeeId);
        $renderData = $this->renderer->getRenderData($employee);

        $qrTtl = $ttl;
        $qrExpiresAt = $employee->qr_expires_at->toIso8601String();
        $qrRemaining = $employee->qrRemainingSeconds();

        // ★ 강력한 캐시 무효화 헤더 적용 (Cloudflare, iOS Safari 대응)
        return response()
            ->view('user.idcard', compact('employee', 'renderData', 'qrTtl', 'qrExpiresAt', 'qrRemaining'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT'); 
    }

    public function download()
    {
        $employeeId = session('employee_id');
        $ttl = (int) config('idcard.qr_ttl_seconds', 10);
        
        Employee::where('id', $employeeId)->update([
            'qr_token' => Employee::generateQrToken(),
            'qr_generated_at' => now(),
            'qr_expires_at' => now()->addSeconds($ttl),
        ]);

        $employee = Employee::with('designTemplate.fieldMappings')->findOrFail($employeeId);
        $imageData = $this->renderer->renderImage($employee, true); // 워터마크 적용 포함

        $filename = $employee->name . '_사원증.png';
        $encodedFilename = rawurlencode($filename);

        return response($imageData, 200, [
            'Content-Type'        => 'image/png',
            'Content-Disposition' => "attachment; filename=\"id_card.png\"; filename*=UTF-8''{$encodedFilename}",
        ]);
    }

    public function qrData()
    {
        $employeeId = session('employee_id');
        $ttl = (int) config('idcard.qr_ttl_seconds', 10);
        $newToken = Employee::generateQrToken();
        $now = now();

        Employee::where('id', $employeeId)->update([
            'qr_token' => $newToken,
            'qr_generated_at' => $now,
            'qr_expires_at' => $now->copy()->addSeconds($ttl),
        ]);

        $verifyUrl = $this->qrService->buildVerifyUrl($newToken);
        $qrBase64 = $this->qrService->generateBase64($verifyUrl, 300);

        return response()->json([
            'qr_image'     => $qrBase64,
            'verify_url'   => $verifyUrl,
            'qr_token'     => $newToken,
            'generated_at' => $now->toIso8601String(),
            'expires_at'   => $now->copy()->addSeconds($ttl)->toIso8601String(),
            'ttl_seconds'  => $ttl,
            'remaining'    => $ttl,
        ], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
        ]);
    }

    public function guide()
    {
        return view('user.guide');
    }

    public function scanner()
    {
        return view('user.scanner');
    }
}
