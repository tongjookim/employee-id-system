<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 디자인 템플릿 위 필드 좌표 매핑
        Schema::create('field_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('design_template_id');
            $table->string('field_key', 50)->comment('필드 키 (name, position, department, photo, qr_code 등)');
            $table->string('label', 100)->comment('표시 라벨');
            $table->enum('field_type', ['text', 'image', 'qr_code'])->default('text');
            $table->integer('pos_x')->default(0)->comment('X 좌표 (px)');
            $table->integer('pos_y')->default(0)->comment('Y 좌표 (px)');
            $table->unsignedInteger('width')->nullable()->comment('너비 (px) - 이미지/QR용');
            $table->unsignedInteger('height')->nullable()->comment('높이 (px) - 이미지/QR용');
            $table->unsignedInteger('font_size')->default(16)->comment('폰트 크기');
            $table->string('font_color', 7)->default('#000000')->comment('폰트 색상');
            $table->string('font_family', 100)->default('NanumGothic')->comment('폰트');
            $table->enum('text_align', ['left', 'center', 'right'])->default('left');
            $table->boolean('is_bold')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('design_template_id')->references('id')->on('design_templates')->cascadeOnDelete();
            $table->unique(['design_template_id', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_mappings');
    }
};
