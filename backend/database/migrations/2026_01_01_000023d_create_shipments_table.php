<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->string('courier_code', 20); // ghtk, ghn, spx, viettel
            $table->string('tracking_number', 100)->nullable();
            $table->string('internal_reference', 100)->nullable();
            $table->enum('status', [
                'draft',
                'created',
                'pickup_scheduled',
                'picked_up',
                'in_transit',
                'out_for_delivery',
                'delivered',
                'failed_delivery',
                'returning',
                'returned',
                'cancelled',
            ])->default('draft');
            $table->string('label_url', 500)->nullable();
            $table->string('label_reference', 255)->nullable();
            $table->jsonb('label_payload')->nullable();
            $table->timestamp('label_printed_at')->nullable();
            $table->bigInteger('cod_amount')->default(0);
            $table->boolean('cod_collected')->default(false);
            $table->bigInteger('shipping_fee_quoted')->nullable();
            $table->bigInteger('shipping_fee_actual')->nullable();
            $table->decimal('weight_kg', 8, 3)->nullable();
            $table->date('estimated_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->integer('failed_attempts')->default(0);
            $table->jsonb('courier_request')->nullable();
            $table->jsonb('courier_response')->nullable();
            $table->uuid('created_by');
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('order_id');
            $table->index('tracking_number');
            $table->index('status');
        });

        Schema::create('shipment_tracking_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('status', 100);
            $table->string('courier_status_code', 50)->nullable();
            $table->text('message');
            $table->string('location', 255)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('synced_at');
            $table->jsonb('raw_data')->nullable();

            $table->index('shipment_id');
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_tracking_events');
        Schema::dropIfExists('shipments');
    }
};
