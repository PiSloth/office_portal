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
        Schema::table('product_type_fields', function (Blueprint $table) {
            $table->boolean('show_in_table_by_default')->default(false)->after('show_in_creation_form');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_type_fields', function (Blueprint $table) {
            //
        });
    }
};
