<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->nullable();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->string('sku', 100);
            $table->string('name', 255);
            $table->foreignUuid('size_option_id')->nullable();
            $table->foreign('size_option_id')->references('id')->on('size_options')->nullOnDelete();
            $table->string('color', 100)->nullable();
            $table->bigInteger('unit_price');
            $table->bigInteger('cost_price')->default(0);
            $table->bigInteger('discount_amount')->default(0);
            $table->foreignUuid('inventory_id')->nullable();
            $table->foreign('inventory_id')->references('id')->on('inventory')->nullOnDelete();
            $table->foreignUuid('shipment_id')->nullable();
            $table->foreign('shipment_id')->references('id')->on('shipments')->nullOnDelete();
            $table->enum('status', ['pending', 'picked', 'packed', 'shipped', 'delivered', 'returned', 'cancelled'])->default('pending');
            $table->timestamp('picked_at')->nullable();
            $table->foreignUuid('picked_by')->nullable();
            $table->foreign('picked_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('packed_at')->nullable();
            $table->foreignUuid('packed_by')->nullable();
            $table->foreign('packed_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('returned_at')->nullable();
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
            $table->jsonb('return_items');
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
