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
        // 1. Orders table optimizations: add email lookup index, drop redundant single status index
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['submitter_email', 'submitter_type'], 'orders_submitter_email_type_idx');
            $table->dropIndex(['status']);
        });

        // 2. Payments table optimizations: add queue composite index & reference check index
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'payments_status_created_at_idx');
            $table->index(['normalized_reference', 'status'], 'payments_norm_ref_status_idx');
        });

        // 3. Reading list entries: add compound index for ordered listing and max position lookup
        Schema::table('reading_list_entries', function (Blueprint $table) {
            $table->index(['reading_list_id', 'position'], 'rle_reading_list_position_idx');
        });

        // 4. Mass schedules: add tenant-isolated resource conflict index, drop redundant single resource_key index
        Schema::table('mass_schedules', function (Blueprint $table) {
            $table->index(['church_id', 'resource_key', 'starts_at'], 'ms_church_resource_starts_idx');
            $table->dropIndex(['resource_key']);
        });

        // 5. Intentions: add compound index for order slip sorting
        Schema::table('intentions', function (Blueprint $table) {
            $table->index(['order_id', 'sort_order'], 'intentions_order_sort_order_idx');
        });

        // 6. Guest order access: elevate token_hash to unique constraint
        Schema::table('guest_order_access', function (Blueprint $table) {
            $table->dropIndex('guest_order_access_token_hash_index');
            $table->unique('token_hash', 'guest_order_access_token_hash_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guest_order_access', function (Blueprint $table) {
            $table->dropUnique('guest_order_access_token_hash_unique');
            $table->index('token_hash', 'guest_order_access_token_hash_index');
        });

        Schema::table('intentions', function (Blueprint $table) {
            $table->dropIndex('intentions_order_sort_order_idx');
        });

        Schema::table('mass_schedules', function (Blueprint $table) {
            $table->dropIndex('ms_church_resource_starts_idx');
            $table->index('resource_key');
        });

        Schema::table('reading_list_entries', function (Blueprint $table) {
            $table->dropIndex('rle_reading_list_position_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_status_created_at_idx');
            $table->dropIndex('payments_norm_ref_status_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_submitter_email_type_idx');
            $table->index('status');
        });
    }
};
