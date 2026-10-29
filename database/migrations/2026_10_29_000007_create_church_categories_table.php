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
        Schema::create('church_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->nullable()->constrained('churches')->cascadeOnDelete();
            $table->string('type', 20)->default('intention')->index(); // 'intention' or 'mass'
            $table->string('key', 50)->index();
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('color', 50)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_system')->default(false)->index();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['church_id', 'type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('church_categories');
    }
};
