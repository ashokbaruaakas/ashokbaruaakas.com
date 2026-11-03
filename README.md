# ashokbaruaakas.com

The source code for my personal portfolio and resume site, [ashokbaruaakas.com](https://ashokbaruaakas.com).

It's open source, so you can fork it and use it for your own portfolio. You'll find the steps to make it yours [further down](#make-it-your-own).

## Features

- **Portfolio home page** covering bio, skills, experience, projects, case studies, testimonials and social links
- **Resume page** (`/resume`), plus a plain-text version (`/resume/plain`) and a downloadable PDF built with headless Chrome
- **Open-source page** (`/open-source`) listing contributions
- **SEO basics**: server-side rendering, an XML sitemap, Open Graph images and cache-friendly public pages
- **Private dashboard** with profile, security, two-factor auth and passkey settings (powered by Laravel Fortify)

## Tech stack

- [Laravel 13](https://laravel.com) on PHP 8.4
- [Inertia.js v3](https://inertiajs.com) + [Vue 3](https://vuejs.org) + TypeScript
- [Tailwind CSS v4](https://tailwindcss.com) with [reka-ui](https://reka-ui.com) components
- [Laravel Wayfinder](https://github.com/laravel/wayfinder) for typed routes on the frontend
- [Pest](https://pestphp.com), [Larastan](https://github.com/larastan/larastan), [Pint](https://laravel.com/docs/pint), ESLint and Prettier
- Docker + GitHub Actions for production deploys

## Requirements

- PHP **8.4** with the usual Laravel extensions (`pdo_sqlite`, `mbstring`, `openssl`, `intl`, etc.)
- [Composer](https://getcomposer.org) 2
- Node.js **24** (see `.node-version`) and npm
- SQLite (the default) or MySQL/PostgreSQL

## Getting started

### Quick setup

```bash
git clone https://github.com/ashokbaruaakas/ashokbaruaakas.com.git
cd ashokbaruaakas.com
composer setup
```

`composer setup` installs the PHP and Node dependencies, creates `.env` from `.env.example`, generates the app key, runs the migrations and builds the frontend assets.

### Manual setup

If you'd rather run each step yourself:

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

touch database/database.sqlite
php artisan migrate

npm run build
```

### Configure your environment

`.env.example` points `APP_URL` at a local `.test` domain. If you serve the app with `php artisan serve`, update these values in `.env`:

```dotenv
APP_URL=http://localhost:8000
APP_DOMAIN=localhost
```

The default setup uses SQLite with database-backed sessions, cache and queues, so you don't need any other services to run the site locally.

### Run the dev server

```bash
composer dev
```

This starts the Laravel app server, the Vite dev server with hot reload, and the other dev processes together. Then open [http://localhost:8000](http://localhost:8000).

### Dashboard login (optional)

The public pages work without an account. To try the dashboard, seed the test user:

```bash
php artisan db:seed
```

Then sign in at `/login` with `test@example.com` / `password`.

### Server-side rendering (optional)

To test SSR locally:

```bash
npm run build:ssr
php artisan inertia:start-ssr
```

## Common commands

| Command | What it does |
| --- | --- |
| `composer dev` | Start the local dev processes |
| `composer test` | Check PHP style, run static analysis, then run the test suite |
| `php artisan test` | Run the Pest test suite only |
| `composer lint` | Fix PHP code style with Pint |
| `composer types:check` | Run Larastan / PHPStan |
| `npm run lint` | Fix JS/TS/Vue lint issues with ESLint |
| `npm run format` | Format frontend code with Prettier |
| `npm run types:check` | Type-check Vue and TypeScript with `vue-tsc` |
| `composer ci:check` | Run every check that CI runs |
| `npm run resume:pdf` | Render `/resume` to `public/Ashok-Barua-Resume.pdf` |

### Building the resume PDF

`npm run resume:pdf` renders the resume page to a PDF with headless Chrome. It uses the URL you pass in, then falls back to `$RESUME_URL`, then `APP_URL` from `.env`, then `http://localhost:8000/resume`:

```bash
npm run resume:pdf -- http://localhost:8000/resume
```

It uses a Chrome, Edge or Chromium already installed on your system, or `$CHROME_PATH` if you set it. Without either, it downloads a project-local `chrome-headless-shell` into `node_modules/.cache` (nothing gets installed system-wide).

## Project structure

```
app/
├── Actions/Portfolio/     # GetPortfolioData — all portfolio and resume content lives here
├── DTOs/Portfolio/        # Typed data objects for the portfolio content
└── Http/Controllers/      # Home, Resume, Open Source and dashboard settings controllers
resources/js/
├── pages/                 # Inertia pages (Home, resume, open-source, auth, dashboard)
├── components/            # Vue components
└── layouts/               # Page layouts
routes/web.php             # Public routes, sitemap and dashboard
scripts/                   # Resume PDF builder and deployment helpers
docker/, Dockerfile, compose.yaml  # Production container setup
```

## Make it your own

1. **Content:** edit `app/Actions/Portfolio/GetPortfolioData.php`. Your name, bio, skills, experience, projects, testimonials and links all live in this one file, and the DTOs in `app/DTOs/Portfolio` describe its shape.
2. **Branding:** replace `public/favicon.*`, `public/apple-touch-icon.png` and `public/portfolio-og.(png|svg)`.
3. **Environment:** set `APP_NAME`, `APP_URL`, `APP_DOMAIN` and `MAIL_FROM_ADDRESS` in `.env`.
4. **Resume PDF:** run `npm run resume:pdf` to generate your own PDF, If you want a different file name, change it in `scripts/build-resume-pdf.mjs`, `resources/js/components/portfolio/ResumeDownloadButton.vue` and `.github/workflows/deploy.yml`.
5. **Deployment:** the Docker and GitHub Actions setup (`Dockerfile`, `compose.yaml`, `.github/workflows/deploy.yml`) is tailored to my server. Treat it as a reference, or deploy the app any way you deploy Laravel apps.

## Contributing

Bug reports and pull requests are welcome. Before you open a PR, please run:

```bash
composer ci:check
```

## License

Open-sourced under the [MIT license](https://opensource.org/licenses/MIT). The personal content (my name, bio, resume details and images) is mine, so please swap in your own before you publish a fork.
