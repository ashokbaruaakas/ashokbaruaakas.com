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
        Schema::table('resumes', function (Blueprint $table): void {
            $table->dropColumn('page_limit');
            $table->string('accent')->nullable()->after('template');
            $table->string('font')->nullable()->after('accent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resumes', function (Blueprint $table): void {
            $table->dropColumn(['accent', 'font']);
            $table->unsignedTinyInteger('page_limit')->default(2);
        });
    }
};
