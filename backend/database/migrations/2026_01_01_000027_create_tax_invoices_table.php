<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number', 30)->unique();
            $table->enum('invoice_type', ['retail', 'vat'])->default('retail');
            $table->string('buyer_name', 255)->nullable();
            $table->string('buyer_tax_code', 20)->nullable();
            $table->text('buyer_address')->nullable();
            $table->timestamp('issued_at');
            $table->string('file_url', 500)->nullable(); // PDF stored in B2
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_invoices');
    }
};
