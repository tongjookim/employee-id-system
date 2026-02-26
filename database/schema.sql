-- ============================================
-- 사원증 관리시스템 DB 스키마 (MySQL)
-- Nginx + MySQL 환경
-- ============================================

CREATE DATABASE IF NOT EXISTS `employee_id_system`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `employee_id_system`;

-- 관리자 테이블
CREATE TABLE `admins` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `login_id` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NULL,
    `role` ENUM('super_admin', 'admin', 'viewer') NOT NULL DEFAULT 'admin',
    `last_login_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 디자인 템플릿 테이블
CREATE TABLE `design_templates` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT '템플릿 이름',
    `background_image` VARCHAR(255) NOT NULL COMMENT '배경 이미지 경로',
    `canvas_width` INT UNSIGNED NOT NULL DEFAULT 640 COMMENT '캔버스 너비(px)',
    `canvas_height` INT UNSIGNED NOT NULL DEFAULT 1010 COMMENT '캔버스 높이(px)',
    `is_default` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '기본 템플릿 여부',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성 여부',
    `description` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 직원 테이블
CREATE TABLE `employees` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `employee_number` VARCHAR(50) NOT NULL UNIQUE COMMENT '사번',
    `password` VARCHAR(255) NOT NULL COMMENT '비밀번호(bcrypt)',
    `name` VARCHAR(100) NOT NULL COMMENT '이름',
    `name_en` VARCHAR(100) NULL COMMENT '영문 이름',
    `department` VARCHAR(100) NULL COMMENT '부서',
    `position` VARCHAR(100) NULL COMMENT '직책',
    `rank` VARCHAR(100) NULL COMMENT '직급',
    `email` VARCHAR(255) NULL COMMENT '이메일',
    `phone` VARCHAR(20) NULL COMMENT '전화번호',
    `photo` VARCHAR(255) NULL COMMENT '증명사진 경로',
    `hire_date` DATE NULL COMMENT '입사일',
    `birth_date` DATE NULL COMMENT '생년월일',
    `blood_type` VARCHAR(10) NULL COMMENT '혈액형',
    `address` TEXT NULL COMMENT '주소',
    `qr_token` VARCHAR(64) NOT NULL UNIQUE COMMENT 'QR 고유 토큰',
    `qr_generated_at` TIMESTAMP NULL COMMENT 'QR 생성 시각',
    `design_template_id` BIGINT UNSIGNED NULL COMMENT '할당된 디자인 템플릿',
    `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,

    INDEX `idx_department` (`department`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`design_template_id`) REFERENCES `design_templates`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 필드 좌표 매핑 테이블
CREATE TABLE `field_mappings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `design_template_id` BIGINT UNSIGNED NOT NULL,
    `field_key` VARCHAR(50) NOT NULL COMMENT '필드 키',
    `label` VARCHAR(100) NOT NULL COMMENT '표시 라벨',
    `field_type` ENUM('text', 'image', 'qr_code') NOT NULL DEFAULT 'text',
    `pos_x` INT NOT NULL DEFAULT 0 COMMENT 'X 좌표(px)',
    `pos_y` INT NOT NULL DEFAULT 0 COMMENT 'Y 좌표(px)',
    `width` INT UNSIGNED NULL COMMENT '너비(px)',
    `height` INT UNSIGNED NULL COMMENT '높이(px)',
    `font_size` INT UNSIGNED NOT NULL DEFAULT 16 COMMENT '폰트 크기',
    `font_color` VARCHAR(7) NOT NULL DEFAULT '#000000' COMMENT '폰트 색상',
    `font_family` VARCHAR(100) NOT NULL DEFAULT 'NanumGothic' COMMENT '폰트',
    `text_align` ENUM('left', 'center', 'right') NOT NULL DEFAULT 'left',
    `is_bold` TINYINT(1) NOT NULL DEFAULT 0,
    `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,

    FOREIGN KEY (`design_template_id`) REFERENCES `design_templates`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uniq_template_field` (`design_template_id`, `field_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- QR 접근 로그 테이블
CREATE TABLE `qr_access_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `employee_id` BIGINT UNSIGNED NOT NULL,
    `qr_token` VARCHAR(64) NOT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `access_type` ENUM('scan', 'view', 'verify') NOT NULL DEFAULT 'view',
    `is_valid` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
    INDEX `idx_qr_token` (`qr_token`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Laravel 세션 테이블 (세션 드라이버를 database로 사용 시)
CREATE TABLE `sessions` (
    `id` VARCHAR(255) NOT NULL PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `payload` LONGTEXT NOT NULL,
    `last_activity` INT NOT NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 초기 데이터 삽입
-- ============================================

-- 기본 관리자 (비밀번호: admin1234!)
INSERT INTO `admins` (`login_id`, `password`, `name`, `email`, `role`, `created_at`, `updated_at`)
VALUES ('admin', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '시스템관리자', 'admin@company.com', 'super_admin', NOW(), NOW());
-- 참고: 위 해시는 'password' 해시입니다. 실제 배포 시 artisan tinker로 Hash::make('admin1234!') 실행 후 교체하세요.

-- 기본 디자인 템플릿
INSERT INTO `design_templates` (`name`, `background_image`, `canvas_width`, `canvas_height`, `is_default`, `is_active`, `description`, `created_at`, `updated_at`)
VALUES ('기본 사원증 템플릿', 'templates/default_bg.png', 640, 1010, 1, 1, '기본 제공 사원증 디자인', NOW(), NOW());

SET @tpl_id = LAST_INSERT_ID();

INSERT INTO `field_mappings` (`design_template_id`, `field_key`, `label`, `field_type`, `pos_x`, `pos_y`, `width`, `height`, `font_size`, `font_color`, `text_align`, `is_bold`, `is_visible`, `sort_order`, `created_at`, `updated_at`) VALUES
(@tpl_id, 'photo',           '증명사진',  'image',   220, 180, 200, 260, 0,  '#333333', 'center', 0, 1, 1, NOW(), NOW()),
(@tpl_id, 'name',            '이름',     'text',    320, 480, NULL, NULL, 28, '#333333', 'center', 1, 1, 2, NOW(), NOW()),
(@tpl_id, 'name_en',         '영문이름',  'text',    320, 520, NULL, NULL, 16, '#666666', 'center', 0, 1, 3, NOW(), NOW()),
(@tpl_id, 'department',      '부서',     'text',    320, 560, NULL, NULL, 18, '#333333', 'center', 0, 1, 4, NOW(), NOW()),
(@tpl_id, 'position',        '직책',     'text',    320, 595, NULL, NULL, 18, '#333333', 'center', 0, 1, 5, NOW(), NOW()),
(@tpl_id, 'employee_number', '사번',     'text',    320, 630, NULL, NULL, 14, '#999999', 'center', 0, 1, 6, NOW(), NOW()),
(@tpl_id, 'qr_code',         'QR코드',   'qr_code', 230, 700, 180, 180, 0,  '#333333', 'center', 0, 1, 7, NOW(), NOW());
