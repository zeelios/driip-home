<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 100)->unique();
            $table->string('barcode', 100)->unique()->nullable();
            $table->jsonb('attribute_values')->default('{}'); // {size_id: uuid, color_id: uuid}
            $table->bigInteger('compare_price')->default(0); // MSRP (VND)
            $table->bigInteger('cost_price')->default(0);
            $table->bigInteger('selling_price')->default(0);
            $table->bigInteger('sale_price')->nullable(); // live override during flash sale
            $table->uuid('sale_event_id')->nullable(); // FK added later after sale_events table
            $table->integer('weight_grams')->default(200);
            $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
