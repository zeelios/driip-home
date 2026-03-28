<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_variant_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('variant_product_id')->constrained('products')->cascadeOnDelete();
            $table->string('relationship_type', 20)->default('color');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['parent_product_id', 'variant_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_links');
    }
};
