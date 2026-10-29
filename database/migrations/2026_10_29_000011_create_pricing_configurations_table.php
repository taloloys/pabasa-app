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
        Schema::create('pricing_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 20)->default('global'); // 'global' or 'church'
            $table->foreignId('church_id')->nullable()->constrained('churches')->cascadeOnDelete();
            $table->string('name');
            $table->string('currency', 3)->default('PHP');
            $table->string('status', 20)->default('draft')->index(); // 'draft', 'approved', 'retired'
            $table->dateTime('effective_from')->nullable();
            $table->dateTime('effective_until')->nullable();
            $table->json('pricing_rules'); // Item pricing only; no invented values
            $table->json('revenue_share_rules')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->text('change_reason')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index(['scope', 'church_id', 'status', 'effective_from'], 'pricing_config_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_configurations');
    }
};
