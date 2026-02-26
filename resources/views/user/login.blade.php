<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
    <meta name="theme-color" content="#0f172a">
    <title>모바일 사원증 - 로그인</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, 'Pretendard', sans-serif;
            background: linear-gradient(160deg, #0f172a 0%, #1e3a5f 50%, #0f172a 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 1rem;
        }
        .login-box {
            width: 100%; max-width: 380px; background: rgba(255,255,255,.95);
            border-radius: 1.2rem; padding: 2.5rem 2rem; box-shadow: 0 20px 60px rgba(0,0,0,.3);
            backdrop-filter: blur(10px);
        }
        .logo { text-align: center; margin-bottom: 2rem; }
        .logo .icon { font-size: 3rem; }
        .logo h1 { font-size: 1.3rem; font-weight: 700; color: #1e293b; margin-top: .5rem; }
        .logo p { color: #64748b; font-size: .85rem; }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; font-size: .8rem; font-weight: 600; color: #475569; margin-bottom: .4rem; }
        .form-group input {
            width: 100%; padding: .8rem 1rem; border: 1.5px solid #e2e8f0; border-radius: .6rem;
            font-size: 1rem; outline: none; transition: border-color .2s;
        }
        .form-group input:focus { border-color: #3b82f6; }
        .btn-login {
            width: 100%; padding: .9rem; border: none; border-radius: .6rem;
            background: linear-gradient(135deg, #1e3a5f, #3b82f6); color: #fff;
            font-size: 1rem; font-weight: 600; cursor: pointer; transition: transform .1s;
        }
        .btn-login:active { transform: scale(.98); }
        .error-msg { background: #fef2f2; color: #dc2626; padding: .6rem 1rem; border-radius: .5rem; font-size: .85rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo">
            <div class="icon">🪪</div>
            <h1>모바일 사원증</h1>
            <p>사번과 비밀번호로 로그인하세요</p>
        </div>

        @if(session('error'))
            <div class="error-msg">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('user.login.submit') }}">
            @csrf
            <div class="form-group">
                <label>사번</label>
                <input type="text" name="employee_number" placeholder="사번을 입력하세요" required autofocus
                       autocomplete="username" inputmode="text">
            </div>
            <div class="form-group">
                <label>비밀번호</label>
                <input type="password" name="password" placeholder="비밀번호를 입력하세요" required
                       autocomplete="current-password">
            </div>
            <button type="submit" class="btn-login">로그인</button>
        </form>
    </div>
<script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').then(reg => {
                    console.log('ServiceWorker registered');
                }).catch(err => {
                    console.log('ServiceWorker registration failed: ', err);
                });
            });
        }
    </script>
</body>
</html>
