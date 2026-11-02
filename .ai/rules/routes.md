---
paths:
  - routes/web.php
---

# Routes

## Portfolio content lives in the database and reaches pages as Laravibe DTOs
Public pages (home, resume, plain-text resume, open source) get all content from App\Actions\Portfolio\GetPortfolioData, which reads the portfolio tables (profiles, projects, experiences, ...) and returns App\DTOs\Portfolio DTOs as the `portfolio` Inertia prop. Lists are ordered by `sort_order`. Database\Seeders\PortfolioSeeder imports the initial content and does nothing once a profile exists, so it never overwrites edited content; it runs on every deploy from scripts/post-deployment.sh and before the Feature suite. The deploy's resume-pdf job renders from a fresh seeded SQLite database, not production. Keep these routes public and never render a login link on them. Frontend consumes `resources/js/types/portfolio.ts`.
