<?php

namespace App\Actions\Portfolio;

use App\DTOs\Portfolio\PortfolioDTO;

class BuildPlainTextResume
{
    public function handle(PortfolioDTO $portfolio): string
    {
        $githubUrl = "https://github.com/{$portfolio->githubUsername}";

        foreach ($portfolio->socialLinks as $socialLink) {
            if ($socialLink->platform === 'GitHub') {
                $githubUrl = $socialLink->url;

                break;
            }
        }

        $lines = [
            $portfolio->name,
            $portfolio->tagline,
            '',
            'Contact Information',
            "Location: {$portfolio->location}",
            "Email: {$portfolio->email}",
            "Phone: {$portfolio->phone}",
            "GitHub: {$githubUrl}",
        ];

        if ($portfolio->websiteUrl !== '') {
            $lines[] = "Website: {$portfolio->websiteUrl}";
        }

        $lines = [
            ...$lines,
            '',
            'Professional Summary',
            $portfolio->professionalSummary,
            '',
            'Technical Skills',
        ];

        foreach ($portfolio->skills as $skillCategory) {
            $lines[] = "{$skillCategory->category}: ".implode(', ', $skillCategory->items);

            if ($skillCategory->category === 'Languages' && $portfolio->familiarSkills !== []) {
                $lines[] = 'Familiar with: '.implode(', ', $portfolio->familiarSkills);
            }
        }

        $lines[] = '';
        $lines[] = 'Experience';

        foreach ($portfolio->experience as $experience) {
            $lines[] = "{$experience->role} — {$experience->company} | {$experience->period}";
            $lines[] = $experience->description;

            foreach ($experience->highlights as $highlight) {
                $lines[] = "- {$highlight}";
            }

            $lines[] = '';
        }

        $lines[] = 'Additional Projects';

        foreach ($portfolio->projects as $project) {
            if ($project->tier !== 2) {
                continue;
            }

            $lines[] = $project->name;

            if ($project->period !== null) {
                $lines[] = "Period: {$project->period}";
            }

            $lines[] = $project->description;

            if ($project->metric !== null) {
                $lines[] = "Metric: {$project->metric}";
            }

            $lines[] = 'Technologies: '.implode(', ', $project->technologies);

            if ($project->demoUrl !== null) {
                $lines[] = 'Link: '.$project->demoUrl;
            }

            if ($project->secondaryUrl !== null) {
                $lines[] = 'Additional link: '.$project->secondaryUrl;
            }

            $lines[] = '';
        }

        $lines[] = 'Open Source';

        foreach ($portfolio->openSourceContributions as $contribution) {
            $lines[] = "{$contribution->title} | {$contribution->date} | {$contribution->organization}";
            $lines[] = $contribution->description;
            $lines[] = 'Link: '.$contribution->url;

            if ($contribution->secondaryUrl !== null) {
                $lines[] = 'Additional link: '.$contribution->secondaryUrl;
            }

            $lines[] = '';
        }

        $lines[] = 'Education';

        foreach ($portfolio->education as $education) {
            $lines[] = "{$education->degree} — {$education->school} | {$education->period}";
        }

        $lines[] = '';
        $lines[] = 'Languages';

        foreach ($portfolio->languages as $language) {
            $lines[] = "{$language->name} ({$language->level})";
        }

        return implode("\n", $lines)."\n";
    }
}
