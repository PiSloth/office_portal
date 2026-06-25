<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('check_sessions', function (Blueprint $table) {
            $table->foreignId('product_type_id')->nullable()->constrained()->nullOnDelete()->after('description');
            $table->foreignId('scan_config_id')->nullable()->constrained()->nullOnDelete()->after('product_type_id');
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete()->after('scan_config_id');
        });
    }

    public function down(): void
    {
        Schema::table('check_sessions', function (Blueprint $table) {
            $table->dropForeign(['assigned_user_id']);
            $table->dropForeign(['scan_config_id']);
            $table->dropForeign(['product_type_id']);
            $table->dropColumn(['assigned_user_id', 'scan_config_id', 'product_type_id']);
        });
    }
};
