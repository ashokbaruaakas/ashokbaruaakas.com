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
        Schema::create('resumes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('template');
            $table->unsignedTinyInteger('page_limit');
            $table->unsignedSmallInteger('page_count')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });

        DB::table('resumes')->insert([
            'name' => 'Main',
            'slug' => 'main',
            'template' => 'classic',
            'page_limit' => 2,
            'is_public' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resumes');
    }
};
