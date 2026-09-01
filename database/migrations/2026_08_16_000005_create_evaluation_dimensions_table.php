<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_dimensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained()->cascadeOnDelete();
            $table->enum('dimension', [
                'code_quality',
                'technical_judgment',
                'colvalues_alignment',
                'communication',
                'problem_complexity',
                'learning_trajectory',
                'technical_breadth',
            ]);
            $table->decimal('score', 3, 1);
            $table->decimal('weight', 3, 2)->default(1.00);
            $table->text('justification');
            $table->json('evidence');
            $table->timestamps();

            $table->index(['evaluation_id', 'dimension']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_dimensions');
    }
};
