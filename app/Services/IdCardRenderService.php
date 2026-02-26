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
    public function renderImage(Employee $employee, bool $withWatermark = false): string
    {
        $template = $employee->getActiveTemplate();
        if (!$template) {
            throw new \RuntimeException('디자인 템플릿이 설정되지 않았습니다.');
        }

        $bgPath = storage_path('app/public/' . $template->background_image);
        if (!file_exists($bgPath)) {
            $canvas = imagecreatetruecolor($template->canvas_width, $template->canvas_height);
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefill($canvas, 0, 0, $white);
        } else {
            $canvas = $this->loadImage($bgPath);
            if (!$canvas) {
                throw new \RuntimeException('배경 이미지를 로드할 수 없습니다.');
            }
        }

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

        // 워터마크 추가
        if ($withWatermark) {
            $this->applyWatermark($canvas, $employee);
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

    /**
     * ★ 텍스트 렌더링 — 한글 폰트 자동 탐색 + 폴백
     */
    private function renderText(\GdImage $canvas, FieldMapping $mapping, Employee $employee): void
    {
        $text = $employee->getFieldValue($mapping->field_key);
        if (empty($text)) return;

        // UTF-8 인코딩 보장
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        $color = $this->hexToColor($canvas, $mapping->font_color);
        $fontSize = $mapping->font_size;

        // TrueType 폰트 경로 (한글 지원 폰트 탐색)
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
            // ★ TTF가 없을 때 — GD 기본 폰트로 한글 출력 불가 경고 텍스트 표시
            // GD 기본 폰트는 ASCII만 지원하므로 한글이 깨짐
            // 시스템 폰트에서 한글 폰트 자동 검색 시도
            $systemFont = $this->findSystemKoreanFont();

            if ($systemFont) {
                $bbox = imagettfbbox($fontSize, 0, $systemFont, $text);
                $textWidth = abs($bbox[2] - $bbox[0]);

                $x = $mapping->pos_x;
                if ($mapping->text_align === 'center') {
                    $x = $mapping->pos_x - ($textWidth / 2);
                } elseif ($mapping->text_align === 'right') {
                    $x = $mapping->pos_x - $textWidth;
                }

                imagettftext($canvas, $fontSize, 0, (int)$x, $mapping->pos_y + $fontSize, $color, $systemFont, $text);
            } else {
                // 최후 폴백: ASCII 부분만 표시
                $gdFont = min(5, max(1, intval($fontSize / 6)));
                imagestring($canvas, $gdFont, $mapping->pos_x, $mapping->pos_y, $text, $color);
            }
        }
    }

    /**
     * ★ 시스템에 설치된 한글 TTF 폰트 자동 탐색
     */
    private function findSystemKoreanFont(): ?string
    {
        $candidates = [
            // 앱 내 fonts 디렉토리
            storage_path('app/fonts/NanumGothic.ttf'),
            storage_path('app/fonts/NanumGothicBold.ttf'),
            storage_path('app/fonts/NanumBarunGothic.ttf'),
            storage_path('app/fonts/NotoSansKR-Regular.ttf'),
            storage_path('app/fonts/NotoSansKR-Medium.ttf'),
            storage_path('app/fonts/malgun.ttf'),

            // Ubuntu/Debian 시스템 폰트
            '/usr/share/fonts/truetype/nanum/NanumGothic.ttf',
            '/usr/share/fonts/truetype/nanum/NanumBarunGothic.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansKR-Regular.ttf',
            '/usr/share/fonts/opentype/noto/NotoSansCJK-Regular.ttc',
            '/usr/share/fonts/truetype/unfonts-core/UnBatang.ttf',
            '/usr/share/fonts/truetype/unfonts-core/UnDotum.ttf',

            // CentOS/RHEL 시스템 폰트
            '/usr/share/fonts/korean/TrueType/NanumGothic.ttf',
            '/usr/share/fonts/google-noto-cjk/NotoSansCJK-Regular.ttc',

            // macOS (개발환경)
            '/Library/Fonts/AppleGothic.ttf',
            '/System/Library/Fonts/AppleSDGothicNeo.ttc',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) return $path;
        }

        // 폰트 디렉토리 내 *.ttf 아무거나 찾기
        $fontsDir = storage_path('app/fonts');
        if (is_dir($fontsDir)) {
            $files = glob($fontsDir . '/*.ttf');
            if (!empty($files)) return $files[0];
            $files = glob($fontsDir . '/*.TTF');
            if (!empty($files)) return $files[0];
        }

        return null;
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
        // 투명도 보존
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
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
            $white = imagecolorallocate($resized, 255, 255, 255);
            imagefill($resized, 0, 0, $white);
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
        if (strlen($hex) < 6) $hex = str_pad($hex, 6, '0');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return imagecolorallocate($image, $r, $g, $b);
    }

    /**
     * ★ 폰트 경로 탐색 (앱 fonts → 시스템 폰트 순)
     */
    private function getFontPath(?string $fontFamily, bool $isBold = false): ?string
    {
        $fontsDir = storage_path('app/fonts');
        $suffix = $isBold ? 'Bold' : '';
        $fontFamily = $fontFamily ?: 'NanumGothic';

        $candidates = [
            "{$fontsDir}/{$fontFamily}{$suffix}.ttf",
            "{$fontsDir}/{$fontFamily}.ttf",
            "{$fontsDir}/NanumGothic{$suffix}.ttf",
            "{$fontsDir}/NanumGothic.ttf",
            "{$fontsDir}/NotoSansKR-Regular.ttf",
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) return $path;
        }

        // 시스템 폰트 폴백
        return $this->findSystemKoreanFont();
    }

    /**
     * 다운로드용 워터마크 합성
     */
    private function applyWatermark(\GdImage $canvas, Employee $employee): void
    {
        $watermarkText = $employee->employee_number . ' / ' . now()->format('Y-m-d H:i');
        $fontSize = 14;
        $fontPath = $this->getFontPath(null, false); // 기본 한글 폰트 가져오기

        // 반투명 색상 생성 (알파값 0~127: 127이 완전 투명)
        $color = imagecolorallocatealpha($canvas, 150, 150, 150, 80);

        if ($fontPath && file_exists($fontPath)) {
            $width = imagesx($canvas);
            $height = imagesy($canvas);

            // 캔버스 전체에 사선(30도)으로 워터마크 반복 배치
            for ($y = 50; $y < $height + 200; $y += 150) {
                for ($x = -50; $x < $width; $x += 250) {
                    imagettftext($canvas, $fontSize, 30, $x, $y, $color, $fontPath, $watermarkText);
                }
            }
        }
    }
}
