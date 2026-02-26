#!/bin/bash
# ═══════════════════════════════════════════════
# 사원증 관리 시스템 - 자동 설치 스크립트
# ═══════════════════════════════════════════════
set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

export COMPOSER_ALLOW_SUPERUSER=1

echo -e "${GREEN}═══════════════════════════════════════════════${NC}"
echo -e "${GREEN}  사원증 관리 시스템 설치${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════${NC}"
echo ""

# 1) PHP
echo -e "${YELLOW}[1/8] PHP 버전 확인...${NC}"
php -v || { echo -e "${RED}PHP 8.2 이상을 설치해주세요.${NC}"; exit 1; }
echo ""

# 2) Composer
echo -e "${YELLOW}[2/8] Composer 확인...${NC}"
composer --version || { echo -e "${RED}Composer를 설치해주세요.${NC}"; exit 1; }
echo ""

# 3) 캐시 정리
echo -e "${YELLOW}[3/8] 이전 캐시 정리...${NC}"
rm -rf bootstrap/cache/*.php
rm -rf vendor
rm -f composer.lock
echo "  캐시 및 vendor 정리 완료"
echo ""

# 4) 의존성 설치 (no-scripts로 부트 오류 방지)
echo -e "${YELLOW}[4/8] Composer 패키지 설치...${NC}"
composer install --no-scripts --no-interaction --prefer-dist
composer dump-autoload --no-scripts
echo ""

# 5) 환경설정
echo -e "${YELLOW}[5/8] 환경 설정...${NC}"
if [ ! -f .env ]; then
    cp .env.example .env
    echo "  .env 파일 생성됨"
fi

# artisan 테스트
echo -n "  artisan 테스트: "
php artisan --version && echo "" || { echo -e "${RED}artisan 실행 실패${NC}"; exit 1; }

php artisan key:generate --force
echo ""

# 6) 스토리지
echo -e "${YELLOW}[6/8] 스토리지 설정...${NC}"
mkdir -p storage/app/public/templates
mkdir -p storage/app/public/photos
mkdir -p storage/app/fonts
mkdir -p storage/framework/{cache/data,sessions,views}
mkdir -p storage/logs
mkdir -p bootstrap/cache

php artisan storage:link --force 2>/dev/null || true

if [ -f public/uploads/templates/default_bg.png ]; then
    cp public/uploads/templates/default_bg.png storage/app/public/templates/default_bg.png
    echo "  기본 배경 이미지 복사됨"
fi
echo ""

# 7) DB
echo -e "${YELLOW}[7/8] 데이터베이스 설정...${NC}"
echo "  .env 파일에서 DB 접속 정보를 확인해주세요."
echo "    DB_HOST=127.0.0.1"
echo "    DB_DATABASE=employee_id_system"
echo "    DB_USERNAME=root"
echo "    DB_PASSWORD="
echo ""
read -p "  DB 설정 완료 후 Enter를 눌러주세요... "
echo ""
echo "  마이그레이션 실행 중..."
php artisan migrate --force
echo "  시드 데이터 삽입 중..."
php artisan db:seed --force
echo ""

# 8) 권한
echo -e "${YELLOW}[8/8] 권한 설정...${NC}"
chmod -R 775 storage bootstrap/cache
echo ""

echo -e "${GREEN}═══════════════════════════════════════════════${NC}"
echo -e "${GREEN}  설치 완료!${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════${NC}"
echo ""
echo -e "  개발 서버:  ${YELLOW}php artisan serve --host=0.0.0.0${NC}"
echo -e "  접속 주소:  ${YELLOW}http://서버IP:8000${NC}"
echo ""
echo -e "  관리자:     ${YELLOW}http://서버IP:8000/admin/login${NC}"
echo -e "              admin / admin1234!"
echo ""
echo -e "  모바일:     ${YELLOW}http://서버IP:8000/user/login${NC}"
echo ""
echo -e "  Nginx 배포: nginx.conf 참고"
echo ""
