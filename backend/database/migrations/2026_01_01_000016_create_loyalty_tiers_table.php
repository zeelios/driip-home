<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_tiers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->integer('min_lifetime_points')->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->boolean('free_shipping')->default(false);
            $table->boolean('early_access')->default(false);
            $table->decimal('birthday_multiplier', 4, 2)->default(1.00);
            $table->jsonb('perks')->default('[]');
            $table->string('color', 7)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('loyalty_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('tier_id')->constrained('loyalty_tiers')->restrictOnDelete();
            $table->integer('points_balance')->default(0);
            $table->integer('lifetime_points')->default(0);
            $table->bigInteger('lifetime_spending')->default(0);
            $table->timestamp('tier_achieved_at')->nullable();
            $table->timestamp('tier_expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('loyalty_account_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['earn', 'redeem', 'expire', 'bonus', 'adjust', 'refund']);
            $table->integer('points'); // positive = earn, negative = spend
            $table->integer('balance_after');
            $table->string('reference_type', 50)->nullable(); // 'order', 'campaign', 'manual'
            $table->uuid('reference_id')->nullable();
            $table->text('description');
            $table->timestamp('expires_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('loyalty_campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->enum('type', ['multiplier', 'flat_bonus', 'birthday', 'referral', 'first_order']);
            $table->decimal('multiplier', 5, 2)->nullable();
            $table->integer('bonus_points')->nullable();
            $table->jsonb('conditions')->default('{}');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_campaigns');
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('loyalty_accounts');
        Schema::dropIfExists('loyalty_tiers');
    }
};
