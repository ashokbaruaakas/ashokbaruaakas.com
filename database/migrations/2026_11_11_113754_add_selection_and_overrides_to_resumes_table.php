<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('resumes', function (Blueprint $table): void {
            $table->json('selection')->nullable();
            $table->json('overrides')->nullable();
            $table->foreignId('job_application_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('archived_at')->nullable();
        });

        $projects = DB::table('projects')->orderBy('sort_order')->orderBy('id')->get(['id', 'tier', 'show_on_resume'])
            ->map(fn (object $project): array => ['id' => $project->id, 'included' => (int) $project->tier === 2 && (bool) $project->show_on_resume])
            ->all();

        DB::table('resumes')->where('is_public', true)->update(['selection' => json_encode(['projects' => $projects])]);

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn('show_on_resume');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->boolean('show_on_resume')->default(true);
        });

        Schema::table('resumes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('job_application_id');
            $table->dropColumn(['selection', 'overrides', 'archived_at']);
        });
    }
};
