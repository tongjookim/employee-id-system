@extends('layouts.admin')
@section('title', '필드 매핑')
@section('page-title', '디자인 매핑 - ' . $template->name)

@push('styles')
<style>
    .mapping-container { display: flex; gap: 1.5rem; }
    .canvas-wrapper {
        position: relative; border: 2px solid #dee2e6; border-radius: .5rem;
        overflow: hidden; background: #f8f8f8; flex-shrink: 0;
    }
    .canvas-wrapper img { display: block; width: 100%; height: auto; }
    .field-element {
        position: absolute; cursor: move; padding: 2px 6px;
        border: 2px dashed rgba(0,123,255,.5); background: rgba(0,123,255,.08);
        border-radius: 3px; user-select: none; font-size: 12px; z-index: 10;
        transition: border-color .15s;
    }
    .field-element:hover, .field-element.active { border-color: #dc3545; background: rgba(220,53,69,.1); }
    .field-element .field-label {
        position: absolute; top: -20px; left: 0; background: #0d6efd; color: #fff;
        font-size: 10px; padding: 1px 5px; border-radius: 3px; white-space: nowrap;
    }
    .field-element.type-image, .field-element.type-qr_code {
        background: rgba(25,135,84,.08); border-color: rgba(25,135,84,.5);
    }
    .props-panel { width: 320px; flex-shrink: 0; }
    .props-panel .card { position: sticky; top: 1rem; }
</style>
@endpush

@section('content')
<div class="mapping-container">
    {{-- 캔버스 영역 --}}
    <div>
        <div class="canvas-wrapper" id="canvas"
             style="width:{{ min($template->canvas_width, 640) }}px;">
            <img src="{{ asset('storage/' . $template->background_image) }}" alt="배경"
                 id="bgImage" draggable="false">
            {{-- 필드 요소가 JS로 렌더링됨 --}}
        </div>
        <div class="mt-2 d-flex gap-2">
            <button class="btn btn-success" onclick="saveMappings()"><i class="bi bi-save"></i> 매핑 저장</button>
            <button class="btn btn-outline-secondary" onclick="addField()"><i class="bi bi-plus"></i> 필드 추가</button>
        </div>
    </div>

    {{-- 속성 패널 --}}
    <div class="props-panel">
        <div class="card">
            <div class="card-header fw-bold">필드 속성</div>
            <div class="card-body" id="propsPanel">
                <p class="text-muted small">필드를 클릭하여 속성을 편집하세요.</p>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header fw-bold">필드 목록</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush" id="fieldList"></ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const CANVAS_DISPLAY_WIDTH = {{ min($template->canvas_width, 640) }};
const CANVAS_REAL_WIDTH = {{ $template->canvas_width }};
const CANVAS_REAL_HEIGHT = {{ $template->canvas_height }};
const SCALE = CANVAS_DISPLAY_WIDTH / CANVAS_REAL_WIDTH;
const AVAILABLE_FIELDS = @json($availableFields);
const SAVE_URL = '{{ route("admin.templates.save-mappings", $template) }}';
const CSRF = '{{ csrf_token() }}';

let fields = @json($template->fieldMappings->toArray());
let activeFieldIdx = null;
let dragState = null;

// ── 초기화 ──
document.addEventListener('DOMContentLoaded', () => {
    renderAllFields();
    renderFieldList();
});

function renderAllFields() {
    document.querySelectorAll('.field-element').forEach(el => el.remove());
    const canvas = document.getElementById('canvas');

    fields.forEach((f, idx) => {
        const el = document.createElement('div');
        el.className = `field-element type-${f.field_type}`;
        el.dataset.idx = idx;
        el.innerHTML = `<span class="field-label">${f.label}</span>${f.field_type === 'text' ? (f.field_key || '') : (f.field_type === 'qr_code' ? 'QR' : '📷')}`;

        // 위치 (스케일 적용)
        el.style.left = (f.pos_x * SCALE) + 'px';
        el.style.top = (f.pos_y * SCALE) + 'px';

        if (f.width) el.style.width = (f.width * SCALE) + 'px';
        if (f.height) el.style.height = (f.height * SCALE) + 'px';
        if (f.field_type === 'text') {
            el.style.fontSize = Math.max(10, f.font_size * SCALE) + 'px';
            el.style.color = f.font_color || '#333';
            if (f.is_bold) el.style.fontWeight = 'bold';
        }

        // 드래그
        el.addEventListener('mousedown', (e) => startDrag(e, idx));
        el.addEventListener('click', (e) => { e.stopPropagation(); setActive(idx); });

        canvas.appendChild(el);
    });
}

function startDrag(e, idx) {
    e.preventDefault();
    setActive(idx);
    const el = e.currentTarget;
    const rect = el.getBoundingClientRect();
    dragState = { idx, offsetX: e.clientX - rect.left, offsetY: e.clientY - rect.top };

    document.addEventListener('mousemove', onDrag);
    document.addEventListener('mouseup', endDrag);
}

function onDrag(e) {
    if (!dragState) return;
    const canvas = document.getElementById('canvas');
    const cRect = canvas.getBoundingClientRect();
    const el = canvas.querySelector(`[data-idx="${dragState.idx}"]`);

    let x = e.clientX - cRect.left - dragState.offsetX;
    let y = e.clientY - cRect.top - dragState.offsetY;
    x = Math.max(0, Math.min(x, cRect.width - el.offsetWidth));
    y = Math.max(0, Math.min(y, cRect.height - el.offsetHeight));

    el.style.left = x + 'px';
    el.style.top = y + 'px';

    // 실제 좌표로 변환하여 저장
    fields[dragState.idx].pos_x = Math.round(x / SCALE);
    fields[dragState.idx].pos_y = Math.round(y / SCALE);

    updatePropsPanel();
}

function endDrag() {
    dragState = null;
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('mouseup', endDrag);
}

function setActive(idx) {
    activeFieldIdx = idx;
    document.querySelectorAll('.field-element').forEach(el => el.classList.remove('active'));
    const el = document.querySelector(`[data-idx="${idx}"]`);
    if (el) el.classList.add('active');
    updatePropsPanel();
    renderFieldList();
}

function updatePropsPanel() {
    const panel = document.getElementById('propsPanel');
    if (activeFieldIdx === null || !fields[activeFieldIdx]) {
        panel.innerHTML = '<p class="text-muted small">필드를 클릭하여 속성을 편집하세요.</p>';
        return;
    }

    const f = fields[activeFieldIdx];
    const idx = activeFieldIdx;

    panel.innerHTML = `
        <div class="mb-2">
            <label class="form-label small fw-bold">필드 종류</label>
            <select class="form-select form-select-sm" onchange="updateField(${idx},'field_key',this.value); updateFieldLabel(${idx},this)">
                ${Object.entries(AVAILABLE_FIELDS).map(([k,v]) =>
                    `<option value="${k}" ${f.field_key===k?'selected':''}>${v}</option>`
                ).join('')}
            </select>
        </div>
        <div class="mb-2">
            <label class="form-label small fw-bold">유형</label>
            <select class="form-select form-select-sm" onchange="updateField(${idx},'field_type',this.value)">
                <option value="text" ${f.field_type==='text'?'selected':''}>텍스트</option>
                <option value="image" ${f.field_type==='image'?'selected':''}>이미지</option>
                <option value="qr_code" ${f.field_type==='qr_code'?'selected':''}>QR코드</option>
            </select>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-6">
                <label class="form-label small">X</label>
                <input type="number" class="form-control form-control-sm" value="${f.pos_x}" onchange="updateField(${idx},'pos_x',+this.value)">
            </div>
            <div class="col-6">
                <label class="form-label small">Y</label>
                <input type="number" class="form-control form-control-sm" value="${f.pos_y}" onchange="updateField(${idx},'pos_y',+this.value)">
            </div>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-6">
                <label class="form-label small">너비</label>
                <input type="number" class="form-control form-control-sm" value="${f.width||''}" onchange="updateField(${idx},'width',+this.value||null)">
            </div>
            <div class="col-6">
                <label class="form-label small">높이</label>
                <input type="number" class="form-control form-control-sm" value="${f.height||''}" onchange="updateField(${idx},'height',+this.value||null)">
            </div>
        </div>
        ${f.field_type === 'text' ? `
        <div class="row g-2 mb-2">
            <div class="col-6">
                <label class="form-label small">폰트 크기</label>
                <input type="number" class="form-control form-control-sm" value="${f.font_size}" min="8" max="80" onchange="updateField(${idx},'font_size',+this.value)">
            </div>
            <div class="col-6">
                <label class="form-label small">색상</label>
                <input type="color" class="form-control form-control-sm form-control-color" value="${f.font_color||'#333333'}" onchange="updateField(${idx},'font_color',this.value)">
            </div>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-6">
                <label class="form-label small">정렬</label>
                <select class="form-select form-select-sm" onchange="updateField(${idx},'text_align',this.value)">
                    <option value="left" ${f.text_align==='left'?'selected':''}>왼쪽</option>
                    <option value="center" ${f.text_align==='center'?'selected':''}>가운데</option>
                    <option value="right" ${f.text_align==='right'?'selected':''}>오른쪽</option>
                </select>
            </div>
            <div class="col-6 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" ${f.is_bold?'checked':''} onchange="updateField(${idx},'is_bold',this.checked)">
                    <label class="form-check-label small">굵게</label>
                </div>
            </div>
        </div>` : ''}
        <div class="form-check mb-2">
            <input type="checkbox" class="form-check-input" ${f.is_visible!==false?'checked':''} onchange="updateField(${idx},'is_visible',this.checked)">
            <label class="form-check-label small">표시</label>
        </div>
        <button class="btn btn-sm btn-outline-danger" onclick="removeField(${idx})"><i class="bi bi-trash"></i> 필드 삭제</button>
    `;
}

function updateField(idx, key, val) {
    fields[idx][key] = val;
    renderAllFields();
    setActive(idx);
}

function updateFieldLabel(idx, select) {
    const key = select.value;
    fields[idx].label = AVAILABLE_FIELDS[key] || key;
    // 이미지/QR 자동 유형 설정
    if (key === 'photo') fields[idx].field_type = 'image';
    else if (key === 'qr_code') fields[idx].field_type = 'qr_code';
    else fields[idx].field_type = 'text';
    renderAllFields();
    setActive(idx);
}

function addField() {
    fields.push({
        field_key: 'name', label: '이름', field_type: 'text',
        pos_x: 100, pos_y: 100, width: null, height: null,
        font_size: 16, font_color: '#333333', font_family: 'NanumGothic',
        text_align: 'center', is_bold: false, is_visible: true
    });
    renderAllFields();
    renderFieldList();
    setActive(fields.length - 1);
}

function removeField(idx) {
    if (!confirm('이 필드를 삭제하시겠습니까?')) return;
    fields.splice(idx, 1);
    activeFieldIdx = null;
    renderAllFields();
    renderFieldList();
    updatePropsPanel();
}

function renderFieldList() {
    const list = document.getElementById('fieldList');
    list.innerHTML = fields.map((f, idx) => `
        <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center small ${idx===activeFieldIdx?'active':''}"
            onclick="setActive(${idx})" style="cursor:pointer;">
            <span>${f.label} (${f.field_key})</span>
            <span class="badge bg-secondary">${f.pos_x}, ${f.pos_y}</span>
        </li>
    `).join('');
}

function saveMappings() {
    const data = fields.map((f, idx) => ({
        field_key: f.field_key,
        label: f.label,
        field_type: f.field_type,
        pos_x: f.pos_x, pos_y: f.pos_y,
        width: f.width, height: f.height,
        font_size: f.font_size || 16,
        font_color: f.font_color || '#333333',
        text_align: f.text_align || 'center',
        is_bold: f.is_bold || false,
        is_visible: f.is_visible !== false,
    }));

    fetch(SAVE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ mappings: data })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) alert('매핑이 저장되었습니다!');
        else alert('저장 실패: ' + (res.message || ''));
    })
    .catch(err => alert('저장 중 오류: ' + err.message));
}
</script>
@endpush
@endsection
