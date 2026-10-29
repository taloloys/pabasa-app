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
        Schema::create('reading_list_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reading_list_id')->constrained('reading_lists')->cascadeOnDelete();
            $table->foreignId('church_id')->constrained('churches')->cascadeOnDelete();
            $table->foreignId('mass_schedule_id')->constrained('mass_schedules')->cascadeOnDelete();
            $table->foreignId('intention_id')->constrained('intentions')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(1);
            $table->string('status', 30)->default('pending')->index(); // 'pending', 'completed', 'issue_reported', 'excluded'
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['reading_list_id', 'intention_id']);
            $table->index(['church_id', 'mass_schedule_id', 'position'], 'rle_church_mass_pos_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reading_list_entries');
    }
};
