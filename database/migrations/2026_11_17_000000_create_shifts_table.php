<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();                         // người mở ca
            $table->foreignId('closed_by')->nullable()->constrained('users');    // người chốt ca
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('opening_cash');
            $table->unsignedBigInteger('counted_cash')->nullable();
            $table->unsignedBigInteger('expected_cash')->nullable();
            $table->string('note')->nullable();
            // 1 khi ca đang mở, NULL khi đã chốt: chỉ mục unique chặn hai ca mở cùng lúc (một shop = một két).
            $table->unsignedTinyInteger('open_lock')->nullable()->unique();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('cash_received')->nullable()->after('total'); // tiền khách đưa (POS tiền mặt)
            $table->foreign('shift_id')->references('id')->on('shifts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropColumn('cash_received');
        });

        Schema::dropIfExists('shifts');
    }
};
