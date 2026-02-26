<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\IdCardRenderService;
use Illuminate\Http\Request;

class IdCardController extends Controller
{
    protected IdCardRenderService $renderer;

    public function __construct(IdCardRenderService $renderer)
    {
        $this->renderer = $renderer;
    }

    // 사원증 보기 (반응형 웹)
    public function show()
    {
        $employee = Employee::with('designTemplate.fieldMappings')
            ->findOrFail(session('employee_id'));

        $renderData = $this->renderer->getRenderData($employee);

        return view('user.idcard', compact('employee', 'renderData'));
    }

    // 사원증 이미지 다운로드
    public function download()
    {
        $employee = Employee::findOrFail(session('employee_id'));
        $imageData = $this->renderer->renderImage($employee);

        return response($imageData, 200, [
            'Content-Type'        => 'image/png',
            'Content-Disposition' => 'attachment; filename="my_id_card.png"',
        ]);
    }

    // QR 코드 JSON (AJAX - 실시간 갱신용)
    public function qrData()
    {
        $employee = Employee::findOrFail(session('employee_id'));
        $qrService = app(\App\Services\QrCodeService::class);

        $verifyUrl = $qrService->buildVerifyUrl($employee->qr_token);
        $qrBase64 = $qrService->generateBase64($verifyUrl, 300);

        return response()->json([
            'qr_image'   => $qrBase64,
            'verify_url' => $verifyUrl,
            'generated'  => $employee->qr_generated_at?->format('Y-m-d H:i:s'),
        ]);
    }

    // 안내 가이드 페이지
    public function guide()
    {
        return view('user.guide');
    }
}
