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
        Schema::table('promotion_items', function (Blueprint $table) {
            $table->unique('barcode');
            $table->unique('lot_serial_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotion_items', function (Blueprint $table) {
            $table->dropUnique(['barcode']);
            $table->dropUnique(['lot_serial_number']);
        });
    }
};
