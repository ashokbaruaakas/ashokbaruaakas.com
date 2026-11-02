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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('tagline');
            $table->text('bio');
            $table->string('location');
            $table->string('availability');
            $table->text('about');
            $table->text('how_i_work');
            $table->text('side_projects');
            $table->json('open_source_highlights');
            $table->string('github_username');
            $table->string('organization');
            $table->string('email');
            $table->string('phone');
            $table->json('familiar_skills');
            $table->text('professional_summary');
            $table->string('website_url');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
