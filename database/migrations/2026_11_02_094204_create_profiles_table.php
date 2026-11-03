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
            $table->string('headline')->nullable();
            $table->json('alternate_names')->nullable();
            $table->string('tagline')->nullable();
            $table->text('bio')->nullable();
            $table->string('location')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('timezone')->nullable();
            $table->string('timezone_label')->nullable();
            $table->string('availability')->nullable();
            $table->text('about')->nullable();
            $table->text('how_i_work')->nullable();
            $table->text('side_projects')->nullable();
            $table->json('open_source_highlights')->nullable();
            $table->string('github_username')->nullable();
            $table->string('organization')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone_display')->nullable();
            $table->json('familiar_skills')->nullable();
            $table->text('professional_summary')->nullable();
            $table->string('website_url')->nullable();
            $table->string('source_url')->nullable();
            $table->string('built_with')->nullable();
            $table->json('marquee_words')->nullable();
            $table->json('knows_about_topics')->nullable();
            $table->text('open_source_interests')->nullable();
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
