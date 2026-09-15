<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('repository_analyses', function (Blueprint $table) {
            $table->integer('authenticity_score')->nullable();
            $table->json('authenticity_flags')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repository_analyses', function (Blueprint $table) {
            $table->dropColumn(['authenticity_score', 'authenticity_flags']);
        });
    }
};
