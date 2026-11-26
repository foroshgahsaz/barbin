<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile', 20)->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('password');
            $table->json('operator_permissions')->nullable()->after('is_active');
        });

        Schema::create('customer_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['owner_user_id', 'slug']);
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('customer_group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('mobile', 20);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('notify_consent')->default(true);
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();
            $table->unique(['owner_user_id', 'mobile']);
            $table->index(['owner_user_id', 'is_active']);
        });

        Schema::create('inventory_import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('status', 32)->default('draft');
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedInteger('duplicate_codes_count')->default(0);
            $table->unsignedInteger('invalid_rows_count')->default(0);
            $table->unsignedInteger('zero_stock_count')->default(0);
            $table->unsignedInteger('positive_stock_count')->default(0);
            $table->json('preview_errors')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('inventory_snapshot_id')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_import_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('original_filename');
            $table->timestamp('recorded_at');
            $table->unsignedInteger('items_count')->default(0);
            $table->unsignedInteger('in_stock_count')->default(0);
            $table->unsignedInteger('zero_stock_count')->default(0);
            $table->string('import_status', 32)->default('confirmed');
            $table->json('error_info')->nullable();
            $table->timestamps();
            $table->index('recorded_at');
        });

        Schema::create('inventory_snapshot_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_snapshot_id')->constrained()->cascadeOnDelete();
            $table->string('product_code', 64);
            $table->string('description')->nullable();
            $table->string('unit', 32)->nullable();
            $table->decimal('quantity', 18, 4)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['inventory_snapshot_id', 'product_code']);
            $table->index('product_code');
        });

        Schema::create('sales_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('original_filename');
            $table->string('source_path');
            $table->string('output_excel_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('pdf_generated_at')->nullable();
            $table->unsignedInteger('product_count')->default(0);
            $table->string('processing_status', 32)->default('pending');
            $table->foreignId('inventory_snapshot_id')->nullable()->constrained()->nullOnDelete();
            $table->string('notification_status', 32)->default('none');
            $table->json('processing_errors')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('sales_file_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_file_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('product_code', 64);
            $table->string('description')->nullable();
            $table->string('unit', 32)->nullable();
            $table->decimal('price', 18, 2)->nullable();
            $table->decimal('stock_quantity', 18, 4)->nullable();
            $table->string('stock_status', 32)->default('pending');
            $table->string('match_notes')->nullable();
            $table->timestamps();
            $table->index(['sales_file_id', 'row_number']);
            $table->index('product_code');
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('file_path');
            $table->string('thumbnail_path')->nullable();
            $table->string('mime_type', 64);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_image_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_image_id')->constrained()->cascadeOnDelete();
            $table->string('product_code', 64);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->unique(['product_image_id', 'product_code']);
            $table->index('product_code');
        });

        Schema::create('messengers', function (Blueprint $table) {
            $table->id();
            $table->string('driver', 32);
            $table->string('name');
            $table->text('bot_token');
            $table->string('channel_id')->nullable();
            $table->string('channel_name')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_success_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 64);
            $table->string('key', 128);
            $table->text('value')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
            $table->unique(['group', 'key']);
        });

        Schema::create('pdf_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_file_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamps();
        });

        Schema::create('delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_file_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('messenger_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 32);
            $table->text('message_text')->nullable();
            $table->string('status', 32)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('external_message_id')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->string('idempotency_key', 128)->nullable();
            $table->timestamps();
            $table->index(['sales_file_id', 'customer_id', 'channel']);
            $table->index(['user_id', 'created_at']);
            $table->index('idempotency_key');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('delivery_logs');
        Schema::dropIfExists('pdf_access_tokens');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('messengers');
        Schema::dropIfExists('product_image_codes');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('sales_file_items');
        Schema::dropIfExists('sales_files');
        Schema::dropIfExists('inventory_snapshot_items');
        Schema::dropIfExists('inventory_snapshots');
        Schema::dropIfExists('inventory_import_batches');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('customer_groups');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['mobile', 'is_active', 'operator_permissions']);
        });
    }
};