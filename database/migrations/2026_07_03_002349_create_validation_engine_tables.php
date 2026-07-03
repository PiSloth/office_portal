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
        Schema::create('validation_rule_sets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('validation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_set_id')->constrained('validation_rule_sets')->cascadeOnDelete();
            $table->string('field_name');
            $table->string('operator')->default('equals');
            $table->string('expected_source')->nullable(); // Column name or hardcoded
            $table->decimal('tolerance', 8, 4)->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_editable')->default(true);
            $table->timestamps();
        });

        Schema::create('validation_histories', function (Blueprint $table) {
            $table->id();
            $table->morphs('validatable');
            $table->foreignId('rule_id')->nullable()->constrained('validation_rules')->nullOnDelete();
            $table->string('status'); // PASS or FAIL
            $table->text('input_value')->nullable();
            $table->text('expected_value')->nullable();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('validation_histories');
        Schema::dropIfExists('validation_rules');
        Schema::dropIfExists('validation_rule_sets');
    }
};
