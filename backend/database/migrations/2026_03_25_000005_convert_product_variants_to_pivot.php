<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('product_variants', 'product_variant_links');

        Schema::table('product_variant_links', function (Blueprint $table) {
            // Rename product_id to parent_product_id for clarity
            $table->renameColumn('product_id', 'parent_product_id');

            // Add variant_product_id for the many-to-many relationship
            $table->foreignUuid('variant_product_id')->after('parent_product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            // Add relationship type
            $table->string('relationship_type', 20)->default('color')->after('variant_product_id');

            // Remove old columns that are now on products table
            $table->dropColumn([
                'sku',
                'barcode',
                'attribute_values',
                'compare_price',
                'cost_price',
                'selling_price',
                'sale_price',
                'weight_grams',
                'sale_event_id',
            ]);

            // Remove status column (now on products)
            $table->dropColumn('status');

            // Add unique constraint
            $table->unique(['parent_product_id', 'variant_product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('product_variant_links', function (Blueprint $table) {
            $table->dropUnique(['parent_product_id', 'variant_product_id']);
            $table->dropColumn(['variant_product_id', 'relationship_type']);
            $table->renameColumn('parent_product_id', 'product_id');

            // Restore old columns
            $table->string('sku', 100)->unique();
            $table->string('barcode', 100)->unique()->nullable();
            $table->jsonb('attribute_values')->default('{}');
            $table->bigInteger('compare_price')->default(0);
            $table->bigInteger('cost_price')->default(0);
            $table->bigInteger('selling_price')->default(0);
            $table->bigInteger('sale_price')->nullable();
            $table->uuid('sale_event_id')->nullable();
            $table->integer('weight_grams')->default(200);
            $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active');
        });

        Schema::rename('product_variant_links', 'product_variants');
    }
};
