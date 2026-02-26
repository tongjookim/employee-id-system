<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR 인증 실패</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, 'Noto Sans KR', sans-serif;
            background: #0f172a; min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            color: #e2e8f0;
        }
        .container { text-align: center; padding: 2rem; max-width: 400px; }
        .icon { font-size: 4rem; margin-bottom: 1rem; }
        h1 { font-size: 1.5rem; color: #ef4444; margin-bottom: .5rem; }
        p { color: rgba(255,255,255,.6); line-height: 1.7; margin-bottom: 1.5rem; }
        .reason-box {
            background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.2);
            border-radius: .75rem; padding: 1rem 1.2rem; margin-bottom: 1.5rem;
            font-size: .9rem; color: #fca5a5;
        }
        a {
            display: inline-block; padding: .75rem 2rem;
            background: rgba(255,255,255,.08); color: #fff;
            border-radius: .5rem; text-decoration: none; font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        @if(($reason ?? 'not_found') === 'expired')
            <div class="icon">⏱️</div>
            <h1>QR 코드 만료</h1>
            <div class="reason-box">
                이 QR 코드의 유효시간이 경과되었습니다.<br>
                사원증 앱에서 새로운 QR 코드를 발급받으세요.
            </div>
        @else
            <div class="icon">❌</div>
            <h1>인증 실패</h1>
            <div class="reason-box">
                유효하지 않은 QR 코드이거나, 이미 만료된 코드입니다.
            </div>
        @endif
        <p>문제가 지속되면 관리자에게 문의하세요.</p>
        <a href="/">홈으로</a>
    </div>
</body>
</html>
