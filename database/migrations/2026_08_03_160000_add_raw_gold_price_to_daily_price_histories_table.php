<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_price_histories', function (Blueprint $table) {
            $table->decimal('raw_gold_price', 15, 2)->nullable()->after('gold_price');
        });
    }

    public function down(): void
    {
        Schema::table('daily_price_histories', function (Blueprint $table) {
            $table->dropColumn('raw_gold_price');
        });
    }
};
