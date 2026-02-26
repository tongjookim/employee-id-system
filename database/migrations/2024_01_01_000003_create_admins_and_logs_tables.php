<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 관리자 테이블
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('login_id', 50)->unique();
            $table->string('password');
            $table->string('name', 100);
            $table->string('email', 255)->nullable();
            $table->enum('role', ['super_admin', 'admin', 'viewer'])->default('admin');
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        // QR 코드 접근/스캔 로그
        Schema::create('qr_access_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('qr_token', 64);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->enum('access_type', ['scan', 'view', 'verify'])->default('view');
            $table->boolean('is_valid')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->index('qr_token');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_access_logs');
        Schema::dropIfExists('admins');
    }
};
