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
        Schema::create('intentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('church_id')->constrained('churches')->cascadeOnDelete();
            $table->foreignId('mass_schedule_id')->constrained('mass_schedules')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('church_categories')->nullOnDelete();
            $table->string('category_label_snapshot')->nullable();
            $table->string('person_or_family_name');
            $table->text('wording');
            $table->string('status', 30)->default('awaiting_payment')->index();
            $table->string('original_name_snapshot')->nullable();
            $table->text('original_wording_snapshot')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['church_id', 'mass_schedule_id', 'status']);
            $table->index(['order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intentions');
    }
};
