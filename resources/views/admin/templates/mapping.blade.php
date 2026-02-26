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
    .canvas-wrapper img.bg-img { display: block; width: 100%; height: auto; pointer-events: none; }

    /* 필드 요소 공통 */
    .field-element {
        position: absolute; cursor: move;
        border: 2px dashed rgba(0,123,255,.4); background: rgba(0,123,255,.06);
        border-radius: 3px; user-select: none; z-index: 10;
        transition: border-color .15s, background .15s;
    }
    .field-element:hover, .field-element.active {
        border-color: #dc3545; background: rgba(220,53,69,.08);
    }
    .field-element .field-tag {
        position: absolute; top: -20px; left: 50%; transform: translateX(-50%);
        background: #0d6efd; color: #fff;
        font-size: 10px; padding: 1px 6px; border-radius: 3px; white-space: nowrap;
        pointer-events: none;
    }
    .field-element.active .field-tag { background: #dc3545; }
    .field-element.type-image, .field-element.type-qr_code {
        background: rgba(25,135,84,.06); border-color: rgba(25,135,84,.4);
        display: flex; align-items: center; justify-content: center;
        color: #999; font-size: 11px;
    }
    .field-element.type-image:hover, .field-element.type-image.active,
    .field-element.type-qr_code:hover, .field-element.type-qr_code.active {
        border-color: #dc3545; background: rgba(220,53,69,.08);
    }

    /* 텍스트 필드 미리보기 */
    .field-element.type-text .text-preview {
        white-space: nowrap; pointer-events: none;
    }

    /* 십자선 가이드 */
    .crosshair-h, .crosshair-v {
        position: absolute; background: rgba(220,53,69,.25);
        pointer-events: none; z-index: 5; display: none;
    }
    .crosshair-h { height: 1px; left: 0; right: 0; }
    .crosshair-v { width: 1px; top: 0; bottom: 0; }
    .canvas-wrapper.dragging .crosshair-h,
    .canvas-wrapper.dragging .crosshair-v { display: block; }

    /* 중앙선 가이드 */
    .center-guide {
        position: absolute; left: 50%; top: 0; bottom: 0;
        width: 1px; background: rgba(99,102,241,.2);
        pointer-events: none; z-index: 4;
    }

    .props-panel { width: 320px; flex-shrink: 0; }
    .props-panel .card { position: sticky; top: 1rem; }
    .coord-display {
        position: absolute; bottom: 8px; right: 8px;
        background: rgba(0,0,0,.7); color: #fff; font-size: 10px;
        padding: 2px 6px; border-radius: 3px; z-index: 50; pointer-events: none;
        font-family: monospace;
    }
</style>
@endpush

@section('content')
<div class="mapping-container">
    {{-- 캔버스 영역 --}}
    <div>
        <div class="canvas-wrapper" id="canvas"
             style="width:{{ min($template->canvas_width, 640) }}px;">
            <img src="{{ asset('storage/' . $template->background_image) }}" alt="배경"
                 class="bg-img" id="bgImage" draggable="false">
            {{-- 중앙선 가이드 --}}
            <div class="center-guide"></div>
            {{-- 십자선 --}}
            <div class="crosshair-h" id="crossH"></div>
            <div class="crosshair-v" id="crossV"></div>
            {{-- 좌표 표시 --}}
            <div class="coord-display" id="coordDisplay" style="display:none;"></div>
        </div>
        <div class="mt-2 d-flex gap-2">
            <button class="btn btn-success" onclick="saveMappings()"><i class="bi bi-save"></i> 매핑 저장</button>
            <button class="btn btn-outline-secondary" onclick="addField()"><i class="bi bi-plus"></i> 필드 추가</button>
            <span class="text-muted small d-flex align-items-center ms-2">
                캔버스: {{ $template->canvas_width }}×{{ $template->canvas_height }}px
                (표시 비율: <span id="scaleInfo"></span>)
            </span>
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

        <div class="card mt-3">
            <div class="card-header fw-bold small">좌표 규칙</div>
            <div class="card-body small text-muted" style="line-height:1.8;">
                <b>텍스트 (center 정렬):</b> pos_x = 텍스트 중앙 X<br>
                <b>텍스트 (left 정렬):</b> pos_x = 텍스트 왼쪽 X<br>
                <b>텍스트 (right 정렬):</b> pos_x = 텍스트 오른쪽 X<br>
                <b>이미지/QR:</b> pos_x, pos_y = 왼쪽 상단 모서리<br>
                <span class="text-primary">빨간 점선</span> = 중앙 가이드선
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const CANVAS_DISPLAY_W = {{ min($template->canvas_width, 640) }};
const CANVAS_REAL_W = {{ $template->canvas_width }};
const CANVAS_REAL_H = {{ $template->canvas_height }};
const SCALE = CANVAS_DISPLAY_W / CANVAS_REAL_W;
const AVAILABLE_FIELDS = @json($availableFields);
const SAVE_URL = '{{ route("admin.templates.save-mappings", $template) }}';
const CSRF = '{{ csrf_token() }}';

// 필드 미리보기 텍스트
const PREVIEW_TEXT = {
    name: '홍길동', name_en: 'Hong Gildong', department: '개발팀',
    position: '팀장', rank: '과장', employee_number: 'EMP001',
    email: 'hong@company.com', phone: '010-1234-5678',
    hire_date: '2024.01.15', birth_date: '1990.05.20', blood_type: 'A'
};

document.getElementById('scaleInfo').textContent = (SCALE * 100).toFixed(0) + '%';

let fields = @json($template->fieldMappings->toArray());
let activeIdx = null;
let dragState = null;

document.addEventListener('DOMContentLoaded', () => {
    renderAll();
    renderFieldList();
});

// ══════════════════════════════════════
// 필드 렌더링 (WYSIWYG)
// ══════════════════════════════════════
function renderAll() {
    document.querySelectorAll('.field-element').forEach(el => el.remove());
    const canvas = document.getElementById('canvas');

    fields.forEach((f, idx) => {
        const el = document.createElement('div');
        el.className = `field-element type-${f.field_type}`;
        el.dataset.idx = idx;

        // 라벨 태그
        el.innerHTML = `<span class="field-tag">${f.label}</span>`;

        if (f.field_type === 'text') {
            // ★ 텍스트 필드: pos_x는 정렬 기준점 (anchor)
            const preview = document.createElement('span');
            preview.className = 'text-preview';
            preview.textContent = PREVIEW_TEXT[f.field_key] || f.field_key;
            preview.style.fontSize = Math.max(10, (f.font_size || 16) * SCALE) + 'px';
            preview.style.color = f.font_color || '#333';
            preview.style.fontWeight = f.is_bold ? '700' : '400';
            el.appendChild(preview);

            // ★ 핵심: 정렬에 따른 CSS 배치
            const sx = f.pos_x * SCALE;
            const sy = f.pos_y * SCALE;

            if (f.text_align === 'center') {
                // pos_x = 텍스트 중앙점 → left + translateX(-50%)
                el.style.left = sx + 'px';
                el.style.top = sy + 'px';
                el.style.transform = 'translateX(-50%)';
                el.style.textAlign = 'center';
            } else if (f.text_align === 'right') {
                // pos_x = 텍스트 오른쪽 끝 → left + translateX(-100%)
                el.style.left = sx + 'px';
                el.style.top = sy + 'px';
                el.style.transform = 'translateX(-100%)';
                el.style.textAlign = 'right';
            } else {
                // left: pos_x = 텍스트 왼쪽 끝
                el.style.left = sx + 'px';
                el.style.top = sy + 'px';
                el.style.textAlign = 'left';
            }
        } else {
            // 이미지/QR: pos_x, pos_y = 왼쪽 상단 모서리
            el.style.left = (f.pos_x * SCALE) + 'px';
            el.style.top = (f.pos_y * SCALE) + 'px';
            if (f.width) el.style.width = (f.width * SCALE) + 'px';
            if (f.height) el.style.height = (f.height * SCALE) + 'px';

            if (f.field_type === 'image') {
                el.innerHTML += '<div style="text-align:center;width:100%;">📷 PHOTO</div>';
            } else {
                el.innerHTML += '<div style="text-align:center;width:100%;">QR CODE</div>';
            }
        }

        // 이벤트
        el.addEventListener('mousedown', (e) => startDrag(e, idx));
        el.addEventListener('click', (e) => { e.stopPropagation(); setActive(idx); });

        canvas.appendChild(el);
    });
}

// ══════════════════════════════════════
// 드래그 (정렬 방식에 맞는 좌표 저장)
// ══════════════════════════════════════
function startDrag(e, idx) {
    e.preventDefault();
    setActive(idx);

    const el = e.currentTarget;
    const canvasRect = document.getElementById('canvas').getBoundingClientRect();
    const f = fields[idx];

    // 마우스 시작 위치와 현재 anchor point 간 오프셋 계산
    const mouseX = e.clientX - canvasRect.left;
    const mouseY = e.clientY - canvasRect.top;
    const anchorX = f.pos_x * SCALE;
    const anchorY = f.pos_y * SCALE;

    dragState = {
        idx,
        offsetX: mouseX - anchorX,
        offsetY: mouseY - anchorY
    };

    document.getElementById('canvas').classList.add('dragging');
    document.addEventListener('mousemove', onDrag);
    document.addEventListener('mouseup', endDrag);
}

function onDrag(e) {
    if (!dragState) return;
    const canvas = document.getElementById('canvas');
    const cRect = canvas.getBoundingClientRect();

    // 새 anchor 좌표 (표시 좌표계)
    let ax = e.clientX - cRect.left - dragState.offsetX;
    let ay = e.clientY - cRect.top - dragState.offsetY;

    // 캔버스 범위 제한
    ax = Math.max(0, Math.min(ax, cRect.width));
    ay = Math.max(0, Math.min(ay, cRect.height));

    // 실제 좌표로 변환
    const realX = Math.round(ax / SCALE);
    const realY = Math.round(ay / SCALE);

    fields[dragState.idx].pos_x = realX;
    fields[dragState.idx].pos_y = realY;

    // 십자선 업데이트
    document.getElementById('crossH').style.top = ay + 'px';
    document.getElementById('crossV').style.left = ax + 'px';

    // 좌표 표시
    const cd = document.getElementById('coordDisplay');
    cd.style.display = 'block';
    cd.textContent = `X: ${realX}  Y: ${realY}`;

    renderAll();
    setActive(dragState.idx);
}

function endDrag() {
    dragState = null;
    document.getElementById('canvas').classList.remove('dragging');
    document.getElementById('coordDisplay').style.display = 'none';
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('mouseup', endDrag);
    renderFieldList();
}

// ══════════════════════════════════════
// 활성 필드 / 속성 패널
// ══════════════════════════════════════
function setActive(idx) {
    activeIdx = idx;
    document.querySelectorAll('.field-element').forEach(el => el.classList.remove('active'));
    const el = document.querySelector(`[data-idx="${idx}"]`);
    if (el) el.classList.add('active');
    updatePropsPanel();
    renderFieldList();
}

function updatePropsPanel() {
    const panel = document.getElementById('propsPanel');
    if (activeIdx === null || !fields[activeIdx]) {
        panel.innerHTML = '<p class="text-muted small">필드를 클릭하여 속성을 편집하세요.</p>';
        return;
    }
    const f = fields[activeIdx];
    const idx = activeIdx;

    panel.innerHTML = `
        <div class="mb-2">
            <label class="form-label small fw-bold">필드 종류</label>
            <select class="form-select form-select-sm" onchange="changeFieldKey(${idx},this.value)">
                ${Object.entries(AVAILABLE_FIELDS).map(([k,v]) =>
                    `<option value="${k}" ${f.field_key===k?'selected':''}>${v}</option>`
                ).join('')}
            </select>
        </div>
        <div class="mb-2">
            <label class="form-label small fw-bold">유형</label>
            <select class="form-select form-select-sm" onchange="updField(${idx},'field_type',this.value)">
                <option value="text" ${f.field_type==='text'?'selected':''}>텍스트</option>
                <option value="image" ${f.field_type==='image'?'selected':''}>이미지</option>
                <option value="qr_code" ${f.field_type==='qr_code'?'selected':''}>QR코드</option>
            </select>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-6">
                <label class="form-label small">X (anchor)</label>
                <input type="number" class="form-control form-control-sm" value="${f.pos_x}"
                    onchange="updField(${idx},'pos_x',+this.value)">
            </div>
            <div class="col-6">
                <label class="form-label small">Y</label>
                <input type="number" class="form-control form-control-sm" value="${f.pos_y}"
                    onchange="updField(${idx},'pos_y',+this.value)">
            </div>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-6">
                <label class="form-label small">너비</label>
                <input type="number" class="form-control form-control-sm" value="${f.width||''}"
                    onchange="updField(${idx},'width',+this.value||null)">
            </div>
            <div class="col-6">
                <label class="form-label small">높이</label>
                <input type="number" class="form-control form-control-sm" value="${f.height||''}"
                    onchange="updField(${idx},'height',+this.value||null)">
            </div>
        </div>
        ${f.field_type === 'text' ? `
        <div class="row g-2 mb-2">
            <div class="col-6">
                <label class="form-label small">폰트 크기</label>
                <input type="number" class="form-control form-control-sm" value="${f.font_size}" min="8" max="80"
                    onchange="updField(${idx},'font_size',+this.value)">
            </div>
            <div class="col-6">
                <label class="form-label small">색상</label>
                <input type="color" class="form-control form-control-sm form-control-color"
                    value="${f.font_color||'#333333'}" onchange="updField(${idx},'font_color',this.value)">
            </div>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-6">
                <label class="form-label small">정렬</label>
                <select class="form-select form-select-sm" onchange="updField(${idx},'text_align',this.value)">
                    <option value="left" ${f.text_align==='left'?'selected':''}>왼쪽</option>
                    <option value="center" ${f.text_align==='center'?'selected':''}>가운데</option>
                    <option value="right" ${f.text_align==='right'?'selected':''}>오른쪽</option>
                </select>
            </div>
            <div class="col-6 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" ${f.is_bold?'checked':''}
                        onchange="updField(${idx},'is_bold',this.checked)">
                    <label class="form-check-label small">굵게</label>
                </div>
            </div>
        </div>` : ''}
        <div class="form-check mb-2">
            <input type="checkbox" class="form-check-input" ${f.is_visible!==false?'checked':''}
                onchange="updField(${idx},'is_visible',this.checked)">
            <label class="form-check-label small">표시</label>
        </div>
        <button class="btn btn-sm btn-outline-danger" onclick="removeField(${idx})">
            <i class="bi bi-trash"></i> 필드 삭제
        </button>
    `;
}

function updField(idx, key, val) {
    fields[idx][key] = val;
    renderAll();
    setActive(idx);
}

function changeFieldKey(idx, key) {
    fields[idx].field_key = key;
    fields[idx].label = AVAILABLE_FIELDS[key] || key;
    if (key === 'photo') fields[idx].field_type = 'image';
    else if (key === 'qr_code') fields[idx].field_type = 'qr_code';
    else fields[idx].field_type = 'text';
    renderAll();
    setActive(idx);
}

function addField() {
    fields.push({
        field_key: 'name', label: '이름', field_type: 'text',
        pos_x: Math.round(CANVAS_REAL_W / 2), pos_y: 200,
        width: null, height: null,
        font_size: 16, font_color: '#333333', font_family: 'NanumGothic',
        text_align: 'center', is_bold: false, is_visible: true
    });
    renderAll();
    renderFieldList();
    setActive(fields.length - 1);
}

function removeField(idx) {
    if (!confirm('이 필드를 삭제하시겠습니까?')) return;
    fields.splice(idx, 1);
    activeIdx = null;
    renderAll();
    renderFieldList();
    updatePropsPanel();
}

function renderFieldList() {
    const list = document.getElementById('fieldList');
    list.innerHTML = fields.map((f, idx) => `
        <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center small
            ${idx===activeIdx?'active':''}" onclick="setActive(${idx})" style="cursor:pointer;">
            <span>${f.label} <span class="text-muted">(${f.field_type})</span></span>
            <span class="badge bg-secondary font-monospace">${f.pos_x}, ${f.pos_y}</span>
        </li>
    `).join('');
}

// ══════════════════════════════════════
// 저장
// ══════════════════════════════════════
function saveMappings() {
    const data = fields.map((f, idx) => ({
        field_key: f.field_key, label: f.label, field_type: f.field_type,
        pos_x: f.pos_x, pos_y: f.pos_y, width: f.width, height: f.height,
        font_size: f.font_size || 16, font_color: f.font_color || '#333333',
        text_align: f.text_align || 'center', is_bold: f.is_bold || false,
        is_visible: f.is_visible !== false, sort_order: idx + 1,
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

// 캔버스 클릭 시 선택 해제
document.getElementById('canvas').addEventListener('click', () => {
    activeIdx = null;
    document.querySelectorAll('.field-element').forEach(el => el.classList.remove('active'));
    updatePropsPanel();
    renderFieldList();
});
</script>
@endpush
@endsection
