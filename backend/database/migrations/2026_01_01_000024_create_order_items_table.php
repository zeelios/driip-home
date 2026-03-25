<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->uuid('product_id')->nullable();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->uuid('product_variant_id')->nullable();
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->string('sku', 100);
            $table->string('name', 255);
            $table->string('size', 50)->nullable();
            $table->string('color', 100)->nullable();
            $table->bigInteger('unit_price');
            $table->bigInteger('cost_price')->default(0);
            $table->integer('quantity');
            $table->integer('quantity_returned')->default(0);
            $table->bigInteger('discount_amount')->default(0);
            $table->bigInteger('total_price');
            $table->timestamps();
        });

        Schema::create('order_status_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50);
            $table->text('note')->nullable();
            $table->boolean('is_customer_visible')->default(false);
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index('order_id');
        });

        Schema::create('order_claims', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('claim_number', 30)->unique();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->uuid('order_item_id')->nullable();
            $table->foreign('order_item_id')->references('id')->on('order_items')->nullOnDelete();
            $table->enum('type', ['wrong_item', 'damaged', 'missing_item', 'late_delivery', 'quality_issue', 'other']);
            $table->enum('status', ['open', 'investigating', 'awaiting_return', 'resolved', 'rejected', 'closed'])->default('open');
            $table->text('description');
            $table->jsonb('evidence_urls')->default('[]');
            $table->enum('resolution', ['refund', 'replacement', 'voucher', 'apology', 'rejected'])->nullable();
            $table->text('resolution_notes')->nullable();
            $table->bigInteger('refund_amount')->nullable();
            $table->uuid('assigned_to')->nullable();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $table->boolean('created_by_customer')->default(false);
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('return_number', 30)->unique();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->uuid('claim_id')->nullable();
            $table->foreign('claim_id')->references('id')->on('order_claims')->nullOnDelete();
            $table->enum('status', ['requested', 'approved', 'in_transit', 'received', 'inspected', 'processed', 'rejected'])->default('requested');
            $table->jsonb('return_items'); // [{order_item_id, qty, reason, condition, refund_amount}]
            $table->string('return_courier', 50)->nullable();
            $table->string('return_tracking', 100)->nullable();
            $table->bigInteger('total_refund')->nullable();
            $table->string('refund_method', 50)->nullable();
            $table->string('refund_reference', 255)->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->uuid('processed_by')->nullable();
            $table->foreign('processed_by')->references('id')->on('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_returns');
        Schema::dropIfExists('order_claims');
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('order_items');
    }
};
