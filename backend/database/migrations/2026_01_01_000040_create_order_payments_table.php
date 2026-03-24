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
            $table->uuid('order_id');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');

            $table->bigInteger('amount');
            $table->enum('payment_method', ['cod', 'bank_transfer', 'momo', 'zalopay', 'vnpay', 'credit_card', 'cash', 'loyalty_points']);
            $table->enum('payment_type', ['deposit', 'final', 'cod_collection', 'refund', 'adjustment']);
            $table->string('reference', 255)->nullable();
            $table->jsonb('proof_urls')->default('[]');
            $table->text('notes')->nullable();

            $table->uuid('recorded_by')->nullable();
            $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();

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
