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
        Schema::create('assistant_proposals', function (Blueprint $table): void {
            $table->id();
            $table->string('conversation_id', 36)->nullable()->index();
            $table->string('message_id', 36)->nullable()->index();
            $table->string('section');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->unsignedBigInteger('created_item_id')->nullable();
            $table->string('summary');
            $table->json('changes');
            $table->json('before')->nullable();
            $table->string('status');
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assistant_proposals');
    }
};
