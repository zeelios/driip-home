<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('commission_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('staff_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('rate_percent', 5, 2)->default(5.00);
            $table->jsonb('category_rates')->default('{}');
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
    }
};
