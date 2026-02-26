@extends('layouts.admin')
@section('title', $employee ? '직원 수정' : '직원 등록')
@section('page-title', $employee ? '직원 정보 수정' : '새 직원 등록')

@section('content')
<div class="card">
    <div class="card-body">
        <form method="POST"
              action="{{ $employee ? route('admin.employees.update', $employee) : route('admin.employees.store') }}"
              enctype="multipart/form-data">
            @csrf
            @if($employee) @method('PUT') @endif

            <div class="row g-3">
                {{-- 기본 정보 --}}
                <div class="col-12"><h6 class="text-primary fw-bold border-bottom pb-2">기본 정보</h6></div>

                <div class="col-md-4">
                    <label class="form-label">사번 <span class="text-danger">*</span></label>
                    <input type="text" name="employee_number" class="form-control"
                           value="{{ old('employee_number', $employee?->employee_number) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">이름 <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control"
                           value="{{ old('name', $employee?->name) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">영문 이름</label>
                    <input type="text" name="name_en" class="form-control"
                           value="{{ old('name_en', $employee?->name_en) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">{{ $employee ? '비밀번호 (변경 시만 입력)' : '비밀번호' }} {{ !$employee ? '*' : '' }}</label>
                    <input type="password" name="password" class="form-control" {{ !$employee ? 'required' : '' }}>
                </div>
                <div class="col-md-4">
                    <label class="form-label">상태 <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="active" {{ old('status', $employee?->status) === 'active' ? 'selected' : '' }}>활성</option>
                        <option value="inactive" {{ old('status', $employee?->status) === 'inactive' ? 'selected' : '' }}>비활성</option>
                        <option value="suspended" {{ old('status', $employee?->status) === 'suspended' ? 'selected' : '' }}>정지</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">디자인 템플릿</label>
                    <select name="design_template_id" class="form-select">
                        <option value="">기본 템플릿 사용</option>
                        @foreach($templates as $tpl)
                            <option value="{{ $tpl->id }}" {{ old('design_template_id', $employee?->design_template_id) == $tpl->id ? 'selected' : '' }}>
                                {{ $tpl->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- 소속 정보 --}}
                <div class="col-12 mt-4"><h6 class="text-primary fw-bold border-bottom pb-2">소속 정보</h6></div>

                <div class="col-md-4">
                    <label class="form-label">부서</label>
                    <input type="text" name="department" class="form-control"
                           value="{{ old('department', $employee?->department) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">직책</label>
                    <input type="text" name="position" class="form-control"
                           value="{{ old('position', $employee?->position) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">직급</label>
                    <input type="text" name="rank" class="form-control"
                           value="{{ old('rank', $employee?->rank) }}">
                </div>

                {{-- 연락처 & 개인정보 --}}
                <div class="col-12 mt-4"><h6 class="text-primary fw-bold border-bottom pb-2">연락처 & 개인정보</h6></div>

                <div class="col-md-4">
                    <label class="form-label">이메일</label>
                    <input type="email" name="email" class="form-control"
                           value="{{ old('email', $employee?->email) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">전화번호</label>
                    <input type="text" name="phone" class="form-control"
                           value="{{ old('phone', $employee?->phone) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">입사일</label>
                    <input type="date" name="hire_date" class="form-control"
                           value="{{ old('hire_date', $employee?->hire_date?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">생년월일</label>
                    <input type="date" name="birth_date" class="form-control"
                           value="{{ old('birth_date', $employee?->birth_date?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">혈액형</label>
                    <select name="blood_type" class="form-select">
                        <option value="">선택</option>
                        @foreach(['A','B','O','AB'] as $bt)
                            <option value="{{ $bt }}" {{ old('blood_type', $employee?->blood_type) === $bt ? 'selected' : '' }}>{{ $bt }}형</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">증명사진</label>
                    <input type="file" name="photo" class="form-control" accept="image/*">
                    @if($employee?->photo)
                        <div class="mt-2">
                            <img src="{{ asset('storage/' . $employee->photo) }}" alt="" class="rounded" style="height:80px;">
                        </div>
                    @endif
                </div>
                <div class="col-12">
                    <label class="form-label">주소</label>
                    <input type="text" name="address" class="form-control"
                           value="{{ old('address', $employee?->address) }}">
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> {{ $employee ? '수정' : '등록' }}
                </button>
                <a href="{{ route('admin.employees.index') }}" class="btn btn-secondary">취소</a>
            </div>
        </form>
    </div>
</div>
@endsection
