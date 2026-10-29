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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 36)->unique();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('church_id')->constrained('churches')->cascadeOnDelete();
            $table->decimal('expected_amount', 12, 2);
            $table->decimal('submitted_amount', 12, 2)->nullable();
            $table->decimal('verified_amount', 12, 2)->nullable();
            $table->string('currency', 3)->default('PHP');
            $table->string('status', 30)->default('pending_payment')->index();
            $table->string('provider', 30)->default('gcash');
            $table->string('submitted_reference')->nullable()->index();
            $table->string('normalized_reference')->nullable()->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('verified_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('resolution_code', 50)->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['church_id', 'status', 'created_at']);
            $table->index(['order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
