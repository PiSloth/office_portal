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
        Schema::create('promotion_sold_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_item_id')->constrained('promotion_items')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('promotion_price', 16, 2);
            $table->string('reference_number')->index();
            $table->foreignId('daily_price_history_id')->nullable()->constrained('daily_price_histories')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_sold_items');
    }
};
