<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('claim_line_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('claim_id')->constrained('order_claims')->cascadeOnDelete();
            $table->foreignUuid('order_item_id')->constrained('order_items')->cascadeOnDelete();

            $table->integer('quantity_claimed')->default(1);
            $table->enum('claim_reason', ['wrong_item', 'damaged', 'missing', 'quality_issue', 'other']);
            $table->enum('resolution_type', ['refund', 'replacement', 'partial_refund', 'rejected'])->nullable();
            $table->bigInteger('resolution_amount')->nullable(); // For partial refunds
            $table->uuid('replacement_product_id')->nullable(); // FK to products
            $table->foreign('replacement_product_id')->references('id')->on('products')->nullOnDelete();
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index('claim_id');
            $table->index('order_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_line_items');
    }
};
