<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\DesignTemplate;
use App\Services\ExcelImportService;
use App\Services\IdCardRenderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('employee_number', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%");
            });
        }

        if ($dept = $request->input('department')) {
            $query->where('department', $dept);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $employees = $query->orderBy('name')->paginate(20)->withQueryString();
        $departments = Employee::distinct()->pluck('department')->filter();

        return view('admin.employees.index', compact('employees', 'departments'));
    }

    public function create()
    {
        $templates = DesignTemplate::where('is_active', true)->get();
        return view('admin.employees.form', ['employee' => null, 'templates' => $templates]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_number'    => 'required|string|max:50|unique:employees',
            'password'           => 'required|string|min:4',
            'name'               => 'required|string|max:100',
            'name_en'            => 'nullable|string|max:100',
            'department'         => 'nullable|string|max:100',
            'position'           => 'nullable|string|max:100',
            'rank'               => 'nullable|string|max:100',
            'email'              => 'nullable|email|max:255',
            'phone'              => 'nullable|string|max:20',
            'photo'              => 'nullable|image|max:5120',
            'hire_date'          => 'nullable|date',
            'birth_date'         => 'nullable|date',
            'blood_type'         => 'nullable|string|max:10',
            'address'            => 'nullable|string',
            'design_template_id' => 'nullable|exists:design_templates,id',
            'status'             => 'required|in:active,inactive,suspended',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['qr_token'] = Employee::generateQrToken();
        $validated['qr_generated_at'] = now();

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('photos', 'public');
        }

        if (empty($validated['design_template_id'])) {
            $validated['design_template_id'] = DesignTemplate::getDefault()?->id;
        }

        Employee::create($validated);

        return redirect()->route('admin.employees.index')
            ->with('success', '직원이 등록되었습니다.');
    }

    public function edit(Employee $employee)
    {
        $templates = DesignTemplate::where('is_active', true)->get();
        return view('admin.employees.form', compact('employee', 'templates'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'employee_number'    => ['required', 'string', 'max:50', Rule::unique('employees')->ignore($employee->id)],
            'password'           => 'nullable|string|min:4',
            'name'               => 'required|string|max:100',
            'name_en'            => 'nullable|string|max:100',
            'department'         => 'nullable|string|max:100',
            'position'           => 'nullable|string|max:100',
            'rank'               => 'nullable|string|max:100',
            'email'              => 'nullable|email|max:255',
            'phone'              => 'nullable|string|max:20',
            'photo'              => 'nullable|image|max:5120',
            'hire_date'          => 'nullable|date',
            'birth_date'         => 'nullable|date',
            'blood_type'         => 'nullable|string|max:10',
            'address'            => 'nullable|string',
            'design_template_id' => 'nullable|exists:design_templates,id',
            'status'             => 'required|in:active,inactive,suspended',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        if ($request->hasFile('photo')) {
            // 기존 사진 삭제
            if ($employee->photo) {
                Storage::disk('public')->delete($employee->photo);
            }
            $validated['photo'] = $request->file('photo')->store('photos', 'public');
        }

        $employee->update($validated);

        return redirect()->route('admin.employees.index')
            ->with('success', '직원 정보가 수정되었습니다.');
    }

    public function destroy(Employee $employee)
    {
        if ($employee->photo) {
            Storage::disk('public')->delete($employee->photo);
        }
        $employee->delete();

        return redirect()->route('admin.employees.index')
            ->with('success', '직원이 삭제되었습니다.');
    }

    // QR 토큰 재생성
    public function regenerateQr(Employee $employee)
    {
        $employee->regenerateQrToken();

        return back()->with('success', 'QR 코드가 재생성되었습니다.');
    }

    // 사원증 이미지 미리보기
    public function previewIdCard(Employee $employee, IdCardRenderService $renderer)
    {
        $imageData = $renderer->renderImage($employee);

        return response($imageData, 200, [
            'Content-Type'        => 'image/png',
            'Content-Disposition' => 'inline; filename="id_card_' . $employee->employee_number . '.png"',
        ]);
    }

    // 엑셀 일괄 등록
    public function importExcel(Request $request, ExcelImportService $importService)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $result = $importService->import($request->file('excel_file'));

        return back()->with('import_result', $result);
    }

    // 엑셀 템플릿 다운로드
    public function downloadTemplate()
    {
        $headers = ['사번', '이름', '영문이름', '부서', '직책', '직급', '이메일', '전화번호', '입사일', '비밀번호'];

        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            // BOM for UTF-8
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, $headers);
            fputcsv($file, ['EMP001', '홍길동', 'Hong Gildong', '개발팀', '팀장', '과장', 'hong@company.com', '010-1234-5678', '2024-01-15', 'pass1234']);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="employee_import_template.csv"',
        ]);
    }
}
