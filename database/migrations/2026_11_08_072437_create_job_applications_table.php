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
        Schema::create('job_applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('job_hunt_id')->constrained()->cascadeOnDelete();
            $table->string('company');
            $table->string('role');
            $table->string('status')->index();
            $table->string('chance')->nullable();
            $table->string('chance_note')->nullable();
            $table->string('link', 2048)->nullable();
            $table->string('country')->nullable();
            $table->string('location')->nullable();
            $table->string('source')->nullable();
            $table->string('salary')->nullable();
            $table->string('contact')->nullable();
            $table->date('applied_on')->nullable();
            $table->date('deadline')->nullable();
            $table->string('next_step')->nullable();
            $table->date('next_step_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
