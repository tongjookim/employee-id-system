<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('design_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('템플릿 이름');
            $table->string('background_image')->comment('배경 이미지 경로');
            $table->unsignedInteger('canvas_width')->default(640)->comment('캔버스 너비(px)');
            $table->unsignedInteger('canvas_height')->default(1010)->comment('캔버스 높이(px)');
            $table->boolean('is_default')->default(false)->comment('기본 템플릿 여부');
            $table->boolean('is_active')->default(true)->comment('활성 여부');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_templates');
    }
};
