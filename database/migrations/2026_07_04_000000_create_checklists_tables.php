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
        Schema::disableForeignKeyConstraints();

        if (!Schema::hasTable('checklists')) {
            Schema::create('checklists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_type_id')->nullable()->constrained('product_types')->nullOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('checklist_items')) {
            Schema::create('checklist_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('checklist_id')->constrained('checklists')->cascadeOnDelete();
                $table->string('label');
                $table->boolean('is_required')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('purchase_request_checklists')) {
            Schema::create('purchase_request_checklists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_request_id')->constrained('purchase_requests')->cascadeOnDelete();
                $table->foreignId('checklist_item_id')->constrained('checklist_items')->cascadeOnDelete();
                $table->boolean('is_checked')->default(false);
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('workflow_transitions', 'checklist_id')) {
            Schema::table('workflow_transitions', function (Blueprint $table) {
                $table->foreignId('checklist_id')->nullable()->constrained('checklists')->nullOnDelete();
            });
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        if (Schema::hasColumn('workflow_transitions', 'checklist_id')) {
            Schema::table('workflow_transitions', function (Blueprint $table) {
                try {
                    $table->dropForeign(['checklist_id']);
                } catch (\Exception $e) {}
                $table->dropColumn('checklist_id');
            });
        }

        Schema::dropIfExists('purchase_request_checklists');
        Schema::dropIfExists('checklist_items');
        Schema::dropIfExists('checklists');

        Schema::enableForeignKeyConstraints();
    }
};
