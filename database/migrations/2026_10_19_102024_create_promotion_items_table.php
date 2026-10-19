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
        Schema::create('promotion_items', function (Blueprint $table) {
            $table->id();
            $table->string('barcode')->nullable()->index();
            $table->string('product')->nullable();
            $table->decimal('weight', 16, 8)->nullable();
            $table->decimal('gold_weight', 16, 8)->nullable();
            $table->decimal('dg_weight', 16, 8)->default(0);
            $table->decimal('addition_wastage', 16, 8)->nullable();
            $table->decimal('total_wastage', 16, 8)->nullable();
            $table->decimal('labor_fee', 14, 2)->default(0);
            $table->string('internal_reference')->nullable();
            $table->string('lot_serial_number')->nullable()->index();
            $table->string('product_subgroup_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_items');
    }
};
