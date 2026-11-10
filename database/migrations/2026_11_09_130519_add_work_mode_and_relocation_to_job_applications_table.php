<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations. Jobs saved with "Remote" as their country or a work mode in their
     * location get it copied to the new work mode column. A location that is only a work mode
     * plus a city is left with the city; longer text is kept as it is.
     */
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table): void {
            $table->string('work_mode')->nullable()->after('location');
            $table->string('relocation')->nullable()->after('work_mode');
        });

        DB::table('job_applications')->orderBy('id')->each(function (object $application): void {
            $country = is_string($application->country) ? mb_trim($application->country) : null;
            $workMode = $country !== null ? $this->workMode($country) : null;

            if ($workMode !== null) {
                $country = null;
            }

            $locationParts = [];

            foreach (preg_split('/\s*[,·|]\s*/u', is_string($application->location) ? $application->location : '') ?: [] as $part) {
                $partWorkMode = $this->workMode($part);

                if ($partWorkMode === null) {
                    $locationParts[] = $part;
                } else {
                    $workMode ??= $partWorkMode;
                }
            }

            $location = mb_trim(implode(', ', array_filter($locationParts))) ?: null;
            $workMode ??= $location !== null ? $this->workModeMentionedIn($location) : null;

            if ($workMode !== null) {
                DB::table('job_applications')->where('id', $application->id)->update([
                    'work_mode' => $workMode,
                    'country' => $country,
                    'location' => $location,
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table): void {
            $table->dropColumn(['work_mode', 'relocation']);
        });
    }

    /**
     * A work mode written inside longer text, such as "Amsterdam, hybrid 2-3 office days". Hybrid wins
     * over the others, because hybrid posts also talk about remote and office days.
     */
    private function workModeMentionedIn(string $text): ?string
    {
        return match (true) {
            preg_match('/\bhybrid\b/i', $text) === 1 => 'hybrid',
            preg_match('/\b(fully )?remote\b/i', $text) === 1 => 'remote',
            preg_match('/\b(on[\s-]?site|in[\s-]office|office)\b/i', $text) === 1 => 'on_site',
            default => null,
        };
    }

    private function workMode(string $text): ?string
    {
        return match (mb_strtolower(mb_trim($text))) {
            'remote', 'fully remote' => 'remote',
            'hybrid' => 'hybrid',
            'on-site', 'onsite', 'on site', 'in office', 'in-office' => 'on_site',
            default => null,
        };
    }
};
