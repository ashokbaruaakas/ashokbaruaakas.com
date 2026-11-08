---
paths:
  - 'resources/**/*.vue'
---

# Js

## Frontend changes are verified with Vite+ and vue-tsc
Verify with `npm run lint:check`, `npm run format:check`, `npm run types:check`, plus the Pest suite (`php artisan test --compact --filter=Portfolio`) for anything touching props/data. Portfolio, resume and open-source pages must keep their exact UI and data: compare rendered output before and after any change that touches them, because shared Tailwind tokens, `resources/css/app.css` and `resources/components/ui` all reach those pages. A running Vite dev server (Lerd, base path `/@lerd-vite/`) can be compile-checked with `curl http://localhost:<port>/@lerd-vite/<path-to-sfc>`.
