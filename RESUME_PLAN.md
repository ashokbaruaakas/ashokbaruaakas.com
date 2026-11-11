# Resume Templates and Versions Plan

Status: decisions settled (see "Settled decisions"). Phase 1 is built.

## Problems this solves

1. The `/resume` page and the PDF look different. The PDF is printed from `/resume` by headless Chrome, but `Index.vue` carries two layouts: screen styles and a large `@media print` override block plus `print:` utilities. They drift apart with every change.
2. The resume grows from 2 to 3 pages without warning. Nothing measures pages, so neither the owner nor the assistant knows the length.
3. There is one resume only. No 1-page version, no job-tailored versions, no record of which PDF was sent where.
4. The assistant can edit content but cannot produce a resume for a job.

## Decisions

| Topic                 | Decision                                                                                                                                                                                                                 |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Public `/resume` page | Kept. Rendered with the public resume's template, styled as paper, so it matches the PDF.                                                                                                                                |
| Paper size            | **A4** (210 × 297 mm), already used by the current `@page` rule. Standard everywhere except the US and Canada, and recruiters there open A4 PDFs without issue. A per-resume Letter option can be added later if needed. |
| Templates             | Two, both single-column and ATS-safe: **Classic** (today's design, ported) and **Compact** (denser spacing and type, aimed at one page).                                                                                 |
| Template storage      | In code: one Vue component per template plus a `ResumeTemplate` enum. No HTML editing in the dashboard.                                                                                                                  |
| Versions              | Two layers: **resumes** (named, configurable variants) and **builds** (every PDF build saved as an entry under its resume). See phase 3.                                                                                 |
| Agent                 | Writes resume content and selections. It never writes layout, HTML or the PDF. The fixed template renders it.                                                                                                            |
| Source of page count  | The built PDF. The on-screen number is a live estimate.                                                                                                                                                                  |

## Phase 1: Template foundation and page count

Goal: one template drives the page and the PDF, and the owner sees the page count.

- Add `App\Enums\ResumeTemplate` (`Classic`, `Compact` later) with metadata: label, description, ATS-safe flag.
- Move the current markup out of `resources/views/resume/Index.vue` into `resources/views/resume/templates/Classic.vue`. `Index.vue` becomes a shell that picks the template component and passes the same props to every template.
- Make Classic paper-exact:
    - On screen, the resume is a 210 mm wide white sheet with A4 margins, using the same fonts and sizes as print.
    - The print CSS shrinks to `@page`, hiding site chrome, and `break-inside: avoid` rules. The screen-only and print-only element pairs (for example `resume-screen-contact-value` and `resume-print-contact-value`) collapse into one version.
    - Dashed page-break guides on screen at each A4 page height give a live page estimate.
- Store the active template in the existing settings store. Default: Classic.
- Page count after each build:
    - `BuildResumePdfAction` counts the pages of the produced PDF and stores the count.
    - Add a page limit setting (default 2).
    - The dashboard shows "N pages" and warns when N exceeds the limit.
- The assistant's context includes the current page count and limit, so suggestions respect length.
- Tests: the template setting is used when rendering, page count is stored after a build, the warning appears over the limit.

## Phase 2: Compact template and picker with live previews

- Add `resume/templates/Compact.vue`.
- Add a template picker on the owner side:
    - Each option shows a scaled-down live render of the owner's real content, in an iframe of `/resume?template=<key>` (template override allowed for the signed-in owner only).
    - Under each preview: estimated page count with current content, and the ATS-safe badge.
    - Selecting a template saves it and schedules a PDF rebuild.
- Tests: the owner can preview and select a template, guests cannot use the template override.

## Phase 3: Resumes (versions) and build history

### Resumes

A resume is a named configuration over the portfolio content, not a copy of it.

- New `resumes` table: `name`, `slug`, `template`, `page_limit`, `is_public` (exactly one), `job_application_id` (nullable), timestamps.
- Selection: which experiences, projects, education, skills and bullets are included, and in what order.
- Overrides (see open questions): a short list of text fields a resume can replace, such as headline, summary and individual bullets.
- Migration: existing settings become a resume named "Main", marked public.
- Owner pages: list, create (from scratch or by duplicating), edit selection and overrides, pick template, set page limit.
- URLs:
    - `/resume` and `/resume.pdf` serve the public resume, as today.
    - Private resumes are owner-only at `/resumes/{slug}` with a PDF download. Sharing links can come later.

### Builds

Following the idea of adding a version on each build:

- New `resume_builds` table: `resume_id`, `template`, `page_count`, `status`, the stored PDF, a JSON snapshot of the rendered content, `built_at`.
- Each successful build adds an entry. The resume's latest build is its current PDF.
- History per resume shows date, template and page count, and lets the owner download an older PDF.
- A job application can point to the exact build that was sent.
- Pruning: keep the last 10 builds per resume, and never prune a build linked to a job application.
- The current `ResumePdfBuild` row (debounce token and status) becomes per resume, or merges into `resume_builds`. Decide while building.

- Tests: selection and overrides render correctly, each build adds a history entry, pruning keeps linked builds, private resumes are hidden from guests.

## Phase 4: Agent-generated resumes

- New assistant proposals, using the existing proposal and undo flow:
    - Create a resume for a job application: choose items, order them, write overrides that use the job's keywords, pick template and page limit.
    - Adjust an existing resume: trim, reorder, rewrite bullets.
- Length loop: after a build, if the page count exceeds the limit, the assistant is told the overflow and proposes trims. The owner approves each proposal.
- The ATS check can run against a chosen resume and the job post.
- Tests: proposals apply and undo cleanly, the overflow feeds back to the assistant.

## Settled decisions

Terms follow `GLOSSARY.md`: Portfolio, Resume, Public Resume, Resolved Resume, Template, Selection, Override, Highlight, Build, Sent Build, Archived Resume. "Version" is not used.

1. **Overrides:** allowed for the headline, the summary and an experience's whole list of Highlights (Highlights have no identity, so per-bullet overrides are out). An Override keeps winning when the Portfolio text changes; the Resume shows "Portfolio changed since this Override" from a hash of the Portfolio text stored with it.
2. **Selection:** new Portfolio items join the Public Resume automatically and stay out of private Resumes. `projects.show_on_resume` moves into Main's Selection and the column is dropped.
3. **Rebuilds:** a content edit rebuilds, debounced, every Resume whose Selection includes the edited item.
4. **Build history:** a Build whose content hash matches the previous one is discarded. Keep the last 10 unsent Builds per Resume; never prune Sent Builds.
5. **`ResumePdfBuild` merges into `resume_builds`:** scheduling creates the row (token, status); a newer schedule deletes the superseded row; an identical result is deleted; the latest failed row stays for the dashboard.
6. **`resumes` table lands in phase 1** with a single "Main" row (template, page limit, page count), so phase 3 only adds Selection, Overrides and more rows.
7. **Resolved Resume:** one action applies Selection and Overrides; templates, the plain-text resume, the ATS check, the assistant and Build snapshots all consume it. `/resume/plain` and the default ATS check use the Public Resume.
8. **Printing private Resumes:** headless Chrome opens a short-lived signed URL per Build (`/resumes/{slug}/print?build=…`) that renders the Template only. The Public Resume uses it too. (Public pages have no session, so owner-only query overrides on `/resume` are not possible; the phase 2 previews use the signed print route.)
9. **Build files:** each `resume_builds` row owns its PDF on the disk; `MediaKey::ResumePdf` is removed and `/resume.pdf` streams the Public Resume's latest Build.
10. **Backups:** include Resumes, Selections, Overrides, and Sent Builds with their PDFs and snapshots. Unsent Builds are skipped; import rebuilds each Resume.
11. **Sent Builds:** `resumes.job_application_id` means "tailored for". `job_applications.resume_build_id` is the Sent Build, defaulting when the application moves to Applied to the latest Build of its tailored Resume, else the Public Resume, and editable.
12. **Deleting:** the Public Resume cannot be deleted until another is public. A Resume with Sent Builds is archived (hidden, never rebuilt) instead of deleted. Others delete outright.
13. **Changing the Public Resume** schedules an immediate Build; until it is ready `/resume.pdf` serves that Resume's latest ready Build, or the previous Public Resume's.
14. **Overflow loop:** the assistant never acts on its own. The dashboard shows the overflow with an "Ask the assistant to trim" action; the assistant's context always carries page count and limit; each trim is a proposal the owner approves.
15. **Sharing:** owner-only pages and downloads; unlisted share links are out of scope.

## Phase 1 as built

- `resumes` table with the "Main" row seeded by its migration; `App\Models\Resume`, `App\Enums\ResumeTemplate` (Classic).
- `/resume` is a shell (`resume/Index.vue`) rendering `resume/templates/Classic.vue` inside `ResumeSheet`, a 210 mm paper sheet. The print design is the only design, so screen and PDF match. Signed-in browsers see dashed A4 page guides and a page estimate.
- `BuildResumePdfAction` reads the page count from the built PDF and stores it on the Public Resume.
- Owner page at `/portfolio/resume`: page limit (1 to 5, default 2) and template. The dashboard status bar warns when the PDF is over the limit; the assistant's instructions carry the page count and limit.
