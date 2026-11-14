<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 10)->unique(); // luôn ở dạng 0xxxxxxxxx
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable(); // null = khách vãng lai, chưa có tài khoản
            $table->json('cart')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->unsignedBigInteger('total_spent')->default(0);
            $table->unsignedInteger('orders_count')->default(0);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
