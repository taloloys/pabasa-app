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
        Schema::create('mass_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained('churches')->cascadeOnDelete();
            $table->foreignId('mass_series_id')->nullable()->constrained('mass_series')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('church_categories')->nullOnDelete();
            $table->string('category_label_snapshot')->nullable();
            $table->string('title');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('timezone', 50)->default('Asia/Manila');
            $table->string('language', 50)->default('English');
            $table->string('booking_status', 20)->default('open')->index(); // 'open', 'closed', 'cancelled'
            $table->string('status', 20)->default('scheduled')->index(); // 'scheduled', 'cancelled', 'completed'
            $table->dateTime('booking_opens_at');
            $table->dateTime('booking_cutoff_at');
            $table->dateTime('finalization_deadline')->nullable();
            $table->string('location_label')->nullable();
            $table->string('resource_key')->nullable()->index();
            $table->text('cancellation_reason')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['church_id', 'starts_at']);
            $table->index(['church_id', 'booking_status', 'starts_at']);
            $table->index(['resource_key', 'starts_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mass_schedules');
    }
};
