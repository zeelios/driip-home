<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->decimal('rate', 5, 2); // 8.00, 10.00
            $table->enum('applies_to', ['all', 'category', 'product'])->default('all');
            $table->jsonb('applies_to_ids')->default('[]');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_configs');
    }
};
