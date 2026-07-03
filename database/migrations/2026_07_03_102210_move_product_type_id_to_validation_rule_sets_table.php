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
            $table->dropForeign(['product_type_id']);
            $table->dropColumn('product_type_id');
        });

        Schema::table('validation_rule_sets', function (Blueprint $table) {
            $table->foreignId('product_type_id')->nullable()->after('name')->constrained('product_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('validation_rule_sets', function (Blueprint $table) {
            $table->dropForeign(['product_type_id']);
            $table->dropColumn('product_type_id');
        });

        Schema::table('validation_rules', function (Blueprint $table) {
            $table->foreignId('product_type_id')->nullable()->after('rule_set_id')->constrained('product_types')->nullOnDelete();
        });
    }
};
