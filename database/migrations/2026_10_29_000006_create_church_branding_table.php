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
        Schema::create('church_branding', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->unique()->constrained('churches')->cascadeOnDelete();
            $table->string('logo_path')->nullable();
            $table->string('banner_path')->nullable();
            $table->string('theme_color', 50)->nullable();
            $table->text('public_description')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('church_branding');
    }
};
