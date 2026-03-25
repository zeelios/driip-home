<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table): void {
            $table->string('label_reference', 255)->nullable()->after('label_url');
            $table->jsonb('label_payload')->nullable()->after('label_reference');
            $table->timestamp('label_printed_at')->nullable()->after('label_payload');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table): void {
            $table->dropColumn(['label_reference', 'label_payload', 'label_printed_at']);
        });
    }
};
