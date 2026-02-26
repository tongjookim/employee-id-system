<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DesignTemplate;
use App\Models\FieldMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DesignTemplateController extends Controller
{
    public function index()
    {
        $templates = DesignTemplate::withCount(['employees', 'fieldMappings'])->get();
        return view('admin.templates.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.templates.form', ['template' => null]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:100',
            'background_image' => 'required|image|max:10240',
            'canvas_width'     => 'required|integer|min:200|max:2000',
            'canvas_height'    => 'required|integer|min:200|max:3000',
            'is_default'       => 'boolean',
            'description'      => 'nullable|string',
        ]);

        $validated['background_image'] = $request->file('background_image')
            ->store('templates', 'public');
        $validated['is_active'] = true;

        if (!empty($validated['is_default'])) {
            DesignTemplate::where('is_default', true)->update(['is_default' => false]);
        }

        $template = DesignTemplate::create($validated);

        // 기본 필드 매핑 자동 생성
        $this->createDefaultMappings($template);

        return redirect()->route('admin.templates.mapping', $template)
            ->with('success', '템플릿이 생성되었습니다. 필드 위치를 설정해주세요.');
    }

    public function edit(DesignTemplate $template)
    {
        return view('admin.templates.form', compact('template'));
    }

    public function update(Request $request, DesignTemplate $template)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:100',
            'background_image' => 'nullable|image|max:10240',
            'canvas_width'     => 'required|integer|min:200|max:2000',
            'canvas_height'    => 'required|integer|min:200|max:3000',
            'is_default'       => 'boolean',
            'is_active'        => 'boolean',
            'description'      => 'nullable|string',
        ]);

        if ($request->hasFile('background_image')) {
            Storage::disk('public')->delete($template->background_image);
            $validated['background_image'] = $request->file('background_image')
                ->store('templates', 'public');
        }

        if (!empty($validated['is_default'])) {
            DesignTemplate::where('is_default', true)->where('id', '!=', $template->id)
                ->update(['is_default' => false]);
        }

        $template->update($validated);

        return redirect()->route('admin.templates.index')
            ->with('success', '템플릿이 수정되었습니다.');
    }

    public function destroy(DesignTemplate $template)
    {
        if ($template->employees()->count() > 0) {
            return back()->with('error', '이 템플릿을 사용 중인 직원이 있어 삭제할 수 없습니다.');
        }

        Storage::disk('public')->delete($template->background_image);
        $template->delete();

        return redirect()->route('admin.templates.index')
            ->with('success', '템플릿이 삭제되었습니다.');
    }

    // 매핑 툴 페이지
    public function mapping(DesignTemplate $template)
    {
        $template->load('fieldMappings');
        $availableFields = $this->getAvailableFieldKeys();

        return view('admin.templates.mapping', compact('template', 'availableFields'));
    }

    // 매핑 데이터 저장 (AJAX)
    public function saveMappings(Request $request, DesignTemplate $template)
    {
        $request->validate([
            'mappings'                => 'required|array',
            'mappings.*.field_key'    => 'required|string|max:50',
            'mappings.*.label'        => 'required|string|max:100',
            'mappings.*.field_type'   => 'required|in:text,image,qr_code',
            'mappings.*.pos_x'        => 'required|integer',
            'mappings.*.pos_y'        => 'required|integer',
            'mappings.*.width'        => 'nullable|integer',
            'mappings.*.height'       => 'nullable|integer',
            'mappings.*.font_size'    => 'nullable|integer|min:8|max:100',
            'mappings.*.font_color'   => 'nullable|string|max:7',
            'mappings.*.text_align'   => 'nullable|in:left,center,right',
            'mappings.*.is_bold'      => 'nullable|boolean',
            'mappings.*.is_visible'   => 'nullable|boolean',
        ]);

        // 기존 매핑 삭제 후 재생성
        $template->fieldMappings()->delete();

        foreach ($request->mappings as $idx => $data) {
            $template->fieldMappings()->create(array_merge($data, [
                'sort_order' => $idx,
            ]));
        }

        return response()->json(['success' => true, 'message' => '매핑이 저장되었습니다.']);
    }

    private function createDefaultMappings(DesignTemplate $template): void
    {
        $defaults = [
            ['field_key'=>'photo','label'=>'증명사진','field_type'=>'image','pos_x'=>220,'pos_y'=>180,'width'=>200,'height'=>260],
            ['field_key'=>'name','label'=>'이름','field_type'=>'text','pos_x'=>320,'pos_y'=>480,'font_size'=>28],
            ['field_key'=>'department','label'=>'부서','field_type'=>'text','pos_x'=>320,'pos_y'=>530,'font_size'=>18],
            ['field_key'=>'position','label'=>'직책','field_type'=>'text','pos_x'=>320,'pos_y'=>565,'font_size'=>18],
            ['field_key'=>'employee_number','label'=>'사번','field_type'=>'text','pos_x'=>320,'pos_y'=>600,'font_size'=>14],
            ['field_key'=>'qr_code','label'=>'QR코드','field_type'=>'qr_code','pos_x'=>230,'pos_y'=>660,'width'=>180,'height'=>180],
        ];

        foreach ($defaults as $i => $field) {
            $template->fieldMappings()->create(array_merge([
                'font_size'  => 16,
                'font_color' => '#333333',
                'text_align' => 'center',
                'is_visible' => true,
                'sort_order' => $i,
            ], $field));
        }
    }

    private function getAvailableFieldKeys(): array
    {
        return [
            'name'            => '이름',
            'name_en'         => '영문이름',
            'department'      => '부서',
            'position'        => '직책',
            'rank'            => '직급',
            'employee_number' => '사번',
            'email'           => '이메일',
            'phone'           => '전화번호',
            'hire_date'       => '입사일',
            'birth_date'      => '생년월일',
            'blood_type'      => '혈액형',
            'photo'           => '증명사진',
            'qr_code'         => 'QR코드',
        ];
    }
}
