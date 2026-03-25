<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('bank_provider', [
                'vietcombank',
                'acb',
                'techcombank',
                'bidv',
                'vietinbank',
                'mbbank',
                'sacombank',
                'vpbank',
                'tpbank',
                'hdbank',
            ]);
            $table->string('account_number', 50);
            $table->string('account_name', 100);
            $table->text('credentials_encrypted');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_check_at')->nullable();
            $table->integer('check_interval_minutes')->default(15);
            $table->timestamps();

            $table->unique(['bank_provider', 'account_number']);
            $table->index(['is_active', 'last_check_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_configs');
    }
};
