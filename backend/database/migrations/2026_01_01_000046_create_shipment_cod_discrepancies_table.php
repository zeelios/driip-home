<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_cod_discrepancies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('shipment_id');
            $table->foreign('shipment_id')->references('id')->on('shipments')->onDelete('cascade');
            $table->uuid('order_id');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->string('courier_code');
            $table->string('tracking_number');
            $table->integer('cod_amount');
            $table->string('discrepancy_type'); // 'cod_not_remittance', 'status_mismatch', 'amount_mismatch'
            $table->string('status')->default('open'); // open, investigating, resolved, dismissed
            $table->text('description');
            $table->text('courier_claim')->nullable(); // What courier says
            $table->text('internal_record')->nullable(); // What we have recorded
            $table->text('resolution_notes')->nullable();
            $table->uuid('resolved_by')->nullable();
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('detected_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'courier_code']);
            $table->index(['shipment_id']);
            $table->index(['order_id']);
            $table->index(['detected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_cod_discrepancies');
    }
};
