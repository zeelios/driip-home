<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update inventory table
        Schema::table('inventory', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
            $table->dropUnique(['product_variant_id', 'warehouse_id']);
            $table->renameColumn('product_variant_id', 'product_id');
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->unique(['product_id', 'warehouse_id']);
        });

        // Update inventory_transactions table
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
            $table->dropIndex(['product_variant_id', 'warehouse_id']);
            $table->renameColumn('product_variant_id', 'product_id');
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->index(['product_id', 'warehouse_id']);
        });
    }

    public function down(): void
    {
        // Revert inventory table
        Schema::table('inventory', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropUnique(['product_id', 'warehouse_id']);
            $table->renameColumn('product_id', 'product_variant_id');
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->cascadeOnDelete();
            $table->unique(['product_variant_id', 'warehouse_id']);
        });

        // Revert inventory_transactions table
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropIndex(['product_id', 'warehouse_id']);
            $table->renameColumn('product_id', 'product_variant_id');
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->cascadeOnDelete();
            $table->index(['product_variant_id', 'warehouse_id']);
        });
    }
};
