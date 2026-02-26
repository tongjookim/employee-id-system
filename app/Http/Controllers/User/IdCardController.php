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

    /**
     * 사원증 보기 — 페이지 로드 시 QR 강제 재생성
     */
    public function show()
    {
        $employee = Employee::findOrFail(session('employee_id'));

        // ★ 1) 먼저 QR 토큰 재생성 (DB 직접 업데이트)
        $ttl = (int) config('idcard.qr_ttl_seconds', 10);
        $newToken = Employee::generateQrToken();
        $now = now();

        $employee->qr_token = $newToken;
        $employee->qr_generated_at = $now;
        $employee->qr_expires_at = $now->copy()->addSeconds($ttl);
        $employee->save();

        // ★ 2) 관계 포함 새로 로드 (캐시된 관계 데이터 방지)
        $employee = Employee::with('designTemplate.fieldMappings')
            ->findOrFail(session('employee_id'));

        // ★ 3) 렌더 데이터 생성 (새 QR 토큰 반영됨)
        $renderData = $this->renderer->getRenderData($employee);

        $qrTtl = $ttl;
        $qrExpiresAt = $employee->qr_expires_at->toIso8601String();
        $qrRemaining = $employee->qrRemainingSeconds();

        // ★ 4) 캐시 방지 헤더
        return response()
            ->view('user.idcard', compact(
                'employee', 'renderData', 'qrTtl', 'qrExpiresAt', 'qrRemaining'
            ))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * 사원증 이미지 다운로드
     */
    public function download()
    {
        $employee = Employee::findOrFail(session('employee_id'));

        $ttl = (int) config('idcard.qr_ttl_seconds', 10);
        $employee->qr_token = Employee::generateQrToken();
        $employee->qr_generated_at = now();
        $employee->qr_expires_at = now()->addSeconds($ttl);
        $employee->save();

        $employee->refresh();

        $imageData = $this->renderer->renderImage($employee);

        $filename = $employee->name . '_사원증.png';
        $encodedFilename = rawurlencode($filename);

        return response($imageData, 200, [
            'Content-Type'        => 'image/png',
            'Content-Disposition' => "attachment; filename=\"id_card.png\"; filename*=UTF-8''{$encodedFilename}",
            'Content-Length'      => strlen($imageData),
        ]);
    }

    /**
     * QR 코드 AJAX 갱신 — 매 호출 시 새 토큰 발급
     */
    public function qrData()
    {
        $employee = Employee::findOrFail(session('employee_id'));

        $ttl = config('idcard.qr_ttl_seconds', 10);
        $newToken = Employee::generateQrToken();
        $now = now();

        $employee->qr_token = $newToken;
        $employee->qr_generated_at = $now;
        $employee->qr_expires_at = $now->copy()->addSeconds($ttl);
        $employee->save();

        $verifyUrl = $this->qrService->buildVerifyUrl($newToken);
        $qrBase64 = $this->qrService->generateBase64($verifyUrl, 300);

        return response()->json([
            'qr_image'     => $qrBase64,
            'verify_url'   => $verifyUrl,
            'qr_token'     => $newToken,
            'generated_at' => $now->toIso8601String(),
            'expires_at'   => $employee->qr_expires_at->toIso8601String(),
            'ttl_seconds'  => $ttl,
            'remaining'    => $ttl,
        ], 200, [
            'Cache-Control' => 'no-store, no-cache',
            'Pragma'        => 'no-cache',
        ]);
    }

    public function guide()
    {
        return view('user.guide');
    }
}
