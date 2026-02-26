<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>신원 확인</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:-apple-system,'Pretendard',sans-serif; background:#f0fdf4; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:1rem; }
        .verify-card {
            max-width:380px; width:100%; background:#fff; border-radius:1.2rem;
            box-shadow:0 10px 40px rgba(0,0,0,.1); text-align:center; padding:2.5rem 2rem;
        }
        .badge-icon { font-size:3.5rem; margin-bottom:1rem; }
        .status { font-size:.85rem; font-weight:600; color:#16a34a; background:#dcfce7; padding:.4rem 1rem; border-radius:2rem; display:inline-block; margin-bottom:1.5rem; }
        .photo { width:100px; height:130px; border-radius:.5rem; object-fit:cover; margin:0 auto 1rem; display:block; border:3px solid #e2e8f0; }
        .name { font-size:1.4rem; font-weight:700; color:#1e293b; }
        .info-row { display:flex; justify-content:space-between; padding:.6rem 0; border-bottom:1px solid #f1f5f9; font-size:.9rem; }
        .info-row .label { color:#64748b; }
        .info-row .value { color:#1e293b; font-weight:500; }
        .timestamp { margin-top:1.5rem; font-size:.75rem; color:#94a3b8; }
    </style>
</head>
<body>
    <div class="verify-card">
        <div class="badge-icon">✅</div>
        <div class="status">신원 확인됨</div>

        @if($employee->photo)
            <img src="{{ asset('storage/' . $employee->photo) }}" alt="사진" class="photo">
        @endif

        <div class="name">{{ $employee->name }}</div>

        <div style="margin-top:1.5rem; text-align:left;">
            <div class="info-row"><span class="label">사번</span><span class="value">{{ $employee->employee_number }}</span></div>
            <div class="info-row"><span class="label">부서</span><span class="value">{{ $employee->department ?? '-' }}</span></div>
            <div class="info-row"><span class="label">직책</span><span class="value">{{ $employee->position ?? '-' }}</span></div>
            @if($employee->email)
                <div class="info-row"><span class="label">이메일</span><span class="value">{{ $employee->email }}</span></div>
            @endif
        </div>

        <div class="timestamp">확인 시각: {{ now()->format('Y-m-d H:i:s') }}</div>
    </div>
</body>
</html>
