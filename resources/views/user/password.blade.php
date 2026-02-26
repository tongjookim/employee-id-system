<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>비밀번호 변경</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, 'Pretendard', sans-serif; background: #f8fafc; min-height: 100vh; padding: 0 0 2rem; }
        .header { background: #0f172a; color: #fff; padding: 1.2rem; display: flex; align-items: center; gap: .8rem; }
        .header a { color: #fff; text-decoration: none; font-size: 1.3rem; }
        .header h1 { font-size: 1.1rem; font-weight: 600; }
        .content { padding: 1.5rem; max-width: 500px; margin: 0 auto; }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; font-size: .85rem; font-weight: 600; color: #475569; margin-bottom: .4rem; }
        .form-group input { width: 100%; padding: .8rem 1rem; border: 1.5px solid #e2e8f0; border-radius: .6rem; font-size: 1rem; outline: none; }
        .form-group input:focus { border-color: #3b82f6; }
        .btn-submit { width: 100%; padding: 1rem; border: none; border-radius: .6rem; background: #0f172a; color: #fff; font-size: 1rem; font-weight: 600; cursor: pointer; }
        .error-msg { background: #fef2f2; color: #dc2626; padding: .6rem 1rem; border-radius: .5rem; font-size: .85rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="header">
        <a href="{{ route('user.idcard') }}">←</a>
        <h1>비밀번호 변경</h1>
    </div>
    <div class="content">
        @if(session('error'))
            <div class="error-msg">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="error-msg">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('user.password.update') }}">
            @csrf
            <div class="form-group">
                <label>현재 비밀번호</label>
                <input type="password" name="current_password" required>
            </div>
            <div class="form-group">
                <label>새 비밀번호 (최소 4자)</label>
                <input type="password" name="new_password" required>
            </div>
            <div class="form-group">
                <label>새 비밀번호 확인</label>
                <input type="password" name="new_password_confirmation" required>
            </div>
            <button type="submit" class="btn-submit">변경하기</button>
        </form>
    </div>
</body>
</html>
