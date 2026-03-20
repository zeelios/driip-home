<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('transfer_number', 30)->unique();
            $table->uuid('from_warehouse_id');
            $table->foreign('from_warehouse_id')->references('id')->on('warehouses')->restrictOnDelete();
            $table->uuid('to_warehouse_id');
            $table->foreign('to_warehouse_id')->references('id')->on('warehouses')->restrictOnDelete();
            $table->enum('status', ['draft', 'approved', 'dispatched', 'in_transit', 'received', 'cancelled'])->default('draft');
            $table->text('reason')->nullable();
            $table->uuid('requested_by');
            $table->foreign('requested_by')->references('id')->on('users')->restrictOnDelete();
            $table->uuid('approved_by')->nullable();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_variant_id')->constrained()->restrictOnDelete();
            $table->integer('quantity_requested');
            $table->integer('quantity_dispatched')->nullable();
            $table->integer('quantity_received')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
    }
};
