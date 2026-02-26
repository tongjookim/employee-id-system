@extends('layouts.admin')
@section('title', '직원 관리')
@section('page-title', '직원 관리')

@section('content')
{{-- 검색 & 필터 --}}
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="이름, 사번, 부서 검색" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="department" class="form-select">
                    <option value="">전체 부서</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept }}" {{ request('department') === $dept ? 'selected' : '' }}>{{ $dept }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">전체 상태</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>활성</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>비활성</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>정지</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> 검색</button>
            </div>
        </form>
    </div>
</div>

{{-- 액션 버튼 --}}
<div class="d-flex justify-content-between mb-3">
    <div>
        <a href="{{ route('admin.employees.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> 직원 등록</a>
        <button class="btn btn-outline-success ms-2" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-file-earmark-excel"></i> 엑셀 일괄등록
        </button>
    </div>
    <a href="{{ route('admin.employees.download-template') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-download"></i> 엑셀 양식 다운로드
    </a>
</div>

{{-- 엑셀 가져오기 결과 --}}
@if(session('import_result'))
    @php $result = session('import_result'); @endphp
    <div class="alert alert-info">
        <strong>가져오기 결과:</strong> 성공 {{ $result['success_count'] }}건, 실패 {{ $result['error_count'] }}건
        @if(!empty($result['errors']))
            <ul class="mt-2 mb-0">
                @foreach(array_slice($result['errors'], 0, 10) as $err)
                    <li class="small">{{ $err }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif

{{-- 직원 목록 --}}
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width:60px">사진</th>
                    <th>사번</th><th>이름</th><th>부서</th><th>직책</th>
                    <th>상태</th><th style="width:180px">관리</th>
                </tr>
            </thead>
            <tbody>
            @forelse($employees as $emp)
                <tr>
                    <td>
                        @if($emp->photo)
                            <img src="{{ asset('storage/' . $emp->photo) }}" alt="" class="rounded" style="width:40px;height:50px;object-fit:cover;">
                        @else
                            <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:40px;height:50px;">
                                <i class="bi bi-person text-muted"></i>
                            </div>
                        @endif
                    </td>
                    <td>{{ $emp->employee_number }}</td>
                    <td class="fw-semibold">{{ $emp->name }}</td>
                    <td>{{ $emp->department }}</td>
                    <td>{{ $emp->position }}</td>
                    <td>
                        <span class="badge bg-{{ $emp->status === 'active' ? 'success' : ($emp->status === 'inactive' ? 'secondary' : 'danger') }}">
                            {{ $emp->status === 'active' ? '활성' : ($emp->status === 'inactive' ? '비활성' : '정지') }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('admin.employees.edit', $emp) }}" class="btn btn-sm btn-outline-primary" title="수정">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="{{ route('admin.employees.preview', $emp) }}" class="btn btn-sm btn-outline-info" title="미리보기" target="_blank">
                            <i class="bi bi-eye"></i>
                        </a>
                        <form action="{{ route('admin.employees.regenerate-qr', $emp) }}" method="POST" class="d-inline" onsubmit="return confirm('QR 코드를 재생성하시겠습니까?')">
                            @csrf
                            <button class="btn btn-sm btn-outline-warning" title="QR 재생성"><i class="bi bi-qr-code"></i></button>
                        </form>
                        <form action="{{ route('admin.employees.destroy', $emp) }}" method="POST" class="d-inline" onsubmit="return confirm('정말 삭제하시겠습니까?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="삭제"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center py-4 text-muted">등록된 직원이 없습니다.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($employees->hasPages())
        <div class="card-footer bg-white">{{ $employees->links() }}</div>
    @endif
</div>

{{-- 엑셀 가져오기 모달 --}}
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.employees.import-excel') }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">엑셀 일괄 등록</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">엑셀 파일 컬럼 순서: 사번, 이름, 영문이름, 부서, 직책, 직급, 이메일, 전화번호, 입사일, 비밀번호</p>
                <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls,.csv" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-upload"></i> 업로드</button>
            </div>
        </form>
    </div>
</div>
@endsection
