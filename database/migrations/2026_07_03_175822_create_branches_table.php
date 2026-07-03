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
        if (!Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->nullable();
                $table->text('address')->nullable();
                $table->string('phone')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('id')->constrained('branches')->nullOnDelete();
            }
        });

        // Safe drop foreign key on purchase_requests
        $foreignKeys = \DB::select(
            "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
             WHERE TABLE_SCHEMA = DATABASE() 
             AND TABLE_NAME = 'purchase_requests' 
             AND COLUMN_NAME = 'branch_id' 
             AND REFERENCED_TABLE_NAME IS NOT NULL"
        );
        
        if (!empty($foreignKeys)) {
            $constraintName = $foreignKeys[0]->CONSTRAINT_NAME;
            try {
                \DB::statement("ALTER TABLE purchase_requests DROP FOREIGN KEY {$constraintName}");
            } catch (\Exception $e) {}
        }

        // Set existing branch_id to null so the foreign key constraint doesn't fail
        \DB::table('purchase_requests')->update(['branch_id' => null]);

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Safe drop foreign key on purchase_requests
        $foreignKeys = \DB::select(
            "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
             WHERE TABLE_SCHEMA = DATABASE() 
             AND TABLE_NAME = 'purchase_requests' 
             AND COLUMN_NAME = 'branch_id' 
             AND REFERENCED_TABLE_NAME IS NOT NULL"
        );
        
        if (!empty($foreignKeys)) {
            $constraintName = $foreignKeys[0]->CONSTRAINT_NAME;
            try {
                \DB::statement("ALTER TABLE purchase_requests DROP FOREIGN KEY {$constraintName}");
            } catch (\Exception $e) {}
        }
            
        try {
            Schema::table('purchase_requests', function (Blueprint $table) {
                $table->foreign('branch_id')->references('id')->on('locations')->nullOnDelete();
            });
        } catch (\Exception $e) {}

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'branch_id')) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            }
        });

        Schema::dropIfExists('branches');
    }
};
