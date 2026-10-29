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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_type')->default('user'); // 'user', 'system', 'anonymous'
            $table->string('action'); // e.g. 'membership.revoked', 'invitation.created'
            $table->string('target_type');
            $table->string('target_id');
            $table->foreignId('church_id')->nullable()->constrained('churches')->nullOnDelete();
            $table->string('request_id')->nullable();
            $table->text('reason')->nullable();
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent_summary')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['church_id', 'created_at']);
            $table->index(['actor_user_id', 'created_at']);
            $table->index(['target_type', 'target_id']);
            $table->index(['action', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
