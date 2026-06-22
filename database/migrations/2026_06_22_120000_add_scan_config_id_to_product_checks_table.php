<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_checks', function (Blueprint $table): void {
            $table->foreignId('scan_config_id')
                ->nullable()
                ->after('check_session_id')
                ->constrained('scan_configs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('product_checks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('scan_config_id');
        });
    }
};
