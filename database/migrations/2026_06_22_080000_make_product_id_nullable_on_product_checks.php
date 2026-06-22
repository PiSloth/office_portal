<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_checks', function (Blueprint $table): void {
            $table->dropForeign(['product_id']);
        });

        DB::statement('ALTER TABLE product_checks MODIFY product_id BIGINT UNSIGNED NULL');

        Schema::table('product_checks', function (Blueprint $table): void {
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('product_checks', function (Blueprint $table): void {
            $table->dropForeign(['product_id']);
        });

        DB::statement('ALTER TABLE product_checks MODIFY product_id BIGINT UNSIGNED NOT NULL');

        Schema::table('product_checks', function (Blueprint $table): void {
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }
};
