<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 로그인</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); min-height: 100vh; display: flex; align-items: center; }
        .login-card { max-width: 400px; width: 100%; margin: auto; border-radius: .75rem; box-shadow: 0 20px 60px rgba(0,0,0,.3); }
    </style>
</head>
<body>
    <div class="login-card card p-4">
        <div class="text-center mb-4">
            <h4 class="fw-bold text-dark">🪪 사원증 관리시스템</h4>
            <p class="text-muted">관리자 로그인</p>
        </div>
        @if(session('error'))
            <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif
        <form method="POST" action="{{ route('admin.login.submit') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">아이디</label>
                <input type="text" name="login_id" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">비밀번호</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-dark w-100">로그인</button>
        </form>
    </div>
</body>
</html>
