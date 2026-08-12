---
paths:
  - routes/web.php
---

# Routes

## Portfolio data lives in the home route closure via Laravibe DTOs
The public portfolio (home '/') passes all content as a `portfolio` Inertia prop built from App\DTOs\Portfolio DTOs (PortfolioDTO, ProjectDTO, SkillCategoryDTO, SocialLinkDTO, ExperienceDTO) directly in the routes/web.php closure. Keep the home route DB-free and public; never render a login link on it. Skill categories are enumerated in App\Enums\SkillCategory. Frontend consumes `resources/js/types/portfolio.ts`.
