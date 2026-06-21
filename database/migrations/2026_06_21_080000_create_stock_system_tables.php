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
        // 1. Product Types
        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Categories
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_type_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        // 3. Sub Categories
        Schema::create('sub_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        // 4. Locations
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 5. Product Type Fields
        Schema::create('product_type_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_type_id')->constrained()->cascadeOnDelete();
            $table->string('field_name');
            $table->string('field_label');
            $table->string('field_type'); // text, number, decimal, date, textarea, select, boolean
            $table->boolean('required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 6. Product Import Batches
        Schema::create('product_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('file_path');
            $table->string('file_name');
            $table->foreignId('product_type_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('PENDING'); // PENDING, SUCCESS, FAILED, ROLLBACKED
            $table->integer('total_rows')->default(0);
            $table->integer('imported_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        // 7. Product Import Logs
        Schema::create('product_import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('product_import_batches')->cascadeOnDelete();
            $table->integer('row_number');
            $table->json('data_json');
            $table->json('errors_json')->nullable();
            $table->string('status'); // SUCCESS, FAILED
            $table->timestamp('created_at')->useCurrent();
        });

        // 8. Products
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sub_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->unique();
            $table->string('barcode')->nullable()->index();
            $table->string('qr_code')->nullable()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('ACTIVE'); // ACTIVE, SUSPENDED
            $table->foreignId('import_batch_id')->nullable()->constrained('product_import_batches')->nullOnDelete();
            $table->timestamps();
        });

        // 9. Product Attribute Values (EAV pattern)
        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('field_name');
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'field_name']);
        });

        // 10. Scan Configurations
        Schema::create('scan_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_type_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('config_json');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 11. Check Sessions
        Schema::create('check_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('started_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->string('status')->default('DRAFT'); // DRAFT, OPEN, COMPLETED, CANCELLED
            $table->timestamps();
        });

        // 12. Product Checks
        Schema::create('product_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('check_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checked_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('checked_at');
            $table->string('result_status'); // PASS, FAIL, WARNING
            $table->text('remark')->nullable();
            $table->timestamps();
        });

        // 13. Product Check Values
        Schema::create('product_check_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_check_id')->constrained()->cascadeOnDelete();
            $table->string('field_name');
            $table->text('expected_value')->nullable();
            $table->text('actual_value')->nullable();
            $table->text('difference_value')->nullable();
            $table->timestamps();
        });

        // 14. Decision Types
        Schema::create('decision_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // INVESTIGATE, REPAIR, TRANSFER, RECOUNT, ADJUST_STOCK, ESCALATE
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 15. Decision Rules
        Schema::create('decision_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('criteria_field'); // e.g. weight_g, location_id, code, etc.
            $table->string('criteria_condition'); // mismatch, exceeds_tolerance
            $table->foreignId('decision_type_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 16. Decisions
        Schema::create('decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_check_id')->constrained()->cascadeOnDelete();
            $table->foreignId('decision_type_id')->constrained()->cascadeOnDelete();
            $table->string('action_status')->default('OPEN'); // OPEN, IN_PROGRESS, DONE, REJECTED
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decision_by')->constrained('users')->cascadeOnDelete();
            $table->text('remark')->nullable();
            $table->timestamps();
        });

        // 17. Decision Histories
        Schema::create('decision_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('decision_id')->constrained()->cascadeOnDelete();
            $table->string('old_status');
            $table->string('new_status');
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->text('remark')->nullable();
            $table->timestamps();
        });

        // 18. Comments
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('decision_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('comment_type'); // DECISION, LOG
            $table->text('comment');
            $table->timestamps();
        });

        // 19. Attachments (Polymorphic)
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('attachable_type');
            $table->unsignedBigInteger('attachable_id');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type');
            $table->integer('file_size');
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            
            $table->index(['attachable_type', 'attachable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('decision_histories');
        Schema::dropIfExists('decisions');
        Schema::dropIfExists('decision_rules');
        Schema::dropIfExists('decision_types');
        Schema::dropIfExists('product_check_values');
        Schema::dropIfExists('product_checks');
        Schema::dropIfExists('check_sessions');
        Schema::dropIfExists('scan_configs');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_import_logs');
        Schema::dropIfExists('product_import_batches');
        Schema::dropIfExists('product_type_fields');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('sub_categories');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('product_types');
    }
};
