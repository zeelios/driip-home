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
            $table->uuid('customer_id')->nullable();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
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
            $table->enum('payment_method', ['cod', 'bank_transfer', 'momo', 'zalopay', 'vnpay', 'credit_card', 'loyalty_points'])->nullable();
            $table->string('payment_reference', 255)->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->bigInteger('subtotal')->default(0);
            $table->uuid('coupon_id')->nullable();
            $table->foreign('coupon_id')->references('id')->on('coupons')->nullOnDelete();
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

            $table->uuid('warehouse_id')->nullable();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
            $table->uuid('assigned_to')->nullable();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete(); // sales rep
            $table->uuid('packed_by')->nullable();
            $table->foreign('packed_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('packed_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('payment_status');
            $table->index('customer_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
