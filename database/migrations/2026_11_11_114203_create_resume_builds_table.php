<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('resume_builds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $table->string('token')->unique();
            $table->string('status');
            $table->timestamp('scheduled_for');
            $table->string('template')->nullable();
            $table->unsignedSmallInteger('page_count')->nullable();
            $table->string('content_hash')->nullable();
            $table->longText('snapshot')->nullable();
            $table->string('path')->nullable();
            $table->timestamp('built_at')->nullable();
            $table->timestamps();

            $table->index(['resume_id', 'status', 'built_at']);
        });

        Schema::table('job_applications', function (Blueprint $table): void {
            $table->foreignId('resume_build_id')->nullable()->constrained()->nullOnDelete();
        });

        $this->carryOverStoredPdf();

        Schema::dropIfExists('resume_pdf_builds');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('resume_pdf_builds', function (Blueprint $table): void {
            $table->id();
            $table->string('token');
            $table->string('status');
            $table->timestamp('scheduled_for');
            $table->timestamps();
        });

        Schema::table('job_applications', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('resume_build_id');
        });

        Schema::dropIfExists('resume_builds');
    }

    /**
     * Keep the PDF visitors download today as the public resume's first build.
     */
    private function carryOverStoredPdf(): void
    {
        $media = DB::table('media')->where('key', 'resume_pdf')->first();
        $resume = DB::table('resumes')->where('is_public', true)->first();

        if ($media === null) {
            return;
        }

        $contents = Storage::disk($media->disk)->get($media->path);

        if (is_string($contents) && $resume !== null) {
            $token = (string) Str::ulid();
            $path = "resume-builds/{$resume->id}/{$token}.pdf";

            Storage::disk('local')->put($path, $contents);

            DB::table('resume_builds')->insert([
                'resume_id' => $resume->id,
                'token' => $token,
                'status' => 'ready',
                'scheduled_for' => now(),
                'template' => $resume->template,
                'page_count' => $resume->page_count,
                'path' => $path,
                'built_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Storage::disk($media->disk)->delete($media->path);
        DB::table('media')->where('id', $media->id)->delete();
    }
};
