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
        Schema::table('product_category_multipliers', function (Blueprint $table) {
            $table->decimal('multiplier', 24, 16)->default(1.0000000000000000)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_category_multipliers', function (Blueprint $table) {
            $table->decimal('multiplier', 12, 6)->default(1.000000)->change();
        });
    }
};
