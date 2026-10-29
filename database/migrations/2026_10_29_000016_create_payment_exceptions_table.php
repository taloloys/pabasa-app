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
        Schema::create('payment_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('church_id')->constrained('churches')->cascadeOnDelete();
            $table->string('type', 40); // 'unmatched', 'underpayment', 'overpayment', 'duplicate_reference', 'late_payment', 'unclear_evidence', 'other'
            $table->string('status', 30)->default('open'); // 'open', 'investigating', 'resolved', 'closed'
            $table->string('priority', 20)->default('normal');
            $table->text('description');
            $table->string('resolution_code', 50)->nullable();
            $table->text('resolution_reason')->nullable();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['church_id', 'status', 'priority']);
            $table->index(['payment_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_exceptions');
    }
};
