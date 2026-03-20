<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->enum('type', ['percent', 'fixed_amount', 'free_shipping']);
            $table->decimal('value', 10, 2); // percent (20.0) or VND amount
            $table->bigInteger('min_order_amount')->nullable();
            $table->integer('min_items')->nullable();
            $table->bigInteger('max_discount_amount')->nullable();
            $table->enum('applies_to', ['all', 'category', 'product', 'brand'])->default('all');
            $table->jsonb('applies_to_ids')->default('[]');
            $table->integer('max_uses')->nullable();
            $table->integer('max_uses_per_customer')->default(1);
            $table->integer('used_count')->default(0);
            $table->boolean('is_public')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('coupon_id')->constrained()->cascadeOnDelete();
            $table->uuid('customer_id')->nullable();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->uuid('order_id'); // FK added after orders table
            $table->bigInteger('discount_amount')->default(0);
            $table->timestamp('used_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupons');
    }
};
