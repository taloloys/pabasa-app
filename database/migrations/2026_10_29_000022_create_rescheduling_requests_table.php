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
        Schema::create('rescheduling_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('intention_id')->nullable()->constrained('intentions')->nullOnDelete();
            $table->foreignId('church_id')->constrained('churches')->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('request_source', 30)->default('user');
            $table->foreignId('requested_mass_schedule_id')->constrained('mass_schedules')->cascadeOnDelete();
            $table->text('reason');
            $table->string('status', 30)->default('submitted')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('decision_reason')->nullable();
            $table->string('payment_implication_status', 40)->default('not_assessed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rescheduling_requests');
    }
};
