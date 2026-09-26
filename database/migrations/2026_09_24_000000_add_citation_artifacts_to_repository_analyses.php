<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repository_analyses', function (Blueprint $table) {
            $table->json('artifact_file_paths')->nullable()->after('authenticity_flags');
            $table->json('commit_samples')->nullable()->after('artifact_file_paths');
        });
    }

    public function down(): void
    {
        Schema::table('repository_analyses', function (Blueprint $table) {
            $table->dropColumn(['artifact_file_paths', 'commit_samples']);
        });
    }
};
