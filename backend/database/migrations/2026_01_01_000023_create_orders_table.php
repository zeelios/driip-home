<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_number', 30)->unique();
            $table->foreignUuid('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_name', 255)->nullable();
            $table->string('guest_email', 255)->nullable();
            $table->string('guest_phone', 20)->nullable();

            $table->enum('status', [
                'pending',
                'confirmed',
                'processing',
                'packed',
                'handed_to_courier',
                'shipped',
                'in_transit',
                'out_for_delivery',
                'delivered',
                'failed_delivery',
                'returned',
                'return_processing',
                'return_completed',
                'cancelled',
                'refunded',
                'on_hold',
                'disputed',
            ])->default('pending');

            $table->enum('payment_status', ['unpaid', 'paid', 'partial', 'refunded', 'failed', 'void'])->default('unpaid');
            $table->enum('payment_method', ['cod', 'bank_transfer', 'momo', 'zalopay', 'vnpay', 'credit_card', 'cash', 'loyalty_points'])->nullable();
            $table->bigInteger('deposit_amount')->default(0);
            $table->timestamp('deposit_paid_at')->nullable();
            $table->jsonb('deposit_proof_urls')->default('[]');
            $table->text('payment_notes')->nullable();
            $table->string('payment_reference', 255)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->bigInteger('cod_expected_amount')->nullable();
            $table->bigInteger('cod_collected_amount')->nullable();
            $table->timestamp('cod_collected_at')->nullable();
            $table->string('cod_collection_reference', 255)->nullable();
            $table->enum('cod_reconciliation_status', ['pending', 'matched', 'disputed', 'waived'])->default('pending');
            $table->bigInteger('cod_discrepancy_amount')->nullable();

            $table->bigInteger('subtotal')->default(0);
            $table->foreignUuid('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('coupon_code', 50)->nullable();
            $table->bigInteger('coupon_discount')->default(0);
            $table->integer('loyalty_points_used')->default(0);
            $table->bigInteger('loyalty_discount')->default(0);
            $table->bigInteger('shipping_fee')->default(0);

            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->bigInteger('vat_amount')->default(0);
            $table->bigInteger('total_before_tax')->default(0);
            $table->bigInteger('total_after_tax')->default(0); // actual final total
            $table->string('tax_code', 20)->nullable(); // buyer tax code for B2B invoices

            $table->bigInteger('cost_total')->default(0); // COGS for profit calculation

            $table->string('shipping_name', 255);
            $table->string('shipping_phone', 20);
            $table->string('shipping_province', 100);
            $table->string('shipping_district', 100)->nullable();
            $table->string('shipping_ward', 100)->nullable();
            $table->text('shipping_address');
            $table->string('shipping_zip', 10)->nullable();

            $table->text('notes')->nullable(); // customer notes
            $table->text('internal_notes')->nullable(); // staff only
            $table->jsonb('tags')->default('[]');
            $table->string('source', 50)->nullable(); // web, facebook, instagram, zalo, manual, admin
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();

            $table->foreignUuid('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // ops / staff assignee
            $table->foreignUuid('sales_rep_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('referral_code', 20)->nullable();
            $table->bigInteger('commission_amount')->default(0);
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->enum('commission_status', ['pending', 'approved', 'paid', 'cancelled'])->default('pending');
            $table->string('commission_paid_reference', 255)->nullable();
            $table->timestamp('commission_paid_at')->nullable();
            $table->foreignUuid('packed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('packed_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->string('public_token', 64)->unique()->nullable();
            $table->timestamp('token_expires_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('payment_status');
            $table->index(['payment_method', 'payment_status']);
            $table->index(['cod_reconciliation_status', 'status']);
            $table->index('customer_id');
            $table->index('sales_rep_id');
            $table->index('referral_code');
            $table->index('commission_status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
