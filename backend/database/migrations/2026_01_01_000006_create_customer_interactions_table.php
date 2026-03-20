<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_interactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['call', 'message', 'email', 'chat', 'visit', 'note']);
            $table->string('channel', 50)->nullable(); // facebook, zalo, phone, email
            $table->text('summary');
            $table->text('outcome')->nullable();
            $table->timestamp('follow_up_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable(); // immutable log — no updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_interactions');
    }
};
