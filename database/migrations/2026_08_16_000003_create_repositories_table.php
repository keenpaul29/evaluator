<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repositories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('github_repo_id');
            $table->string('name');
            $table->string('full_name');
            $table->string('description')->nullable();
            $table->string('html_url');
            $table->string('default_branch')->default('main');
            $table->string('primary_language')->nullable();
            $table->integer('stars_count')->default(0);
            $table->integer('forks_count')->default(0);
            $table->integer('open_issues_count')->default(0);
            $table->timestamp('created_at_github')->nullable();
            $table->timestamp('updated_at_github')->nullable();
            $table->json('topics')->nullable();
            $table->boolean('is_fork')->default(false);
            $table->string('fork_parent_name')->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();

            $table->index('candidate_id');
            $table->unique(['candidate_id', 'github_repo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repositories');
    }
};
