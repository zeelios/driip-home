<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('brand_id')->nullable();
            $table->foreign('brand_id')->references('id')->on('brands')->nullOnDelete();
            $table->uuid('category_id')->nullable();
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->string('sku', 100)->unique()->nullable();
            $table->string('barcode', 100)->unique()->nullable();
            $table->bigInteger('compare_price')->default(0);
            $table->bigInteger('cost_price')->default(0);
            $table->bigInteger('selling_price')->default(0);
            $table->bigInteger('sale_price')->nullable();
            $table->integer('weight_grams')->default(200);
            $table->uuid('sale_event_id')->nullable();
            $table->text('description')->nullable();
            $table->string('short_description', 500)->nullable();
            $table->string('sku_base', 50)->nullable();
            $table->jsonb('attribute_values')->default('{}');
            $table->enum('variant_status', ['active', 'inactive', 'discontinued'])->default('active');
            $table->enum('gender', ['men', 'women', 'unisex', 'kids'])->nullable();
            $table->string('season', 20)->nullable(); // SS26, FW25, ALL
            $table->jsonb('tags')->default('[]');
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
