<?php

declare(strict_types=1);

namespace App\Ai;

/**
 * How the assistant writes anything that reaches a resume, the portfolio or a cover letter, so applicant tracking
 * systems parse it cleanly and a recruiter skimming it for a few seconds sees the value.
 */
final class ResumeWriting
{
    public static function rules(): string
    {
        return <<<'TEXT'
            Every word you write for the portfolio, the resume or a cover letter must pass an applicant tracking system (ATS)
            and read well to a recruiter skimming it in a few seconds:
            - Plain text only: no tables, emoji, icons, decorative symbols, Markdown or special bullets. Use standard words for
              job titles, skills and section names, as recruiters search for them.
            - Mirror the exact wording of the target job post for skills, tools and titles the owner really has. Write a term
              out once with its acronym when both are common, such as "Continuous Integration (CI)".
            - Start each highlight with a strong past-tense action verb (present tense for the current role), never with
              "Responsible for", "Worked on", "Helped" or "Duties included". Vary the verbs.
            - Show impact: what was done, how, and the result. Quantify with real numbers, percentages, scale, time or money
              the owner gave you; when no number is known, ask the owner instead of inventing one.
            - Keep each highlight to one line of 12 to 25 words, three to six highlights per role, most impressive first.
            - No first-person pronouns (I, me, my, we) in resume text and no filler such as "hard-working" or "team player".
            - Name the technologies used inside the highlights, so skills are backed by experience and not only listed.
            - The professional summary is 40 to 80 words: title and years of experience, core skills, and one or two proven
              results that fit the roles the owner targets.
            - Cover letters: under 350 words, three or four short paragraphs, the company and role in the opening line, two
              concrete results that match the job's main requirements, and a clear closing call to action.
            - Keep spelling consistent and dates in one format, such as "Jan 2023 - Present".
            TEXT;
    }

    /**
     * What any change to a resume keeps to, whether it rewords, shortens or tailors it.
     */
    public static function resumeRules(): string
    {
        return <<<'TEXT'
            Every resume change keeps to these rules:
            - Never add a fact the portfolio does not state: no new number, tool, employer, title, team size or result.
              Rewording may sharpen, shorten and reorder what it says, nothing more.
            - To shorten a resume, first tighten the wording and leave out the weakest projects, skills and highlights. Cut a
              whole role only when that is not enough, starting with the oldest and least relevant one.
            - To tailor a resume for a job, lead with what the job asks for and use the job post's own words for the skills,
              tools and titles the owner really has.
            TEXT;
    }
}
