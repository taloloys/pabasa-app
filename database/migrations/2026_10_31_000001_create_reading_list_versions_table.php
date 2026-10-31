<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reading_list_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reading_list_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->json('snapshot');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->unique(['reading_list_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_list_versions');
    }
};
