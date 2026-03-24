<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add commission fields to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->string('referral_code', 20)->nullable()->after('token_expires_at');
            $table->uuid('sales_rep_id')->nullable()->after('referral_code');
            $table->foreign('sales_rep_id')->references('id')->on('users')->nullOnDelete();
            $table->bigInteger('commission_amount')->default(0)->after('sales_rep_id');
            $table->decimal('commission_rate', 5, 2)->default(0)->after('commission_amount');
            $table->enum('commission_status', ['pending', 'approved', 'paid', 'cancelled'])->default('pending')->after('commission_rate');
            $table->string('commission_paid_reference', 255)->nullable()->after('commission_status');
            $table->timestamp('commission_paid_at')->nullable()->after('commission_paid_reference');
            
            $table->index('referral_code');
            $table->index('sales_rep_id');
            $table->index('commission_status');
        });

        // Commission configuration table
        Schema::create('commission_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('staff_id');
            $table->foreign('staff_id')->references('id')->on('users')->cascadeOnDelete();
            $table->decimal('rate_percent', 5, 2)->default(5.00);
            $table->jsonb('category_rates')->default('{}'); // {"category_id": rate}
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('staff_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_configs');
        
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'referral_code',
                'sales_rep_id',
                'commission_amount',
                'commission_rate',
                'commission_status',
                'commission_paid_reference',
                'commission_paid_at',
            ]);
        });
    }
};
