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
        Schema::table('validation_rules', function (Blueprint $table) {
            $table->boolean('is_based_grade')->default(false);
            $table->json('grades_json')->nullable();
            $table->boolean('is_skip_zero')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('validation_rules', function (Blueprint $table) {
            $table->dropColumn(['is_based_grade', 'grades_json', 'is_skip_zero']);
        });
    }
};
