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
        Schema::create('mass_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained('churches')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('recurrence_rule');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->string('timezone', 50)->default('Asia/Manila');
            $table->foreignId('category_id')->nullable()->constrained('church_categories')->nullOnDelete();
            $table->string('location_label')->nullable();
            $table->string('resource_key')->nullable()->index();
            $table->unsignedInteger('booking_opens_days_before')->default(7);
            $table->unsignedInteger('booking_cutoff_hours_before')->default(2);
            $table->string('status', 20)->default('active')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['church_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mass_series');
    }
};
