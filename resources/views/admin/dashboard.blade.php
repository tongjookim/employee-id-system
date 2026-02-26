@extends('layouts.admin')
@section('title', '대시보드')
@section('page-title', '대시보드')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="number text-primary">{{ $stats['total_employees'] }}</div>
            <div class="label">전체 직원</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="number text-success">{{ $stats['active_employees'] }}</div>
            <div class="label">활성 직원</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="number text-info">{{ $stats['templates'] }}</div>
            <div class="label">디자인 템플릿</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="number text-warning">{{ $stats['qr_scans_today'] }}</div>
            <div class="label">오늘 QR 스캔</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-white fw-bold">최근 등록 직원</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>사번</th><th>이름</th><th>부서</th><th>상태</th><th>등록일</th></tr>
                    </thead>
                    <tbody>
                    @forelse($recentEmployees as $emp)
                        <tr>
                            <td>{{ $emp->employee_number }}</td>
                            <td>{{ $emp->name }}</td>
                            <td>{{ $emp->department }}</td>
                            <td>
                                <span class="badge bg-{{ $emp->status === 'active' ? 'success' : ($emp->status === 'inactive' ? 'secondary' : 'danger') }}">
                                    {{ $emp->status === 'active' ? '활성' : ($emp->status === 'inactive' ? '비활성' : '정지') }}
                                </span>
                            </td>
                            <td>{{ $emp->created_at->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-3 text-muted">등록된 직원이 없습니다.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-white fw-bold">최근 QR 스캔 로그</div>
            <div class="card-body p-0" style="max-height:400px;overflow-y:auto;">
                <table class="table table-sm mb-0">
                    <thead><tr><th>직원</th><th>유형</th><th>시각</th></tr></thead>
                    <tbody>
                    @forelse($recentLogs as $log)
                        <tr>
                            <td>{{ $log->employee?->name ?? '-' }}</td>
                            <td><span class="badge bg-info">{{ $log->access_type }}</span></td>
                            <td class="small">{{ $log->created_at->format('H:i:s') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center py-3 text-muted">로그가 없습니다.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
