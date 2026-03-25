<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();

            $table->bigInteger('amount');
            $table->enum('payment_method', ['cod', 'bank_transfer', 'momo', 'zalopay', 'vnpay', 'credit_card', 'cash', 'loyalty_points']);
            $table->enum('payment_type', ['deposit', 'final', 'cod_collection', 'refund', 'adjustment']);
            $table->string('reference', 255)->nullable();
            $table->jsonb('proof_urls')->default('[]');
            $table->text('notes')->nullable();

            $table->foreignUuid('recorded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['order_id', 'payment_type', 'created_at']);
            $table->index(['payment_method', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
};
