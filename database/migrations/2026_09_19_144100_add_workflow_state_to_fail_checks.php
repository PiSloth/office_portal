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
        Schema::table('fail_checks', function (Blueprint $table) {
            $table->foreignId('workflow_state_id')->nullable()->after('field_name')->constrained('workflow_states')->nullOnDelete();
            $table->index(['purchase_request_id', 'workflow_state_id'], 'fail_checks_pr_state_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fail_checks', function (Blueprint $table) {
            $table->dropIndex('fail_checks_pr_state_idx');
            $table->dropForeign(['workflow_state_id']);
            $table->dropColumn('workflow_state_id');
        });
    }
};
