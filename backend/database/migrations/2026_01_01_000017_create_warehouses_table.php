<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 20)->unique();
            $table->string('name', 255);
            $table->enum('type', ['main', 'satellite', 'virtual', 'consignment'])->default('main');
            $table->text('address')->nullable();
            $table->string('province', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->uuid('manager_id')->nullable();
            $table->foreign('manager_id')->references('id')->on('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('warehouse_staff', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['manager', 'picker', 'packer', 'receiver', 'auditor']);
            $table->date('assigned_at');
            $table->date('unassigned_at')->nullable();
            $table->timestamps();

            $table->unique(['warehouse_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_staff');
        Schema::dropIfExists('warehouses');
    }
};
