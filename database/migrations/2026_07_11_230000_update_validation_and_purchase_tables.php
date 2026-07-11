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
        Schema::table('validation_rule_sets', function (Blueprint $table) {
            $table->boolean('is_push_decision')->default(false);
        });

        Schema::create('fail_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('purchase_requests')->cascadeOnDelete();
            $table->string('field_name');
            $table->text('expected_value')->nullable();
            $table->text('actual_value')->nullable();
            $table->text('remark')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('purchase_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('purchase_requests')->cascadeOnDelete();
            $table->string('status')->default('open');
            $table->text('remark')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_decisions');
        Schema::dropIfExists('fail_checks');
        Schema::table('validation_rule_sets', function (Blueprint $table) {
            $table->dropColumn('is_push_decision');
        });
    }
};
