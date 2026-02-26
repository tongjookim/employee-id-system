@extends('layouts.admin')
@section('title', $template ? '템플릿 수정' : '새 템플릿')
@section('page-title', $template ? '템플릿 수정' : '새 템플릿 등록')

@section('content')
<div class="card" style="max-width:700px;">
    <div class="card-body">
        <form method="POST"
              action="{{ $template ? route('admin.templates.update', $template) : route('admin.templates.store') }}"
              enctype="multipart/form-data">
            @csrf
            @if($template) @method('PUT') @endif

            <div class="mb-3">
                <label class="form-label">템플릿 이름 <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control"
                       value="{{ old('name', $template?->name) }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label">배경 이미지 {{ !$template ? '*' : '' }}</label>
                <input type="file" name="background_image" class="form-control" accept="image/*" {{ !$template ? 'required' : '' }}>
                @if($template?->background_image)
                    <div class="mt-2">
                        <img src="{{ asset('storage/' . $template->background_image) }}" class="img-fluid" style="max-height:200px;">
                    </div>
                @endif
            </div>

            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label">캔버스 너비 (px)</label>
                    <input type="number" name="canvas_width" class="form-control"
                           value="{{ old('canvas_width', $template?->canvas_width ?? 640) }}" min="200" max="2000" required>
                </div>
                <div class="col-6">
                    <label class="form-label">캔버스 높이 (px)</label>
                    <input type="number" name="canvas_height" class="form-control"
                           value="{{ old('canvas_height', $template?->canvas_height ?? 1010) }}" min="200" max="3000" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">설명</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $template?->description) }}</textarea>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" name="is_default" value="1" class="form-check-input"
                       {{ old('is_default', $template?->is_default) ? 'checked' : '' }}>
                <label class="form-check-label">기본 템플릿으로 설정</label>
            </div>

            @if($template)
            <div class="mb-3 form-check">
                <input type="checkbox" name="is_active" value="1" class="form-check-input"
                       {{ old('is_active', $template?->is_active) ? 'checked' : '' }}>
                <label class="form-check-label">활성 상태</label>
            </div>
            @endif

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> {{ $template ? '수정' : '등록' }}
                </button>
                <a href="{{ route('admin.templates.index') }}" class="btn btn-secondary">취소</a>
            </div>
        </form>
    </div>
</div>
@endsection
