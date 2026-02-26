@extends('layouts.admin')
@section('title', '디자인 관리')
@section('page-title', '디자인 템플릿 관리')

@section('content')
<div class="mb-3">
    <a href="{{ route('admin.templates.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> 새 템플릿</a>
</div>

<div class="row g-3">
@forelse($templates as $tpl)
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <img src="{{ asset('storage/' . $tpl->background_image) }}" alt="{{ $tpl->name }}"
                     class="img-fluid mb-3" style="max-height:200px;border:1px solid #eee;border-radius:.5rem;">
                <h6 class="fw-bold">{{ $tpl->name }}</h6>
                <div class="small text-muted">
                    {{ $tpl->canvas_width }} × {{ $tpl->canvas_height }}px |
                    필드: {{ $tpl->field_mappings_count }}개 |
                    직원: {{ $tpl->employees_count }}명
                </div>
                @if($tpl->is_default)
                    <span class="badge bg-primary mt-1">기본 템플릿</span>
                @endif
                @if(!$tpl->is_active)
                    <span class="badge bg-secondary mt-1">비활성</span>
                @endif
            </div>
            <div class="card-footer bg-white d-flex gap-1 justify-content-center">
                <a href="{{ route('admin.templates.mapping', $tpl) }}" class="btn btn-sm btn-info"><i class="bi bi-grid-3x3"></i> 매핑</a>
                <a href="{{ route('admin.templates.edit', $tpl) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                <form action="{{ route('admin.templates.destroy', $tpl) }}" method="POST" onsubmit="return confirm('삭제하시겠습니까?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        </div>
    </div>
@empty
    <div class="col-12 text-center py-5 text-muted">등록된 템플릿이 없습니다.</div>
@endforelse
</div>
@endsection
