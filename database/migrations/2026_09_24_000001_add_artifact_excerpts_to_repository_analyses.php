<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repository_analyses', function (Blueprint $table) {
            $table->json('artifact_excerpts')->nullable()->after('commit_samples');
        });
    }

    public function down(): void
    {
        Schema::table('repository_analyses', function (Blueprint $table) {
            $table->dropColumn(['artifact_excerpts']);
        });
    }
};
