<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_counts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('count_number', 30)->unique();
            $table->foreignUuid('warehouse_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['full', 'partial', 'cycle_count', 'spot_check']);
            $table->enum('status', ['draft', 'in_progress', 'completed', 'approved', 'rejected'])->default('draft');
            $table->date('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->integer('total_variance_qty')->nullable();
            $table->bigInteger('total_variance_value')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by');
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('stock_count_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_count_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_variant_id')->constrained()->restrictOnDelete();
            $table->integer('quantity_expected');
            $table->integer('quantity_counted')->nullable();
            $table->integer('variance')->nullable(); // counted - expected
            $table->bigInteger('variance_value')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('counted_by')->nullable();
            $table->foreign('counted_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('counted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_count_items');
        Schema::dropIfExists('stock_counts');
    }
};
