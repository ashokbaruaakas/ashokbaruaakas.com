<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ats_checks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('score');
            $table->unsignedTinyInteger('resume_score');
            $table->unsignedTinyInteger('job_match_score')->nullable();
            $table->string('job_title')->nullable();
            $table->json('checks');
            $table->json('matched_keywords')->nullable();
            $table->json('missing_keywords')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ats_checks');
    }
};
