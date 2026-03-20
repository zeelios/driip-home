<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('customer_code', 20)->unique()->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 255)->unique()->nullable();
            $table->string('phone', 20)->unique()->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('avatar', 500)->nullable();
            $table->string('source', 100)->nullable(); // facebook, instagram, zalo, web, manual
            $table->uuid('referrer_id')->nullable();
            $table->foreign('referrer_id')->references('id')->on('customers')->nullOnDelete();
            $table->string('referral_code', 20)->unique()->nullable();
            $table->jsonb('tags')->default('[]');
            $table->boolean('is_blocked')->default(false);
            $table->text('blocked_reason')->nullable();
            $table->integer('total_orders')->default(0);
            $table->bigInteger('total_spent')->default(0);
            $table->timestamp('last_ordered_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('zalo_id', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
