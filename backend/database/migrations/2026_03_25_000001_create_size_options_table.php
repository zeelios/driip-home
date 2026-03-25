<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('size_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 20)->unique(); // 'S', 'M', 'L', 'XL', '42', '42.5'
            $table->string('display_name', 50);   // 'Small', 'Medium', 'Size 42'
            $table->string('size_type', 20);      // 'letter', 'numeric', 'eu', 'us'
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('size_options');
    }
};
