<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluation_progress', function (Blueprint $table) {
            $table->index('candidate_id', 'eval_progress_candidate_id_idx');
        });

        Schema::table('candidates', function (Blueprint $table) {
            $table->index('batch_id', 'candidates_batch_id_idx');
        });

        Schema::table('interview_questions', function (Blueprint $table) {
            $table->index('evaluation_id', 'interview_questions_evaluation_id_idx');
        });

        Schema::table('candidate_comparison_candidate', function (Blueprint $table) {
            $table->index('candidate_id', 'candidate_comp_candidate_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('evaluation_progress', function (Blueprint $table) {
            $table->dropIndex('eval_progress_candidate_id_idx');
        });

        Schema::table('candidates', function (Blueprint $table) {
            $table->dropIndex('candidates_batch_id_idx');
        });

        Schema::table('interview_questions', function (Blueprint $table) {
            $table->dropIndex('interview_questions_evaluation_id_idx');
        });

        Schema::table('candidate_comparison_candidate', function (Blueprint $table) {
            $table->dropIndex('candidate_comp_candidate_id_idx');
        });
    }
};
