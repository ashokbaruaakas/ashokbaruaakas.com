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
        Schema::table('assistant_proposals', function (Blueprint $table): void {
            $table->json('job_keywords')->nullable()->after('before');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assistant_proposals', function (Blueprint $table): void {
            $table->dropColumn('job_keywords');
        });
    }
};
