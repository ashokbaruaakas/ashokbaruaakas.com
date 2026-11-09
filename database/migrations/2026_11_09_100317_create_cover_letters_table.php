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
        Schema::create('cover_letters', function (Blueprint $table): void {
            $table->id();
            $table->string('conversation_id', 36)->nullable()->index();
            $table->string('message_id', 36)->nullable()->index();
            $table->foreignId('job_application_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('recipient')->nullable();
            $table->text('body');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cover_letters');
    }
};
