<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            
            // Actor tracking
            $table->enum('actor_type', ['customer', 'staff', 'system', 'api'])->default('system');
            $table->uuid('actor_id')->nullable();
            
            // Activity details
            $table->enum('activity_type', [
                'status_change',
                'payment',
                'deposit_recorded',
                'note_added',
                'file_upload',
                'claim_created',
                'claim_resolved',
                'return_shipped',
                'return_received',
                'refund_processed',
                'commission_calculated',
                'commission_paid',
                'order_created',
                'order_edited',
                'customer_notified',
                'system_event',
            ])->default('system_event');
            
            $table->text('description');
            $table->jsonb('metadata')->default('{}');
            
            // Audit trail
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            $table->timestamp('created_at');
            
            // Indexes
            $table->index('order_id');
            $table->index('actor_type');
            $table->index('activity_type');
            $table->index('created_at');
            $table->index(['order_id', 'activity_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_activities');
    }
};
