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
        Schema::table('job_hunts', function (Blueprint $table): void {
            $table->json('work_modes')->nullable()->after('goal');
            $table->string('countries')->nullable()->after('work_modes');
            $table->boolean('open_to_relocation')->default(false)->after('countries');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_hunts', function (Blueprint $table): void {
            $table->dropColumn(['work_modes', 'countries', 'open_to_relocation']);
        });
    }
};
