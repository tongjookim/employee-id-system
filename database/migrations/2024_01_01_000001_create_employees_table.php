<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_number', 50)->unique()->comment('사번');
            $table->string('password')->comment('비밀번호 (bcrypt)');
            $table->string('name', 100)->comment('이름');
            $table->string('name_en', 100)->nullable()->comment('영문 이름');
            $table->string('department', 100)->nullable()->comment('부서');
            $table->string('position', 100)->nullable()->comment('직책');
            $table->string('rank', 100)->nullable()->comment('직급');
            $table->string('email', 255)->nullable()->comment('이메일');
            $table->string('phone', 20)->nullable()->comment('전화번호');
            $table->string('photo')->nullable()->comment('증명사진 경로');
            $table->date('hire_date')->nullable()->comment('입사일');
            $table->date('birth_date')->nullable()->comment('생년월일');
            $table->string('blood_type', 10)->nullable()->comment('혈액형');
            $table->text('address')->nullable()->comment('주소');
            $table->string('qr_token', 64)->unique()->comment('QR 고유 토큰');
            $table->timestamp('qr_generated_at')->nullable()->comment('QR 생성 시각');
            $table->unsignedBigInteger('design_template_id')->nullable()->comment('할당된 디자인 템플릿');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('department');
            $table->index('status');
            $table->foreign('design_template_id')->references('id')->on('design_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
