<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Deposit tracking
            $table->bigInteger('deposit_amount')->default(0)->after('payment_status');
            $table->timestamp('deposit_paid_at')->nullable()->after('deposit_amount');
            $table->jsonb('deposit_proof_urls')->default('[]')->after('deposit_paid_at');
            $table->text('payment_notes')->nullable()->after('deposit_proof_urls');

            // Public token for customer self-service portal
            $table->string('public_token', 64)->unique()->nullable()->after('cancellation_reason');
            $table->timestamp('token_expires_at')->nullable()->after('public_token');
        });

        // Index for token lookups
        Schema::table('orders', function (Blueprint $table) {
            $table->index('public_token');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'deposit_amount',
                'deposit_paid_at',
                'deposit_proof_urls',
                'payment_notes',
                'public_token',
                'token_expires_at',
            ]);
        });
    }
};
