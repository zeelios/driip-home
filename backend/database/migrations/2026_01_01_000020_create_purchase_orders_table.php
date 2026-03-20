<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('po_number', 30)->unique();
            $table->foreignUuid('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('warehouse_id')->constrained()->restrictOnDelete();
            $table->enum('status', [
                'draft',
                'sent',
                'confirmed',
                'partial_received',
                'fully_received',
                'cancelled',
            ])->default('draft');
            $table->date('expected_arrival_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->bigInteger('shipping_cost')->default(0);
            $table->bigInteger('other_costs')->default(0);
            $table->bigInteger('total_cost')->default(0);
            $table->text('notes')->nullable();
            $table->uuid('created_by');
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
            $table->uuid('approved_by')->nullable();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_variant_id')->constrained()->restrictOnDelete();
            $table->string('sku', 100); // snapshot
            $table->integer('quantity_ordered');
            $table->integer('quantity_received')->default(0);
            $table->bigInteger('unit_cost');
            $table->bigInteger('total_cost');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_order_receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->string('receipt_number', 30)->unique();
            $table->uuid('received_by');
            $table->foreign('received_by')->references('id')->on('users')->restrictOnDelete();
            $table->timestamp('received_at');
            $table->text('notes')->nullable();
            $table->jsonb('attachments')->default('[]');
            $table->jsonb('receipt_items'); // [{po_item_id, qty_received, condition, notes}]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_receipts');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
