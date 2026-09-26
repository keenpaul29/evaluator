<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluation_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status')->default('generated');
            $table->string('token', 64)->unique();
            $table->json('brief');
            $table->string('ai_model_used')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->string('submitted_repo_url')->nullable();
            $table->text('reflection')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('reviewed_by')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
