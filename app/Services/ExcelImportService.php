<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\DesignTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelImportService
{
    protected array $errors = [];
    protected int $successCount = 0;

    /**
     * 엑셀 파일에서 사원 데이터 일괄 등록
     * 컬럼 순서: 사번, 이름, 영문이름, 부서, 직책, 직급, 이메일, 전화번호, 입사일, 비밀번호
     */
    public function import(UploadedFile $file): array
    {
        $this->errors = [];
        $this->successCount = 0;

        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        // 첫 행은 헤더
        $header = array_shift($rows);

        $defaultTemplate = DesignTemplate::getDefault();

        DB::beginTransaction();
        try {
            foreach ($rows as $rowIdx => $row) {
                $rowNum = $rowIdx + 2; // 엑셀 기준 행 번호
                $data = array_values($row);

                if (empty(trim($data[0] ?? '')) || empty(trim($data[1] ?? ''))) {
                    $this->errors[] = "행 {$rowNum}: 사번 또는 이름이 비어있습니다.";
                    continue;
                }

                $employeeNumber = trim($data[0]);

                if (Employee::where('employee_number', $employeeNumber)->exists()) {
                    $this->errors[] = "행 {$rowNum}: 사번 '{$employeeNumber}'이(가) 이미 존재합니다.";
                    continue;
                }

                try {
                    Employee::create([
                        'employee_number'    => $employeeNumber,
                        'name'               => trim($data[1] ?? ''),
                        'name_en'            => trim($data[2] ?? ''),
                        'department'         => trim($data[3] ?? ''),
                        'position'           => trim($data[4] ?? ''),
                        'rank'               => trim($data[5] ?? ''),
                        'email'              => trim($data[6] ?? ''),
                        'phone'              => trim($data[7] ?? ''),
                        'hire_date'          => !empty($data[8]) ? date('Y-m-d', strtotime($data[8])) : null,
                        'password'           => Hash::make(trim($data[9] ?? $employeeNumber)),
                        'qr_token'           => Employee::generateQrToken(),
                        'qr_generated_at'    => now(),
                        'design_template_id' => $defaultTemplate?->id,
                        'status'             => 'active',
                    ]);
                    $this->successCount++;
                } catch (\Exception $e) {
                    $this->errors[] = "행 {$rowNum}: 등록 실패 - " . $e->getMessage();
                    Log::error("Excel import error at row {$rowNum}", ['error' => $e->getMessage()]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->errors[] = '전체 가져오기 실패: ' . $e->getMessage();
        }

        return [
            'success_count' => $this->successCount,
            'error_count'   => count($this->errors),
            'errors'        => $this->errors,
        ];
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
