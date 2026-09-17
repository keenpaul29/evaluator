<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_progress', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->unique();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['queued', 'analyzing', 'scoring', 'complete', 'failed'])->default('queued');
            $table->string('current_step')->nullable();
            $table->unsignedInteger('progress_percent')->default(0);
            $table->unsignedInteger('steps_total')->default(0);
            $table->unsignedInteger('steps_completed')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_progress');
    }
};
