<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('overall_score', 3, 1);
            $table->enum('verdict', ['strong_hire', 'hire', 'maybe', 'no_hire', 'strong_no_hire']);
            $table->longText('narrative_summary');
            $table->json('strengths');
            $table->json('concerns');
            $table->json('interview_focus_areas');
            $table->string('ai_model_used');
            $table->timestamp('evaluated_at');
            $table->boolean('reviewed_by_hr')->default(false);
            $table->text('hr_notes')->nullable();
            $table->timestamps();

            $table->index('verdict');
            $table->index('overall_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
