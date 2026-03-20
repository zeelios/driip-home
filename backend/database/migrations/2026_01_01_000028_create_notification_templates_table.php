<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug', 100)->unique();
            $table->string('name', 255);
            $table->enum('channel', ['email', 'sms', 'zalo_oa'])->default('email');
            $table->string('subject', 255)->nullable();
            $table->text('body_html');
            $table->jsonb('variables')->default('[]'); // list of available variable names
            $table->string('locale', 10)->default('vi');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('channel', 20);
            $table->string('recipient', 255); // email or phone
            $table->uuid('template_id')->nullable();
            $table->foreign('template_id')->references('id')->on('notification_templates')->nullOnDelete();
            $table->string('subject', 255)->nullable();
            $table->jsonb('payload')->default('{}');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->integer('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error')->nullable();
            $table->string('notifiable_type', 100)->nullable();
            $table->uuid('notifiable_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_templates');
    }
};
