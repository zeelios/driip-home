<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('courier_code', 20)->unique();
            $table->string('name', 100);
            $table->string('api_endpoint', 500)->nullable();
            $table->text('api_key')->nullable();
            $table->text('api_secret')->nullable();
            $table->string('account_id', 100)->nullable();
            $table->string('pickup_hub_code', 50)->nullable();
            $table->jsonb('pickup_address')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->boolean('is_active')->default(true);
            $table->jsonb('settings')->default('{}');
            $table->timestamps();
        });

        Schema::create('courier_cod_remittances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('courier_code', 20);
            $table->string('remittance_reference', 100)->nullable();
            $table->date('period_from');
            $table->date('period_to');
            $table->bigInteger('total_cod_collected')->default(0);
            $table->bigInteger('total_fees_deducted')->default(0);
            $table->bigInteger('net_remittance')->default(0);
            $table->enum('status', ['pending', 'received', 'partial', 'disputed', 'reconciled'])->default('pending');
            $table->timestamp('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('courier_cod_remittance_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('remittance_id')->constrained('courier_cod_remittances')->cascadeOnDelete();
            $table->foreignUuid('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('cod_amount')->default(0);
            $table->bigInteger('shipping_fee')->default(0);
            $table->bigInteger('other_fees')->default(0);
            $table->bigInteger('net_amount')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_cod_remittance_items');
        Schema::dropIfExists('courier_cod_remittances');
        Schema::dropIfExists('courier_configs');
    }
};
