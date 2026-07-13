<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_import_batches', function (Blueprint $table) {
            $table->foreignId('check_session_id')->nullable()->after('product_type_id')->constrained('check_sessions')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_import_batches', function (Blueprint $table) {
            $table->dropForeign(['check_session_id']);
            $table->dropColumn('check_session_id');
        });
    }
};
