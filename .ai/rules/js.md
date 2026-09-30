---
paths:
  - 'resources/js/**/*.vue'
---

# Js

## Frontend changes are verified with lint, prettier, and vue-tsc — no JS test runner
The repo has no JS test framework (no Vitest, no pest-plugin-browser), so a presentational Vue change cannot get an automated component test. Verify with `npx eslint <file>`, `npx prettier --check <file>`, `npm run types:check`, plus the Pest suite (`php artisan test --compact --filter=Portfolio`) for anything touching props/data. Adding a JS test dependency requires user approval. A running Vite dev server (Lerd, base path `/@lerd-vite/`) can be compile-checked with `curl http://localhost:<port>/@lerd-vite/<path-to-sfc>`.
