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
        if (Schema::hasTable('checklist_items') && !Schema::hasColumn('checklist_items', 'sort_order')) {
            Schema::table('checklist_items', function (Blueprint $table) {
                $table->integer('sort_order')->default(0)->after('is_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('checklist_items') && Schema::hasColumn('checklist_items', 'sort_order')) {
            Schema::table('checklist_items', function (Blueprint $table) {
                $table->dropColumn('sort_order');
            });
        }
    }
};
