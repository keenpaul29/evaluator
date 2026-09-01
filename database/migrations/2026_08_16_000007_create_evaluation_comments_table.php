<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hr_user_id')->constrained('hr_users')->cascadeOnDelete();
            $table->text('comment');
            $table->timestamps();

            $table->index('evaluation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_comments');
    }
};
