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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('owner')->nullable();
            $table->string('repo')->nullable();
            $table->json('technologies');
            $table->unsignedInteger('stars')->default(0);
            $table->string('language');
            $table->string('demo_url')->nullable();
            $table->unsignedInteger('tier')->default(2);
            $table->string('period')->nullable();
            $table->text('role')->nullable();
            $table->string('metric')->nullable();
            $table->boolean('is_public_repo')->default(false);
            $table->string('link_label')->nullable();
            $table->boolean('show_on_resume')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
