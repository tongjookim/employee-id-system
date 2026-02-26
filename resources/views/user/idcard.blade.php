<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#0f172a">
    <title>내 사원증</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, 'Pretendard', sans-serif;
            background: #0f172a; min-height: 100vh;
            display: flex; flex-direction: column;
        }

        /* 상단 바 */
        .top-bar {
            display: flex; justify-content: space-between; align-items: center;
            padding: .8rem 1rem; color: #fff;
        }
        .top-bar .name { font-weight: 700; font-size: 1.05rem; }
        .top-bar a { color: rgba(255,255,255,.6); text-decoration: none; font-size: .85rem; }

        /* 사원증 카드 */
        .card-container {
            flex: 1; display: flex; align-items: center; justify-content: center;
            padding: 1rem;
        }
        .id-card {
            position: relative; width: 100%; max-width: 380px;
            border-radius: 1rem; overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,.4);
            aspect-ratio: auto;
        }
        .id-card .bg-image {
            width: 100%; display: block;
        }
        /* CSS 오버레이 렌더링 필드 */
        .id-card .overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        }
        .id-card .field {
            position: absolute; transform-origin: top left;
        }
        .id-card .field.type-text {
            white-space: nowrap;
        }
        .id-card .field.type-image img {
            width: 100%; height: 100%; object-fit: cover; border-radius: 4px;
        }
        .id-card .field.type-qr_code img {
            width: 100%; height: 100%;
        }

        /* QR 확대 오버레이 */
        .qr-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,.9); z-index: 1000;
            display: none; align-items: center; justify-content: center;
            flex-direction: column;
        }
        .qr-overlay.show { display: flex; }
        .qr-overlay img { width: 280px; height: 280px; border-radius: .5rem; background: #fff; padding: 10px; }
        .qr-overlay .close-hint {
            color: rgba(255,255,255,.5); margin-top: 1.5rem; font-size: .85rem;
        }
        .qr-overlay .name-badge {
            color: #fff; font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem;
        }

        /* 하단 메뉴 */
        .bottom-bar {
            display: flex; justify-content: space-around; align-items: center;
            padding: .8rem 1rem; background: rgba(255,255,255,.05);
        }
        .bottom-bar button, .bottom-bar a {
            background: none; border: none; color: rgba(255,255,255,.6);
            font-size: .75rem; text-align: center; cursor: pointer; text-decoration: none;
            display: flex; flex-direction: column; align-items: center; gap: .2rem;
        }
        .bottom-bar button:hover, .bottom-bar a:hover { color: #fff; }
        .bottom-bar .icon { font-size: 1.3rem; }

        /* 흔들기 안내 */
        .shake-hint {
            text-align: center; color: rgba(255,255,255,.3); font-size: .75rem;
            padding: .3rem; animation: fadeInUp .8s;
        }
        @keyframes fadeInUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
    </style>
</head>
<body>
    {{-- 상단 바 --}}
    <div class="top-bar">
        <span class="name">{{ $employee->name }}</span>
        <div>
            <a href="{{ route('user.guide') }}">안내</a>
            <form action="{{ route('user.logout') }}" method="POST" style="display:inline;">
                @csrf
                <button type="submit" style="background:none;border:none;color:rgba(255,255,255,.6);font-size:.85rem;cursor:pointer;margin-left:.8rem;">로그아웃</button>
            </form>
        </div>
    </div>

    {{-- 사원증 카드 --}}
    <div class="card-container">
        <div class="id-card" id="idCard">
            <img src="{{ $renderData['template']['background_image'] }}" alt="배경" class="bg-image" id="bgImg">
            <div class="overlay" id="overlay">
                {{-- JS로 렌더링 --}}
            </div>
        </div>
    </div>

    <div class="shake-hint">📱 흔들거나 QR을 탭하면 QR 코드가 확대됩니다</div>

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
    <div class="qr-overlay" id="qrOverlay" onclick="hideQr()">
        <div class="name-badge">{{ $employee->name }} ({{ $employee->employee_number }})</div>
        <img src="" alt="QR" id="qrLargeImg">
        <div class="close-hint">화면을 탭하면 닫힙니다</div>
    </div>

    <script>
    const renderData = @json($renderData);
    const bgImg = document.getElementById('bgImg');
    const overlay = document.getElementById('overlay');

    let qrImageSrc = '';

    bgImg.onload = function() {
        renderFields();
    };

    function renderFields() {
        const card = document.getElementById('idCard');
        const displayW = card.offsetWidth;
        const realW = renderData.template.canvas_width;
        const scale = displayW / realW;

        overlay.innerHTML = '';

        renderData.fields.forEach(f => {
            const el = document.createElement('div');
            el.className = `field type-${f.field_type}`;

            el.style.left = (f.pos_x * scale) + 'px';
            el.style.top = (f.pos_y * scale) + 'px';

            if (f.field_type === 'text') {
                el.textContent = f.value || '';
                el.style.fontSize = Math.max(10, f.font_size * scale) + 'px';
                el.style.color = f.font_color || '#333';
                el.style.fontWeight = f.is_bold ? '700' : '400';
                el.style.textAlign = f.text_align || 'center';
                if (f.text_align === 'center') {
                    el.style.transform = 'translateX(-50%)';
                }
            } else if (f.field_type === 'image') {
                const img = document.createElement('img');
                img.src = f.value;
                img.alt = '사진';
                el.appendChild(img);
                if (f.width) el.style.width = (f.width * scale) + 'px';
                if (f.height) el.style.height = (f.height * scale) + 'px';
            } else if (f.field_type === 'qr_code') {
                const img = document.createElement('img');
                img.src = f.value;
                img.alt = 'QR';
                img.style.cursor = 'pointer';
                img.onclick = (e) => { e.stopPropagation(); showQr(); };
                el.appendChild(img);
                if (f.width) el.style.width = (f.width * scale) + 'px';
                if (f.height) el.style.height = (f.height * scale) + 'px';
                qrImageSrc = f.value;
            }

            overlay.appendChild(el);
        });
    }

    // 반응형 - 리사이즈 대응
    window.addEventListener('resize', renderFields);

    // QR 확대
    function showQr() {
        // AJAX로 최신 QR 가져오기
        fetch('{{ route("user.idcard.qr-data") }}')
            .then(r => r.json())
            .then(data => {
                document.getElementById('qrLargeImg').src = data.qr_image;
            })
            .catch(() => {
                document.getElementById('qrLargeImg').src = qrImageSrc;
            });

        document.getElementById('qrOverlay').classList.add('show');
    }

    function hideQr() {
        document.getElementById('qrOverlay').classList.remove('show');
    }

    // 흔들기 감지 (모바일)
    if (window.DeviceMotionEvent) {
        let lastShake = 0;
        let lastX = 0, lastY = 0, lastZ = 0;

        window.addEventListener('devicemotion', (e) => {
            const acc = e.accelerationIncludingGravity;
            if (!acc) return;

            const dx = Math.abs(acc.x - lastX);
            const dy = Math.abs(acc.y - lastY);
            const dz = Math.abs(acc.z - lastZ);

            if ((dx + dy + dz) > 30) {
                const now = Date.now();
                if (now - lastShake > 1500) {
                    lastShake = now;
                    showQr();
                }
            }

            lastX = acc.x; lastY = acc.y; lastZ = acc.z;
        });
    }
    </script>
</body>
</html>
