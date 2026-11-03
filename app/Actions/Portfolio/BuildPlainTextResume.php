<?php

namespace App\Actions\Portfolio;

use App\DTOs\Portfolio\PortfolioDTO;
use App\DTOs\Portfolio\ProjectDTO;

class BuildPlainTextResume
{
    public function handle(PortfolioDTO $portfolio): string
    {
        $githubUrl = $portfolio->githubUsername !== '' ? "https://github.com/{$portfolio->githubUsername}" : null;
        $linkedInUrl = null;

        foreach ($portfolio->socialLinks as $socialLink) {
            if ($socialLink->platform === 'GitHub') {
                $githubUrl = $socialLink->url;
            }

            if ($socialLink->platform === 'LinkedIn') {
                $linkedInUrl = $socialLink->url;
            }
        }

        $lines = [
            $portfolio->name,
            $portfolio->tagline,
            '',
            'Contact Information',
        ];

        if ($portfolio->location !== '') {
            $timezone = $portfolio->timezoneLabel !== '' ? " ({$portfolio->timezoneLabel})" : '';
            $lines[] = "Location: {$portfolio->location}{$timezone}";
        }

        if ($portfolio->availability !== '') {
            $lines[] = "Availability: {$portfolio->availability}";
        }

        if ($portfolio->email !== '') {
            $lines[] = "Email: {$portfolio->email}";
        }

        if ($portfolio->phone !== '') {
            $lines[] = "Phone: {$portfolio->phone}";
        }

        if ($githubUrl !== null) {
            $lines[] = "GitHub: {$githubUrl}";
        }

        if ($linkedInUrl !== null) {
            $lines[] = "LinkedIn: {$linkedInUrl}";
        }

        if ($portfolio->websiteUrl !== '') {
            $lines[] = "Website: {$portfolio->websiteUrl}";
        }

        if ($portfolio->professionalSummary !== '') {
            $lines = [...$lines, '', 'Professional Summary', $portfolio->professionalSummary];
        }

        if ($portfolio->skills !== []) {
            $lines = [...$lines, '', 'Technical Skills'];

            foreach ($portfolio->skills as $skillCategory) {
                $lines[] = "{$skillCategory->category}: ".implode(', ', $skillCategory->items);

                if ($skillCategory->category === 'Languages' && $portfolio->familiarSkills !== []) {
                    $lines[] = 'Familiar with: '.implode(', ', $portfolio->familiarSkills);
                }
            }
        }

        $lines[] = '';

        if ($portfolio->experience !== []) {
            $lines[] = 'Experience';

            foreach ($portfolio->experience as $experience) {
                $lines[] = "{$experience->role} — {$experience->company} | {$experience->period}";

                if ($experience->description !== '') {
                    $lines[] = $experience->description;
                }

                if ($experience->url !== null) {
                    $lines[] = "Company: {$experience->url}";
                }

                foreach ($experience->highlights as $highlight) {
                    $lines[] = "- {$highlight}";
                }

                $lines[] = '';
            }
        }

        $resumeProjects = array_filter(
            $portfolio->projects,
            fn (ProjectDTO $project): bool => $project->tier === 2 && $project->showOnResume,
        );

        if ($resumeProjects !== []) {
            $lines[] = 'Additional Projects';

            foreach ($resumeProjects as $project) {
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

                foreach ($project->links as $link) {
                    $lines[] = ($link->isPrimary ? 'Link' : 'Additional link')." ({$link->label}): {$link->url}";
                }

                $lines[] = '';
            }
        }

        if ($portfolio->openSourceContributions !== []) {
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
        }

        if ($portfolio->education !== []) {
            $lines[] = 'Education';

            foreach ($portfolio->education as $education) {
                $lines[] = "{$education->degree} — {$education->school} | {$education->period}";
            }

            $lines[] = '';
        }

        if ($portfolio->languages !== []) {
            $lines[] = 'Languages';

            foreach ($portfolio->languages as $language) {
                $lines[] = "{$language->name} ({$language->level})";
            }
        }

        return rtrim(implode("\n", $lines))."\n";
    }
}
