<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_check_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bank_config_id');
            $table->foreign('bank_config_id')->references('id')->on('bank_configs')->onDelete('cascade');
            $table->enum('status', ['success', 'failed', 'partial'])->default('success');
            $table->integer('transactions_found')->default(0);
            $table->integer('deposits_matched')->default(0);
            $table->text('error_message')->nullable();
            $table->jsonb('details')->default('{}');
            $table->integer('duration_ms')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['bank_config_id', 'status', 'started_at']);
            $table->index(['started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_check_logs');
    }
};
