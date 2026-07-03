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
        Schema::table('workflow_transitions', function (Blueprint $table) {
            $table->foreignId('validation_rule_set_id')->nullable()->constrained('validation_rule_sets')->nullOnDelete()->after('action_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflow_transitions', function (Blueprint $table) {
            $table->dropForeign(['validation_rule_set_id']);
            $table->dropColumn('validation_rule_set_id');
        });
    }
};
