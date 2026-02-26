<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#0f172a">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>내 사원증</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        /* ══════════════════════════════════════
           ★ 보안: 복사/선택/저장 전면 차단
           ══════════════════════════════════════ */
        body, html {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            -webkit-touch-callout: none;   /* iOS 길게 누르기 메뉴 차단 */
            -webkit-text-size-adjust: none;
        }
        img {
            -webkit-user-drag: none;
            -khtml-user-drag: none;
            -moz-user-drag: none;
            -o-user-drag: none;
            user-drag: none;
            pointer-events: none;          /* 이미지 직접 클릭/저장 차단 */
            -webkit-touch-callout: none;
        }
        /* QR 클릭은 부모 div에서 처리 */
        .qr-click-area { pointer-events: auto; cursor: pointer; }

        body {
            font-family: -apple-system, 'Pretendard', 'Noto Sans KR', sans-serif;
            background: #0f172a; min-height: 100vh;
            display: flex; flex-direction: column;
        }

        .top-bar {
            display: flex; justify-content: space-between; align-items: center;
            padding: .8rem 1rem; color: #fff;
        }
        .top-bar .name { font-weight: 700; font-size: 1.05rem; }
        .top-bar a, .top-bar button.link {
            color: rgba(255,255,255,.6); text-decoration: none; font-size: .85rem;
            background: none; border: none; cursor: pointer;
            pointer-events: auto;
        }
        .top-bar form button { pointer-events: auto; }

        .card-container {
            flex: 1; display: flex; align-items: center; justify-content: center;
            padding: 1rem; flex-direction: column;
        }
        .id-card {
            position: relative; width: 100%; max-width: 380px;
            border-radius: 1rem; overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,.4);
        }
        .id-card .bg-image { width: 100%; display: block; }
        .id-card .overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
        .id-card .field { position: absolute; transform-origin: top left; }
        .id-card .field.type-text { white-space: nowrap; }
        .id-card .field.type-image { overflow: hidden; border-radius: 4px; }
        .id-card .field.type-image img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .id-card .field.type-qr_code img { width: 100%; height: 100%; display: block; }

        /* ★ 보안: 이미지 위에 투명 레이어 → 우클릭/길게누르기 가로챔 */
        .security-shield {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            z-index: 20; pointer-events: auto;
        }

        /* QR 타이머 */
        .qr-timer {
            margin-top: .8rem; text-align: center;
            display: flex; align-items: center; justify-content: center; gap: .5rem;
        }
        .qr-timer .timer-bar {
            width: 160px; height: 5px; border-radius: 3px;
            background: rgba(255,255,255,.1); overflow: hidden;
        }
        .qr-timer .timer-fill {
            height: 100%; border-radius: 3px;
            background: linear-gradient(90deg, #6366f1, #22c55e);
            transition: width 1s linear;
        }
        .qr-timer .timer-fill.expiring {
            background: linear-gradient(90deg, #ef4444, #f59e0b);
        }
        .qr-timer .timer-text {
            color: rgba(255,255,255,.5); font-size: .75rem;
            font-variant-numeric: tabular-nums; min-width: 40px;
        }
        .qr-timer .timer-text.expiring { color: #f59e0b; font-weight: 700; }

        .qr-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,.92); z-index: 1000;
            display: none; align-items: center; justify-content: center;
            flex-direction: column; backdrop-filter: blur(8px);
        }
        .qr-overlay.show { display: flex; }
        .qr-overlay img.qr-large {
            width: 280px; height: 280px; border-radius: .75rem;
            background: #fff; padding: 12px;
        }
        .qr-overlay .close-hint { color: rgba(255,255,255,.4); margin-top: 1.5rem; font-size: .85rem; }
        .qr-overlay .name-badge { color: #fff; font-size: 1.2rem; font-weight: 700; margin-bottom: .5rem; }
        .qr-overlay .dept-badge { color: rgba(255,255,255,.5); font-size: .9rem; margin-bottom: 1rem; }
        .qr-overlay .overlay-timer {
            margin-top: 1rem; display: flex; align-items: center; gap: .5rem;
        }
        .qr-overlay .overlay-timer .timer-ring { width: 36px; height: 36px; }
        .qr-overlay .overlay-timer .timer-ring svg { transform: rotate(-90deg); }
        .qr-overlay .overlay-timer .timer-ring .ring-bg { fill: none; stroke: rgba(255,255,255,.1); stroke-width: 3; }
        .qr-overlay .overlay-timer .timer-ring .ring-fill {
            fill: none; stroke: #6366f1; stroke-width: 3; stroke-linecap: round;
            transition: stroke-dashoffset 1s linear;
        }
        .qr-overlay .overlay-timer .timer-ring .ring-fill.expiring { stroke: #f59e0b; }
        .qr-overlay .overlay-timer .timer-count {
            color: rgba(255,255,255,.6); font-size: .9rem; font-variant-numeric: tabular-nums;
        }
        /* 오버레이 클릭 영역 */
        .qr-overlay .close-area {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1; pointer-events: auto; cursor: pointer;
        }

        .bottom-bar {
            display: flex; justify-content: space-around; align-items: center;
            padding: .8rem 1rem; background: rgba(255,255,255,.05);
        }
        .bottom-bar button, .bottom-bar a {
            background: none; border: none; color: rgba(255,255,255,.6);
            font-size: .75rem; text-align: center; cursor: pointer; text-decoration: none;
            display: flex; flex-direction: column; align-items: center; gap: .2rem;
            pointer-events: auto;
        }
        .bottom-bar .icon { font-size: 1.3rem; }

        .shake-hint {
            text-align: center; color: rgba(255,255,255,.25); font-size: .75rem;
            padding: .3rem; animation: fadeInUp .8s;
        }
        @keyframes fadeInUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

        /* ★ 보안: 인쇄 차단 */
        @media print {
            body { display: none !important; }
            html::after {
                content: '사원증은 인쇄할 수 없습니다.';
                display: block; padding: 2rem; text-align: center;
                font-size: 1.5rem; color: #999;
            }
        }
    </style>
</head>
<body oncontextmenu="return false;" ondragstart="return false;" onselectstart="return false;">
    {{-- 상단 바 --}}
    <div class="top-bar">
        <span class="name">{{ $employee->name }}</span>
        <div>
            <a href="{{ route('user.password.form') }}" style="margin-right:.8rem;">비번변경</a>
            <a href="{{ route('user.guide') }}">안내</a>
            <form action="{{ route('user.logout') }}" method="POST" style="display:inline;">
                @csrf
                <button type="submit" class="link" style="margin-left:.8rem;">로그아웃</button>
            </form>
        </div>
    </div>

    {{-- 사원증 카드 --}}
    <div class="card-container">
        <div class="id-card" id="idCard">
            <img src="{{ $renderData['template']['background_image'] }}" alt="배경" class="bg-image" id="bgImg">
            <div class="overlay" id="overlay"></div>
            {{-- ★ 보안: 카드 전체를 덮는 투명 실드 (우클릭→이미지 저장 차단) --}}
            <div class="security-shield" id="cardShield"></div>
        </div>

        <div class="qr-timer" id="qrTimer">
            <div class="timer-bar"><div class="timer-fill" id="timerFill"></div></div>
            <span class="timer-text" id="timerText">{{ $qrRemaining }}초</span>
        </div>
    </div>

    <div class="shake-hint" id="shakeHint">📱 흔들거나 QR을 탭하면 QR 코드가 확대됩니다</div>

    {{-- 하단 메뉴 --}}
    <div class="bottom-bar">
        <button onclick="showQr()">
            <span class="icon">📷</span>QR 확대
        </button>
        <a href="{{ route('user.idcard.download') }}">
            <span class="icon">💾</span>다운로드
        </a>
        <a href="{{ route('user.guide') }}">
            <span class="icon">📋</span>안내
        </a>
    </div>

    {{-- QR 확대 오버레이 --}}
    <div class="qr-overlay" id="qrOverlay">
        <div class="close-area" onclick="hideQr()"></div>
        <div class="name-badge">{{ $employee->name }}</div>
        <div class="dept-badge">{{ $employee->department }} · {{ $employee->position }}</div>
        <img src="" alt="QR" id="qrLargeImg" class="qr-large">
        <div class="overlay-timer">
            <div class="timer-ring">
                <svg width="36" height="36" viewBox="0 0 36 36">
                    <circle class="ring-bg" cx="18" cy="18" r="15"/>
                    <circle class="ring-fill" id="ringFill" cx="18" cy="18" r="15"
                        stroke-dasharray="94.25" stroke-dashoffset="0"/>
                </svg>
            </div>
            <span class="timer-count" id="overlayTimerText">10초</span>
        </div>
        <div class="close-hint" onclick="hideQr()" style="cursor:pointer;">화면을 탭하면 닫힙니다</div>
    </div>

    <script>
    // ══════════════════════════════════════
    // ★ 보안: 키보드 단축키 차단
    // ══════════════════════════════════════
    document.addEventListener('keydown', function(e) {
        // Ctrl+C, Ctrl+A, Ctrl+S, Ctrl+P, Ctrl+Shift+I, F12
        if (e.ctrlKey && ['c','a','s','p','u'].includes(e.key.toLowerCase())) {
            e.preventDefault(); return false;
        }
        if (e.ctrlKey && e.shiftKey && ['i','j','c'].includes(e.key.toLowerCase())) {
            e.preventDefault(); return false;
        }
        if (e.key === 'F12' || e.key === 'PrintScreen') {
            e.preventDefault(); return false;
        }
    });

    // 터치 길게 누르기 차단 (iOS/Android 이미지 저장 방지)
    document.addEventListener('touchstart', function(e) {
        if (e.target.tagName === 'IMG') e.preventDefault();
    }, { passive: false });

    // ══════════════════════════════════════
    // QR 시스템
    // ══════════════════════════════════════
    const QR_TTL = {{ $qrTtl }};
    let qrExpiresAt = new Date('{{ $qrExpiresAt }}');
    let qrImageSrc = '';
    let refreshing = false;

    const renderData = @json($renderData);
    const bgImg = document.getElementById('bgImg');
    const overlay = document.getElementById('overlay');

    bgImg.onload = function() { renderFields(); };

    // 카드 실드 클릭 → QR 확대
    document.getElementById('cardShield').addEventListener('click', function(e) {
        // QR 영역 클릭 감지
        const card = document.getElementById('idCard');
        const rect = card.getBoundingClientRect();
        const scale = card.offsetWidth / renderData.template.canvas_width;

        const clickX = (e.clientX - rect.left) / scale;
        const clickY = (e.clientY - rect.top) / scale;

        // QR 필드 찾기
        const qrField = renderData.fields.find(f => f.field_type === 'qr_code');
        if (qrField) {
            const qx = qrField.pos_x, qy = qrField.pos_y;
            const qw = qrField.width || 180, qh = qrField.height || 180;
            if (clickX >= qx && clickX <= qx + qw && clickY >= qy && clickY <= qy + qh) {
                showQr();
            }
        }
    });

    function renderFields() {
        const card = document.getElementById('idCard');
        const displayW = card.offsetWidth;
        const realW = renderData.template.canvas_width;
        const scale = displayW / realW;
        overlay.innerHTML = '';

        renderData.fields.forEach(f => {
            const el = document.createElement('div');
            el.className = `field type-${f.field_type}`;

            if (f.field_type === 'text') {
                el.textContent = f.value || '';
                el.style.fontSize = Math.max(10, f.font_size * scale) + 'px';
                el.style.color = f.font_color || '#333';
                el.style.fontWeight = f.is_bold ? '700' : '400';

                const sx = f.pos_x * scale;
                const sy = f.pos_y * scale;
                el.style.top = sy + 'px';
                el.style.left = sx + 'px';

                const align = f.text_align || 'center';
                if (align === 'center') {
                    el.style.transform = 'translateX(-50%)';
                    el.style.textAlign = 'center';
                } else if (align === 'right') {
                    el.style.transform = 'translateX(-100%)';
                    el.style.textAlign = 'right';
                } else {
                    el.style.textAlign = 'left';
                }
            } else if (f.field_type === 'image') {
                el.style.left = (f.pos_x * scale) + 'px';
                el.style.top = (f.pos_y * scale) + 'px';
                if (f.width) el.style.width = (f.width * scale) + 'px';
                if (f.height) el.style.height = (f.height * scale) + 'px';
                const img = document.createElement('img');
                img.src = f.value;
                img.draggable = false;
                el.appendChild(img);
            } else if (f.field_type === 'qr_code') {
                el.style.left = (f.pos_x * scale) + 'px';
                el.style.top = (f.pos_y * scale) + 'px';
                if (f.width) el.style.width = (f.width * scale) + 'px';
                if (f.height) el.style.height = (f.height * scale) + 'px';
                const img = document.createElement('img');
                img.src = f.value;
                img.id = 'cardQrImg';
                img.draggable = false;
                el.appendChild(img);
                qrImageSrc = f.value;
            }
            overlay.appendChild(el);
        });
    }

    window.addEventListener('resize', renderFields);

    // ── QR 자동 갱신 ──
    function refreshQr() {
        if (refreshing) return;
        refreshing = true;
        document.getElementById('timerText').textContent = '갱신중…';
        // ★ 추가: 확대된 QR 화면(오버레이)의 텍스트도 '갱신중…'으로 변경
        document.getElementById('overlayTimerText').textContent = '갱신중…';

        fetch('{{ route("user.idcard.qr-data") }}', {
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Cache-Control': 'no-cache',
            },
            cache: 'no-store'
        })
        .then(r => r.json())
        .then(data => {
            qrImageSrc = data.qr_image;
            qrExpiresAt = new Date(data.expires_at);

            const cardQr = document.getElementById('cardQrImg');
            if (cardQr) cardQr.src = data.qr_image;
            document.getElementById('qrLargeImg').src = data.qr_image;

            refreshing = false;
            updateTimer();
        })
        .catch(() => { refreshing = false; setTimeout(refreshQr, 2000); });
    }

    function updateTimer() {
        // ★ 추가: 통신(갱신) 중일 때는 타이머 UI 업데이트를 중단하여 깜빡임 방지
        if (refreshing) return;
        
        const now = new Date();
        const remaining = Math.max(0, Math.floor((qrExpiresAt - now) / 1000));
        const pct = Math.max(0, (remaining / QR_TTL) * 100);
        const isExpiring = remaining <= 3;

        const fill = document.getElementById('timerFill');
        const text = document.getElementById('timerText');
        fill.style.width = pct + '%';
        text.textContent = remaining + '초';
        fill.classList.toggle('expiring', isExpiring);
        text.classList.toggle('expiring', isExpiring);

        const ringFill = document.getElementById('ringFill');
        const overlayText = document.getElementById('overlayTimerText');
        ringFill.style.strokeDashoffset = 94.25 * (1 - remaining / QR_TTL);
        ringFill.classList.toggle('expiring', isExpiring);
        overlayText.textContent = remaining + '초';

        if (remaining <= 0) refreshQr();
    }

    setInterval(updateTimer, 1000);
    updateTimer();

    function showQr() {
        document.getElementById('qrLargeImg').src = qrImageSrc;
        document.getElementById('qrOverlay').classList.add('show');
    }
    function hideQr() {
        document.getElementById('qrOverlay').classList.remove('show');
    }

    // 흔들기
    if (window.DeviceMotionEvent) {
        let lastShake = 0, lastX = 0, lastY = 0, lastZ = 0;
        window.addEventListener('devicemotion', (e) => {
            const acc = e.accelerationIncludingGravity;
            if (!acc) return;
            if ((Math.abs(acc.x-lastX) + Math.abs(acc.y-lastY) + Math.abs(acc.z-lastZ)) > 30) {
                const now = Date.now();
                if (now - lastShake > 1500) { lastShake = now; showQr(); }
            }
            lastX = acc.x; lastY = acc.y; lastZ = acc.z;
        });
    }
    setTimeout(() => { const h = document.getElementById('shakeHint'); if(h) h.style.display='none'; }, 5000);
    </script>
</body>
</html>
