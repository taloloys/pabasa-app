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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 36)->unique();
            $table->string('order_number', 50)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('submitter_type', 30)->default('registered_user'); // 'registered_user', 'guest', 'staff_assisted'
            $table->foreignId('church_id')->constrained('churches')->cascadeOnDelete();
            $table->foreignId('mass_schedule_id')->constrained('mass_schedules')->cascadeOnDelete();
            $table->string('status', 30)->default('awaiting_payment')->index();
            $table->string('currency', 3)->default('PHP');
            $table->decimal('expected_amount', 12, 2)->nullable();
            $table->foreignId('pricing_configuration_id')->nullable()->constrained('pricing_configurations')->nullOnDelete();
            $table->json('pricing_snapshot')->nullable();
            $table->string('submitter_name');
            $table->string('submitter_email');
            $table->string('privacy_notice_version', 20)->default('1.0');
            $table->dateTime('privacy_acknowledged_at');
            $table->dateTime('submitted_at');
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index(['church_id', 'created_at']);
            $table->index(['church_id', 'mass_schedule_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
