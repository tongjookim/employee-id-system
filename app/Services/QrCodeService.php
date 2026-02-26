<?php

namespace App\Services;

use chillerlan\QRCode\{QRCode, QROptions};

class QrCodeService
{
    /**
     * QR 코드 PNG 이미지 데이터 반환
     */
    public function generatePng(string $data, int $size = 300): string
    {
        $options = new QROptions([
            'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'     => QRCode::ECC_M,
            'scale'        => max(1, intval($size / 25)),
            'imageBase64'  => false,
            'quietzoneSize' => 2,
        ]);

        return (new QRCode($options))->render($data);
    }

    /**
     * QR 코드 Base64 Data URI 반환 (웹 표시용)
     */
    public function generateBase64(string $data, int $size = 300): string
    {
        $options = new QROptions([
            'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'     => QRCode::ECC_M,
            'scale'        => max(1, intval($size / 25)),
            'imageBase64'  => true,
            'quietzoneSize' => 2,
        ]);

        return (new QRCode($options))->render($data);
    }

    /**
     * 사원 QR 코드 검증 URL 생성
     */
    public function buildVerifyUrl(string $qrToken): string
    {
        return config('app.url') . '/verify/' . $qrToken;
    }

    /**
     * GD 이미지 리소스로 QR 코드 생성 (이미지 합성용)
     */
    public function generateGdImage(string $data, int $size = 180): \GdImage|false
    {
        $pngData = $this->generatePng($data, $size);
        return imagecreatefromstring($pngData);
    }
}
