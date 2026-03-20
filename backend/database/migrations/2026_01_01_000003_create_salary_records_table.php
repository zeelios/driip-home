<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('period', 7); // YYYY-MM format
            $table->bigInteger('base_salary')->default(0);
            $table->jsonb('allowances')->default('{}'); // {transport, meal, phone, etc.}
            $table->decimal('overtime_hours', 5, 2)->default(0);
            $table->bigInteger('overtime_rate')->default(0);
            $table->jsonb('bonuses')->default('{}');
            $table->jsonb('deductions')->default('{}'); // {tax, social_insurance, etc.}
            $table->bigInteger('total_gross')->default(0);
            $table->bigInteger('total_net')->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_reference', 255)->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_records');
    }
};
