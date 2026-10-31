<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reading_list_entry_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reading_list_entry_id')->constrained()->restrictOnDelete();
            $table->foreignId('church_id')->constrained()->restrictOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('open')->index();
            $table->text('private_note');
            $table->string('resolution', 20)->nullable();
            $table->dateTime('reported_at');
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['church_id', 'status', 'reported_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_list_entry_issues');
    }
};
