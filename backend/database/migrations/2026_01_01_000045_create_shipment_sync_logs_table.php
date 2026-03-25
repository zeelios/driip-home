<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_sync_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('sync_type', ['batch', 'single'])->default('single');
            $table->string('courier_code')->nullable();
            $table->uuid('shipment_id')->nullable();
            $table->foreign('shipment_id')->references('id')->on('shipments')->onDelete('set null');
            $table->integer('shipments_processed')->default(0);
            $table->integer('shipments_failed')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['sync_type', 'courier_code', 'started_at']);
            $table->index(['shipment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_sync_logs');
    }
};
