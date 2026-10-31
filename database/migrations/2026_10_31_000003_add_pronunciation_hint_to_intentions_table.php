<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intentions', function (Blueprint $table) {
            $table->string('pronunciation_hint', 255)->nullable()->after('person_or_family_name');
        });
    }

    public function down(): void
    {
        Schema::table('intentions', function (Blueprint $table) {
            $table->dropColumn('pronunciation_hint');
        });
    }
};
