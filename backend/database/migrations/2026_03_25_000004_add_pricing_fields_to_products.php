<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sku', 100)->unique()->nullable()->after('slug');
            $table->string('barcode', 100)->unique()->nullable()->after('sku');
            $table->bigInteger('compare_price')->default(0)->after('barcode');
            $table->bigInteger('cost_price')->default(0)->after('compare_price');
            $table->bigInteger('selling_price')->default(0)->after('cost_price');
            $table->bigInteger('sale_price')->nullable()->after('selling_price');
            $table->integer('weight_grams')->default(200)->after('sale_price');
            $table->uuid('sale_event_id')->nullable()->after('weight_grams');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'sku',
                'barcode',
                'compare_price',
                'cost_price',
                'selling_price',
                'sale_price',
                'weight_grams',
                'sale_event_id',
            ]);
        });
    }
};
