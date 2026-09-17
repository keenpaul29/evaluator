<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_comparison_candidate', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_comparison_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['candidate_comparison_id', 'candidate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_comparison_candidate');
    }
};
