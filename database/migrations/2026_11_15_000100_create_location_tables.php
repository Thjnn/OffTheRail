<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Đơn vị hành chính 2 cấp (từ 01/07/2025): Tỉnh/Thành → Phường/Xã.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->string('code', 5)->primary();
            $table->string('name');
        });

        Schema::create('wards', function (Blueprint $table) {
            $table->string('code', 8)->primary();
            $table->string('province_code', 5)->index();
            $table->string('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wards');
        Schema::dropIfExists('provinces');
    }
};
