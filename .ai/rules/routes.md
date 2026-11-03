---
paths:
  - routes/web.php
---

# Routes

## Portfolio content lives in the database and reaches pages as Laravibe DTOs
Public pages (home, resume, plain-text resume, open source) get all content from App\Actions\Portfolio\GetPortfolioData, which reads the portfolio tables (profiles, projects, experiences, ...) and returns App\DTOs\Portfolio DTOs as the `portfolio` Inertia prop. Lists are ordered by `sort_order`. App\Http\Middleware\EnsurePortfolioPageIsAvailable runs before `cache.headers` so its responses are never publicly cached: with no profile, home renders the `Welcome` page (the only public page with a dashboard link) and other pages return 404; resume and open source also 404 until they have data. Sections and links render only when their data exists. Database\Seeders\AshokBaruaSeeder holds the current owner's content and is run by hand, never automatically, and does nothing once a profile exists. The deploy's resume-pdf job renders from a fresh seeded SQLite database, not production. Owner images live on the `public` disk, tracked by App\Models\Media and shared as the `media` prop; stored names must include a content hash because nginx serves images as immutable. Frontend consumes `resources/js/types/portfolio.ts`.
