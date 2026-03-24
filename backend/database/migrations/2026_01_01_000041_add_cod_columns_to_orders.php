<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // COD tracking columns
            $table->bigInteger('cod_expected_amount')->nullable()->after('deposit_proof_urls');
            $table->bigInteger('cod_collected_amount')->nullable()->after('cod_expected_amount');
            $table->timestamp('cod_collected_at')->nullable()->after('cod_collected_amount');
            $table->string('cod_collection_reference', 255)->nullable()->after('cod_collected_at');
            $table->enum('cod_reconciliation_status', ['pending', 'matched', 'disputed', 'waived'])
                ->default('pending')
                ->after('cod_collection_reference');
            $table->bigInteger('cod_discrepancy_amount')->nullable()->after('cod_reconciliation_status');

            // Additional payment tracking
            $table->bigInteger('deposit_amount')->default(0)->change();

            // Indexes
            $table->index(['payment_method', 'payment_status']);
            $table->index(['cod_reconciliation_status', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'cod_expected_amount',
                'cod_collected_amount',
                'cod_collected_at',
                'cod_collection_reference',
                'cod_reconciliation_status',
                'cod_discrepancy_amount',
            ]);
            $table->dropIndex(['payment_method', 'payment_status']);
            $table->dropIndex(['cod_reconciliation_status', 'status']);
        });
    }
};
