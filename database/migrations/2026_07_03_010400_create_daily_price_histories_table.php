<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_price_histories', function (Blueprint $table) {
            $table->id();
            $table->decimal('gold_price', 15, 2);
            $table->decimal('tax_rate', 15, 2);
            $table->timestamps(); // automatically gives created_at and updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_price_histories');
    }
};
