<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repository_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repository_id')->unique()->constrained()->cascadeOnDelete();
            $table->integer('total_files_analyzed')->default(0);
            $table->integer('total_lines_analyzed')->default(0);
            $table->json('primary_languages');
            $table->boolean('has_readme')->default(false);
            $table->boolean('has_tests')->default(false);
            $table->boolean('has_ci_config')->default(false);
            $table->boolean('has_documentation')->default(false);
            $table->decimal('commit_frequency_score', 3, 1)->nullable();
            $table->decimal('avg_commit_quality_score', 3, 1)->nullable();
            $table->string('code_complexity_estimate')->default('medium');
            $table->json('architectural_patterns')->nullable();
            $table->json('dependencies_analysis')->nullable();
            $table->timestamp('analyzed_at');
            $table->timestamps();

            $table->index('repository_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repository_analyses');
    }
};
