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
        Schema::table('church_branding', function (Blueprint $table) {
            // GCash payment credentials for parishioner payment screen
            $table->string('gcash_name', 100)->nullable()->after('contact_phone');
            $table->string('gcash_number', 30)->nullable()->after('gcash_name');
            $table->string('gcash_qr_path', 255)->nullable()->after('gcash_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('church_branding', function (Blueprint $table) {
            $table->dropColumn(['gcash_name', 'gcash_number', 'gcash_qr_path']);
        });
    }
};
