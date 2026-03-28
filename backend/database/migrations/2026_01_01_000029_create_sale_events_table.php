<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sale_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['flash_sale', 'drop_launch', 'clearance', 'bundle']);
            $table->enum('status', ['draft', 'scheduled', 'active', 'ended', 'cancelled'])->default('draft');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->integer('max_orders_total')->nullable();
            $table->boolean('is_public')->default(false);
            $table->string('banner_url', 500)->nullable();
            $table->uuid('created_by');
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sale_event_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sale_event_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('sale_price');
            $table->bigInteger('compare_price')->nullable();
            $table->integer('max_qty_per_customer')->nullable();
            $table->integer('max_qty_total')->nullable();
            $table->integer('sold_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->uuid('linked_product_id')->nullable();
            $table->foreign('linked_product_id')->references('id')->on('products')->nullOnDelete();
            $table->uuid('customer_id')->nullable();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->string('email', 255)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('source', 100)->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['linked_product_id', 'notified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist_entries');
        Schema::dropIfExists('sale_event_items');
        Schema::dropIfExists('sale_events');
    }
};
