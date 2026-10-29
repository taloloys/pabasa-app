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
        Schema::table('church_categories', function (Blueprint $table) {
            $table->string('template_type', 30)->default('standard')->after('type');
            $table->decimal('price', 12, 2)->nullable()->after('description');
            $table->json('form_config')->nullable()->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('church_categories', function (Blueprint $table) {
            $table->dropColumn(['template_type', 'price', 'form_config']);
        });
    }
};
