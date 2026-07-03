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
        Schema::create('calculation_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('php_class_name');
            $table->timestamps();
        });

        Schema::create('calculation_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('method_id')->constrained('calculation_methods')->cascadeOnDelete();
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->timestamps();
        });

        Schema::create('calculation_histories', function (Blueprint $table) {
            $table->id();
            $table->morphs('calculatable'); // attach to purchase_item or anything else
            $table->json('parameter_snapshot_json')->nullable();
            $table->json('input_snapshot_json')->nullable();
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->foreignId('user_id')->nullable()->constrained(); // who triggered it
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calculation_histories');
        Schema::dropIfExists('calculation_parameters');
        Schema::dropIfExists('calculation_methods');
    }
};
