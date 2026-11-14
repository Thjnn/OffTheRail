<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('channel', 10);            // web | pos | shopee
            $table->string('external_code')->nullable(); // mã đơn Shopee
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone', 10)->nullable()->index();
            $table->string('customer_email')->nullable();
            $table->json('address')->nullable();
            $table->string('status', 12)->index();
            $table->string('payment_method', 16);     // cod | bank_transfer | cash | qr
            $table->string('payment_status', 10)->default('unpaid'); // unpaid | paid | refunded
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('shipping_fee')->default(0);
            $table->unsignedBigInteger('total');
            $table->foreignId('staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('shift_id')->nullable(); // bảng shifts có ở Kế hoạch 4
            $table->string('tracking_code')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['channel', 'external_code']);
            $table->index('created_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            // Chụp lại thông tin lúc bán: sản phẩm có đổi tên/xoá thì đơn cũ vẫn đúng.
            $table->string('name');
            $table->string('color')->nullable();
            $table->string('size');
            $table->string('sku');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedInteger('qty');
        });

        Schema::create('order_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from', 12)->nullable();
            $table->string('to', 12);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->integer('change');
            $table->string('reason', 10); // sale | cancel | return | adjust | import
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('order_status_logs');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
