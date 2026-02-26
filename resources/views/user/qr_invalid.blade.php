<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR 코드 오류</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:-apple-system,sans-serif; background:#fef2f2; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:1rem; }
        .error-card {
            max-width:380px; width:100%; background:#fff; border-radius:1.2rem;
            box-shadow:0 10px 40px rgba(0,0,0,.1); text-align:center; padding:2.5rem 2rem;
        }
        .icon { font-size:3.5rem; margin-bottom:1rem; }
        h2 { color:#dc2626; margin-bottom:.5rem; }
        p { color:#64748b; }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="icon">❌</div>
        <h2>인증 실패</h2>
        <p>유효하지 않거나 만료된 QR 코드입니다.<br>관리자에게 문의해주세요.</p>
    </div>
</body>
</html>
