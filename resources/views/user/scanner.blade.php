<!DOCTYPE html>
<html lang="ko">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR 로그인 스캐너</title>
    <script src="https://unpkg.com/html5-qrcode"></script>
</head>
<body style="background: #0f172a; color: white; margin: 0; display: flex; flex-direction: column; height: 100vh;">
    <div style="padding: 1rem; text-align: center;">
        <a href="{{ route('user.idcard') }}" style="color: white; text-decoration: none;">← 뒤로가기</a>
        <h3 style="margin-top: 1rem;">PC 화면의 QR을 스캔하세요</h3>
    </div>
    
    <div id="reader" style="width: 100%; max-width: 500px; margin: 0 auto;"></div>

    <script>
        const html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 });
        
        function onScanSuccess(decodedText) {
            html5QrcodeScanner.clear(); // 스캔 중지
            
            // 예: decodedText가 "wp_token=ABC123" 형태일 때
            const urlParams = new URLSearchParams(decodedText.split('?')[1]);
            const wpToken = urlParams.get('wp_token');

            if (wpToken) {
                if (confirm('PC 로그인을 승인하시겠습니까?')) {
                    // 워드프레스 승인 API로 요청 전송 (또는 Laravel 백엔드를 거쳐 전송)
                    fetch('https://워드프레스주소.com/wp-json/my-sso/v1/approve', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Auth-Employee': '{{ session("employee_number") }}',
                            'X-API-KEY': '보안_API_키' // 사원증 서버와 WP 간의 약속된 키
                        },
                        body: JSON.stringify({ token: wpToken })
                    }).then(() => {
                        alert('로그인이 승인되었습니다.');
                        window.location.href = "{{ route('user.idcard') }}";
                    });
                } else {
                    window.location.reload(); // 취소 시 다시 스캔
                }
            }
        }
        html5QrcodeScanner.render(onScanSuccess);
    </script>
</body>
</html>
