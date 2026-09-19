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
        Schema::table('validation_histories', function (Blueprint $table) {
            $table->foreignId('workflow_state_id')->nullable()->after('rule_id')->constrained('workflow_states')->nullOnDelete();
            $table->foreignId('validation_rule_set_id')->nullable()->after('workflow_state_id')->constrained('validation_rule_sets')->nullOnDelete();
            $table->index(['validatable_type', 'validatable_id', 'workflow_state_id'], 'val_histories_validatable_state_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('validation_histories', function (Blueprint $table) {
            $table->dropIndex('val_histories_validatable_state_idx');
            $table->dropForeign(['workflow_state_id']);
            $table->dropForeign(['validation_rule_set_id']);
            $table->dropColumn(['workflow_state_id', 'validation_rule_set_id']);
        });
    }
};
