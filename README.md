# 🪪 사원증 관리 및 모바일 전자신분증 시스템

Laravel 11 + Blade + MySQL 기반의 사원증 관리/모바일 전자신분증 통합 시스템.

## 빠른 시작 (Quick Start)

```bash
# 1. 프로젝트 디렉토리 이동
cd employee-id-system

# 2. 자동 설치 (대화형)
bash setup.sh

# 3. 개발 서버 실행
php artisan serve

# 4. 브라우저 접속
# 관리자:    http://localhost:8000/admin/login
# 모바일:    http://localhost:8000/user/login
```

**테스트 계정**
- 관리자: `admin` / `admin1234!`
- 초기 직원 데이터는 시드에 포함되어 있지 않으므로, 관리자 대시보드에서 직접 등록하세요.

---

## 시스템 구조

```
Nginx (80/443)
  └── PHP-FPM (Laravel 11)
        ├── /admin/*        관리자 대시보드 (Blade SSR)
        ├── /user/*         모바일 사원증 (반응형 Blade)
        ├── /verify/{token} QR 코드 검증 (공개)
        └── /api/*          REST API
              └── MySQL 8.0 (employee_id_system)
```

## 서버 요구사항

| 구성요소 | 최소 버전 |
|----------|-----------|
| PHP      | 8.2+ (ext-gd, ext-mbstring, ext-pdo_mysql, ext-openssl) |
| MySQL    | 8.0+ |
| Nginx    | 1.18+ |
| Composer | 2.x |

## 수동 설치 (setup.sh 대신)

```bash
# 의존성 설치
composer install --no-dev --optimize-autoloader

# 환경설정
cp .env.example .env
php artisan key:generate

# .env에서 DB 접속정보 수정
vi .env

# DB 마이그레이션 + 시드
php artisan migrate --seed

# 스토리지 링크
php artisan storage:link

# 기본 배경이미지 복사
cp public/uploads/templates/default_bg.png storage/app/public/templates/

# 한글 폰트 (서버사이드 이미지 합성용, 선택사항)
mkdir -p storage/app/fonts
# NanumGothic.ttf, NanumGothicBold.ttf를 여기에 복사

# 캐시 최적화 (프로덕션)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 권한
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

## Nginx 배포

```bash
# Nginx 설정 복사
sudo cp nginx.conf /etc/nginx/sites-available/idcard
sudo ln -s /etc/nginx/sites-available/idcard /etc/nginx/sites-enabled/
# nginx.conf 내 server_name, root 경로 수정
sudo nginx -t && sudo systemctl reload nginx
```

## 주요 기능

### 관리자 (`/admin`)
- **대시보드** — 전체/활성 직원, 템플릿 수, 오늘 QR 스캔 통계
- **직원 CRUD** — 검색/부서/상태 필터, 페이지네이션
- **엑셀 일괄등록** — CSV/XLSX 파일 업로드
- **디자인 커스텀** — 배경 이미지 업로드 + 드래그&드롭 좌표 매핑 툴
- **사원증 미리보기** — GD 서버사이드 이미지 합성
- **QR 재생성** — 직원별 보안 토큰 갱신

### 사용자 (`/user`, 반응형 모바일)
- **사번/비밀번호 로그인**
- **전자 사원증** — CSS 기반 실시간 렌더링 (관리자 디자인 반영)
- **QR 확대** — 탭 또는 흔들기(DeviceMotion) 제스처
- **사원증 다운로드** — 서버사이드 PNG 이미지 합성
- **안내 가이드** — 사용처, 보안 유의사항

### QR 검증 (공개)
- `GET /verify/{token}` — 웹 페이지 신원 확인
- `GET /api/verify/{token}` — JSON API

## 디렉토리 구조

```
employee-id-system/
├── app/
│   ├── Http/Controllers/
│   │   ├── Admin/          AuthController, DashboardController,
│   │   │                   EmployeeController, DesignTemplateController
│   │   ├── User/           AuthController, IdCardController
│   │   └── Api/            QrVerifyController
│   ├── Http/Middleware/     AdminAuthenticate, EmployeeAuthenticate
│   ├── Models/              Admin, Employee, DesignTemplate,
│   │                        FieldMapping, QrAccessLog
│   ├── Providers/           AppServiceProvider
│   └── Services/            QrCodeService, IdCardRenderService,
│                            ExcelImportService
├── bootstrap/app.php        미들웨어 별칭 등록
├── config/                  app, database, session, filesystems 등
├── database/
│   ├── migrations/          4개 마이그레이션 파일
│   ├── seeders/             초기 관리자 + 기본 템플릿 + 필드매핑
│   └── schema.sql           직접 MySQL 실행용
├── public/
│   ├── index.php            ★ Laravel 진입점
│   └── uploads/templates/   기본 배경 이미지
├── resources/views/
│   ├── layouts/admin.blade.php
│   ├── admin/               dashboard, login, employees/*, templates/*
│   └── user/                login, idcard, guide, qr_verify, qr_invalid
├── routes/web.php
├── storage/app/public/      photos/, templates/ (storage:link)
├── setup.sh                 자동 설치 스크립트
├── nginx.conf               Nginx 설정 예시
├── composer.json
└── .env.example
```

## DB 테이블

| 테이블 | 설명 |
|--------|------|
| `admins` | 관리자 계정 (role: super_admin/admin/viewer) |
| `design_templates` | 사원증 배경 디자인 |
| `field_mappings` | 필드별 좌표(x,y), 폰트, 크기 매핑 |
| `employees` | 직원 정보 + QR 토큰 (soft delete) |
| `qr_access_logs` | QR 스캔/검증 이력 |

## 보안

- 비밀번호: bcrypt 해시
- QR 토큰: 48자 랜덤, 재생성 가능
- CSRF: 모든 POST 요청 보호
- XSS: Blade `{{ }}` 자동 이스케이프
- 파일 업로드: MIME + 크기 검증
- QR 로그: IP, User-Agent 기록
