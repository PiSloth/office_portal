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
        Schema::table('product_checks', function (Blueprint $table) {
            $table->string('barcode')->nullable()->after('product_id');
            $table->integer('quantity')->default(1)->after('barcode');
            $table->foreignId('location_id')->nullable()->after('quantity')->constrained('locations')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_checks', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn(['barcode', 'quantity', 'location_id']);
        });
    }
};
