<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\DesignTemplate;
use App\Models\FieldMapping;

class IdCardRenderService
{
    protected QrCodeService $qrService;

    public function __construct(QrCodeService $qrService)
    {
        $this->qrService = $qrService;
    }

    /**
     * 사원증 이미지 합성 (GD Library)
     * @return string PNG 이미지 바이너리
     */
    public function renderImage(Employee $employee): string
    {
        $template = $employee->getActiveTemplate();
        if (!$template) {
            throw new \RuntimeException('디자인 템플릿이 설정되지 않았습니다.');
        }

        $bgPath = storage_path('app/public/' . $template->background_image);
        if (!file_exists($bgPath)) {
            // 배경 없으면 빈 캔버스 생성
            $canvas = imagecreatetruecolor($template->canvas_width, $template->canvas_height);
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefill($canvas, 0, 0, $white);
        } else {
            $canvas = $this->loadImage($bgPath);
            if (!$canvas) {
                throw new \RuntimeException('배경 이미지를 로드할 수 없습니다.');
            }
        }

        // 캔버스 크기 맞추기
        $canvas = $this->resizeCanvas($canvas, $template->canvas_width, $template->canvas_height);

        // 필드 매핑 순회하며 합성
        $mappings = $template->fieldMappings()->where('is_visible', true)->get();

        foreach ($mappings as $mapping) {
            switch ($mapping->field_type) {
                case 'text':
                    $this->renderText($canvas, $mapping, $employee);
                    break;
                case 'image':
                    $this->renderPhoto($canvas, $mapping, $employee);
                    break;
                case 'qr_code':
                    $this->renderQrCode($canvas, $mapping, $employee);
                    break;
            }
        }

        // PNG 출력
        ob_start();
        imagepng($canvas, null, 6);
        $imageData = ob_get_clean();
        imagedestroy($canvas);

        return $imageData;
    }

    /**
     * CSS 기반 렌더링 데이터 반환 (모바일 실시간 렌더링용)
     */
    public function getRenderData(Employee $employee): array
    {
        $template = $employee->getActiveTemplate();
        if (!$template) {
            return ['error' => '템플릿 없음'];
        }

        $mappings = $template->fieldMappings()->where('is_visible', true)->get();
        $fields = [];

        foreach ($mappings as $mapping) {
            $fieldData = [
                'field_key'  => $mapping->field_key,
                'field_type' => $mapping->field_type,
                'label'      => $mapping->label,
                'pos_x'      => $mapping->pos_x,
                'pos_y'      => $mapping->pos_y,
                'width'      => $mapping->width,
                'height'     => $mapping->height,
                'font_size'  => $mapping->font_size,
                'font_color' => $mapping->font_color,
                'font_family'=> $mapping->font_family,
                'text_align' => $mapping->text_align,
                'is_bold'    => $mapping->is_bold,
            ];

            if ($mapping->field_type === 'text') {
                $fieldData['value'] = $employee->getFieldValue($mapping->field_key) ?? '';
            } elseif ($mapping->field_type === 'image') {
                $fieldData['value'] = $employee->photo
                    ? asset('storage/' . $employee->photo)
                    : asset('images/default_photo.png');
            } elseif ($mapping->field_type === 'qr_code') {
                $verifyUrl = $this->qrService->buildVerifyUrl($employee->qr_token);
                $fieldData['value'] = $this->qrService->generateBase64($verifyUrl, $mapping->width ?? 180);
            }

            $fields[] = $fieldData;
        }

        return [
            'template' => [
                'id'               => $template->id,
                'name'             => $template->name,
                'background_image' => asset('storage/' . $template->background_image),
                'canvas_width'     => $template->canvas_width,
                'canvas_height'    => $template->canvas_height,
            ],
            'employee' => [
                'id'              => $employee->id,
                'employee_number' => $employee->employee_number,
                'name'            => $employee->name,
            ],
            'fields' => $fields,
        ];
    }

    // ── Private helpers ──

    private function renderText(\GdImage $canvas, FieldMapping $mapping, Employee $employee): void
    {
        $text = $employee->getFieldValue($mapping->field_key);
        if (empty($text)) return;

        $color = $this->hexToColor($canvas, $mapping->font_color);
        $fontSize = $mapping->font_size;

        // TrueType 폰트 경로
        $fontPath = $this->getFontPath($mapping->font_family, $mapping->is_bold);

        if ($fontPath && file_exists($fontPath)) {
            // TTF 렌더링
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);
            $textWidth = abs($bbox[2] - $bbox[0]);

            $x = $mapping->pos_x;
            if ($mapping->text_align === 'center') {
                $x = $mapping->pos_x - ($textWidth / 2);
            } elseif ($mapping->text_align === 'right') {
                $x = $mapping->pos_x - $textWidth;
            }

            imagettftext($canvas, $fontSize, 0, (int)$x, $mapping->pos_y + $fontSize, $color, $fontPath, $text);
        } else {
            // 기본 GD 폰트 사용 (폴백)
            $gdFont = min(5, max(1, intval($fontSize / 6)));
            imagestring($canvas, $gdFont, $mapping->pos_x, $mapping->pos_y, $text, $color);
        }
    }

    private function renderPhoto(\GdImage $canvas, FieldMapping $mapping, Employee $employee): void
    {
        $photoPath = $employee->photo
            ? storage_path('app/public/' . $employee->photo)
            : public_path('images/default_photo.png');

        if (!file_exists($photoPath)) return;

        $photo = $this->loadImage($photoPath);
        if (!$photo) return;

        $w = $mapping->width ?? 200;
        $h = $mapping->height ?? 260;

        $resized = imagecreatetruecolor($w, $h);
        imagecopyresampled($resized, $photo, 0, 0, 0, 0, $w, $h, imagesx($photo), imagesy($photo));
        imagecopy($canvas, $resized, $mapping->pos_x, $mapping->pos_y, 0, 0, $w, $h);

        imagedestroy($photo);
        imagedestroy($resized);
    }

    private function renderQrCode(\GdImage $canvas, FieldMapping $mapping, Employee $employee): void
    {
        $verifyUrl = $this->qrService->buildVerifyUrl($employee->qr_token);
        $size = $mapping->width ?? 180;
        $qrImage = $this->qrService->generateGdImage($verifyUrl, $size);

        if ($qrImage) {
            $w = $mapping->width ?? 180;
            $h = $mapping->height ?? 180;
            $resized = imagecreatetruecolor($w, $h);
            imagecopyresampled($resized, $qrImage, 0, 0, 0, 0, $w, $h, imagesx($qrImage), imagesy($qrImage));
            imagecopy($canvas, $resized, $mapping->pos_x, $mapping->pos_y, 0, 0, $w, $h);
            imagedestroy($qrImage);
            imagedestroy($resized);
        }
    }

    private function loadImage(string $path): \GdImage|false
    {
        $info = @getimagesize($path);
        if (!$info) return false;

        return match ($info[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => imagecreatefrompng($path),
            IMAGETYPE_GIF  => imagecreatefromgif($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default        => false,
        };
    }

    private function resizeCanvas(\GdImage $source, int $targetW, int $targetH): \GdImage
    {
        $srcW = imagesx($source);
        $srcH = imagesy($source);

        if ($srcW === $targetW && $srcH === $targetH) {
            return $source;
        }

        $canvas = imagecreatetruecolor($targetW, $targetH);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetW, $targetH, $srcW, $srcH);
        imagedestroy($source);

        return $canvas;
    }

    private function hexToColor(\GdImage $image, string $hex): int
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return imagecolorallocate($image, $r, $g, $b);
    }

    private function getFontPath(string $fontFamily, bool $isBold = false): ?string
    {
        $fontsDir = storage_path('app/fonts');
        $suffix = $isBold ? 'Bold' : '';

        $candidates = [
            "{$fontsDir}/{$fontFamily}{$suffix}.ttf",
            "{$fontsDir}/{$fontFamily}.ttf",
            "{$fontsDir}/NanumGothic{$suffix}.ttf",
            "{$fontsDir}/NanumGothic.ttf",
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) return $path;
        }

        return null;
    }
}
