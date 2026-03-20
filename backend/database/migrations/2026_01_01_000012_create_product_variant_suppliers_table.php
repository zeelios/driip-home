<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_suppliers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_variant_id')->constrained()->cascadeOnDelete();
            $table->uuid('supplier_id'); // FK added later after suppliers table
            $table->string('supplier_sku', 100)->nullable();
            $table->integer('lead_time_days')->nullable();
            $table->bigInteger('cost_price')->default(0);
            $table->boolean('is_preferred')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_suppliers');
    }
};
