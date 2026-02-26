<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>사원증 이용 안내</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:-apple-system,'Pretendard',sans-serif; background:#f8fafc; min-height:100vh; padding: 0 0 2rem; }
        .header { background:#0f172a; color:#fff; padding:1.2rem; display:flex; align-items:center; gap:.8rem; }
        .header a { color:#fff; text-decoration:none; font-size:1.3rem; }
        .header h1 { font-size:1.1rem; font-weight:600; }
        .content { padding:1.5rem; max-width:600px; margin:0 auto; }
        .section { background:#fff; border-radius:.8rem; padding:1.5rem; margin-bottom:1rem; box-shadow:0 1px 3px rgba(0,0,0,.06); }
        .section h3 { font-size:1rem; font-weight:700; color:#1e293b; margin-bottom:.8rem; display:flex; align-items:center; gap:.5rem; }
        .section p, .section li { color:#475569; font-size:.9rem; line-height:1.7; }
        .section ul { padding-left:1.2rem; }
        .section li { margin-bottom:.3rem; }
    </style>
</head>
<body>
    <div class="header">
        <a href="{{ route('user.idcard') }}">←</a>
        <h1>사원증 이용 안내</h1>
    </div>

    <div class="content">
        <div class="section">
            <h3>📱 사용 방법</h3>
            <ul>
                <li>사번과 비밀번호로 로그인하면 디지털 사원증이 표시됩니다.</li>
                <li>QR 코드를 탭하거나 스마트폰을 흔들면 QR 코드가 확대됩니다.</li>
                <li>하단의 다운로드 버튼으로 사원증 이미지를 저장할 수 있습니다.</li>
            </ul>
        </div>

        <div class="section">
            <h3>🏢 사용처</h3>
            <ul>
                <li>사옥 출입 시 QR 코드 스캔</li>
                <li>회의실 예약 및 입장</li>
                <li>사내 식당 이용</li>
                <li>비품 수령 시 본인 확인</li>
            </ul>
        </div>

        <div class="section">
            <h3>🔒 보안 유의사항</h3>
            <ul>
                <li>사원증 화면을 타인에게 캡처하여 전송하지 마세요.</li>
                <li>로그인 후 장시간 사용하지 않으면 자동 로그아웃됩니다.</li>
                <li>QR 코드에는 시간 제한 토큰이 포함되어 있어 재사용이 제한됩니다.</li>
                <li>비밀번호가 노출되었다고 판단될 경우 즉시 관리자에게 문의하세요.</li>
            </ul>
        </div>

        <div class="section">
            <h3>❓ 문의</h3>
            <p>사원증 관련 문의는 인사팀(내선 1234) 또는 IT지원팀(내선 5678)으로 연락해주세요.</p>
        </div>
    </div>
</body>
</html>
