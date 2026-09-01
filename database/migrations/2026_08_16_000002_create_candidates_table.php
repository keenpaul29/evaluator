<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('github_username')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->enum('status', ['submitted', 'analyzing', 'evaluated', 'shortlisted', 'rejected'])->default('submitted');
            $table->foreignId('submitted_by')->nullable()->constrained('hr_users')->nullOnDelete();
            $table->enum('submission_type', ['hr_initiated', 'candidate_self_service'])->default('hr_initiated');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('github_username');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
