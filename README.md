# ashokbaruaakas.com

The source code for my personal portfolio and resume site, [ashokbaruaakas.com](https://ashokbaruaakas.com).

Built on [LaraVibe-Vue](https://github.com/thesoftpulze/laravibe-vue) and [LaraVibe Standards](https://github.com/thesoftpulze/laravibe-standards).

It's open source, so you can fork it and use it for your own portfolio. You'll find the steps to make it yours [further down](#make-it-your-own).

## Features

- **Portfolio home page** covering bio, skills, experience, projects, case studies, testimonials and social links
- **Resume page** (`/resume`), plus a plain-text version (`/resume/plain`) and a downloadable PDF (`/resume.pdf`) rebuilt on a queue with headless Chrome
- **Open-source page** (`/open-source`) listing contributions
- **SEO basics**: server-side rendering, an XML sitemap, Open Graph images and cache-friendly public pages
- **Portfolio editor** at `/portfolio` for your profile, images, headings, SEO text and every list on the site
- **Dashboard, settings and account area** from LaraVibe-Vue: settings, profile, security, two-factor auth and passkeys (powered by Laravel Fortify)

## Tech stack

- [Laravel 13](https://laravel.com) on PHP 8.4
- [Inertia.js v3](https://inertiajs.com) + [Vue 3](https://vuejs.org) + TypeScript
- [Tailwind CSS v4](https://tailwindcss.com) with [reka-ui](https://reka-ui.com) components
- [Laravel Wayfinder](https://github.com/laravel/wayfinder) for typed routes on the frontend
- [Pest](https://pestphp.com), [Larastan](https://github.com/larastan/larastan), [Pint](https://laravel.com/docs/pint), Rector and Vite+ (Oxlint, Oxfmt)
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
php artisan storage:link
php artisan owner:create

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

### Create your account

Until an owner account exists, the home page shows these instructions and `/login` redirects to it. Create the account with:

```bash
php artisan owner:create
```

Then sign in at `/login`. Registration is closed by default, because each install has one owner.

The command also starts your public profile with the same name and email. If you later change them on the account page, the profile follows, unless you gave it a different name or email under Portfolio.

### Sample data (optional)

`php artisan db:seed` adds nothing, so a fresh install stays empty. To fill every page right away and review the site, run the dummy seeder:

```bash
php artisan db:seed --class=DummySeeder
```

It adds a fictional owner, Sam Rivera, with a full portfolio, sample images and general settings. If no account exists yet, it also creates one: sign in at `/login` with `admin@example.com` and the password `password`.

It only runs when `APP_ENV` is `local` or `testing`, and it does nothing if the portfolio already has content. To replace existing content with the sample data, reset the database first with `php artisan migrate:fresh --seeder=DummySeeder`, which erases everything in it.

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
| `composer test` | Run the test suite |
| `php artisan test` | Run the Pest test suite only |
| `composer pint` | Fix PHP code style with Pint |
| `composer rector` | Apply Rector refactors |
| `composer types:check` | Run Larastan / PHPStan |
| `npm run lint` | Fix JS/TS/Vue lint issues with Vite+ |
| `npm run format` | Format frontend code with Vite+ |
| `npm run types:check` | Type-check Vue and TypeScript with `vue-tsc` |
| `composer ci:check` | Run every check that CI runs |

### Building the resume PDF

Every change in the portfolio editor schedules a queued rebuild 5 minutes later, and each new change restarts that wait. The dashboard shows the countdown on every page, with a button to rebuild right away. The job renders `/resume` with headless Chrome and serves the result at `/resume.pdf`, so a queue worker must be running.

It uses a Chrome or Chromium installed in a standard location, or `CHROME_PATH` if you set it. The worker must be able to open `APP_URL`.

## Project structure

```
app/
├── Actions/Portfolio/     # Reads portfolio content from the database
├── DTOs/Portfolio/        # Typed data objects for the portfolio content
├── Http/Controllers/      # Portfolio pages, plus dashboard, settings and account
└── Models/                # Portfolio content tables and Media
database/seeders/          # DummySeeder for sample data, AshokBaruaSeeder for the owner's content (both run by hand)
resources/
├── views/                 # Inertia pages (Home, resume, open-source, auth, account, Dashboard, portfolio, settings)
├── components/            # Vue components, including portfolio/
├── layouts/               # PortfolioLayout, plus the LaraVibe layouts
└── blades/                # Root Blade view and mail templates
routes/                    # Public routes, sitemap, dashboard, portfolio editor, settings and account
scripts/                   # Deployment helpers
docker/, Dockerfile, compose.yaml  # Production container setup
```

## Make it your own

1. **Content:** a fresh install starts empty and shows a welcome page. Add your information at `/portfolio`, and pages and sections appear once they have data. Your name and email alone keep the welcome page up. To see a complete portfolio first, run the [dummy seeder](#sample-data-optional).
2. **Branding:** upload images and favicons under Images in the editor. They live in Laravel Storage and are tracked in the `media` table. `public/favicon.svg` is the neutral default for an empty install.
3. **Environment:** set `APP_NAME`, `APP_URL`, `APP_DOMAIN` and `MAIL_FROM_ADDRESS` in `.env`.
4. **Resume PDF:** built by a queued job from the resume page and named after you. See [Building the resume PDF](#building-the-resume-pdf).
5. **Deployment:** the Docker and GitHub Actions setup (`Dockerfile`, `compose.yaml`, `.github/workflows/deploy.yml`) is tailored to my server. Treat it as a reference, or deploy the app any way you deploy Laravel apps.

## Contributing

Bug reports and pull requests are welcome. Before you open a PR, please run:

```bash
composer ci:check
```

## License

Open-sourced under the [MIT license](https://opensource.org/licenses/MIT). The personal content (my name, bio, resume details and images) is mine, so please swap in your own before you publish a fork.
