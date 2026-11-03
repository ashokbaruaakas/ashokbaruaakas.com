---
paths:
  - routes/web.php
---

# Routes

## Portfolio content lives in the database and reaches pages as Laravibe DTOs
Public pages (home, resume, plain-text resume, open source) use controllers in App\Http\Controllers\Portfolio, which get all content from App\Actions\Portfolio\GetPortfolioDataAction. It reads the portfolio tables (profiles, projects, experiences, ...) and returns App\DTOs\Portfolio DTOs as the `portfolio` Inertia prop. Lists are ordered by `sort_order`. App\Http\Middleware\EnsurePortfolioPageIsAvailable runs before `cache.headers` so its responses are never publicly cached: with no profile, home renders the `Welcome` page (the only public page with a dashboard link) and other pages return 404; resume and open source also 404 until they have data. Sections and links render only when their data exists. Database\Seeders\AshokBaruaSeeder holds the current owner's content and does nothing once a profile exists. It holds one person's data, so it runs by hand only: never add it to deploy workflows, post-deployment.sh, composer setup or DatabaseSeeder. Owner images live on the `public` disk, tracked by App\Models\Media and shared as the `media` prop; stored names must include a content hash because nginx serves images as immutable. These pages use `PortfolioLayout`, not the kit's `GuestLayout`. The owner edits all of this at `/administration/portfolio`: list fields are defined once in App\Enums\PortfolioList and profile fields in Profile::editableFields(), and the validation rules and forms come from those definitions, so add a field there rather than writing a new form. Frontend consumes `resources/types/portfolio.ts`.
