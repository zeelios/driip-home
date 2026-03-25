<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_deposits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->bigInteger('expected_amount');
            $table->integer('amount_tolerance')->default(0);
            $table->string('transfer_content_pattern', 255);
            $table->uuid('bank_config_id')->nullable();
            $table->foreign('bank_config_id')->references('id')->on('bank_configs')->nullOnDelete();
            $table->enum('status', ['pending', 'matched', 'expired', 'cancelled'])->default('pending');
            $table->timestamp('expires_at');
            $table->string('matched_transaction_id', 255)->nullable();
            $table->timestamp('matched_at')->nullable();
            $table->uuid('matched_by')->nullable();
            $table->foreign('matched_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'expires_at']);
            $table->index(['order_id', 'status']);
            $table->index(['bank_config_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_deposits');
    }
};
