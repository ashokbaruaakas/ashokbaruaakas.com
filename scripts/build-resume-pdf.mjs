// Renders the /resume page to a static, ATS-friendly PDF with headless Chrome.
//
// Usage: npm run resume:pdf -- [resume-url]
//   resume-url  defaults to $RESUME_URL, then APP_URL from .env, then
//               http://localhost:8000/resume
//   $CHROME_PATH overrides the browser binary.
//
// The browser binary is resolved in order:
//   1. $CHROME_PATH
//   2. a system Chrome/Edge/Chromium, when one is already installed
//   3. a project-local chrome-headless-shell, downloaded on demand into
//      node_modules/.cache/puppeteer via @puppeteer/browsers. Nothing is ever
//      installed system-wide; deleting node_modules removes it.
import { execFileSync } from 'node:child_process';
import { existsSync, readFileSync } from 'node:fs';
import { resolve } from 'node:path';

import {
    Browser,
    BrowserTag,
    getInstalledBrowsers,
    install,
    resolveBuildId,
    detectBrowserPlatform,
} from '@puppeteer/browsers';

/** Read a single key from the local .env file, or null when unset. */
function readDotEnvValue(key) {
    const envPath = resolve('.env');

    if (!existsSync(envPath)) {
        return null;
    }

    const match = readFileSync(envPath, 'utf8').match(
        new RegExp(`^${key}=(.*)$`, 'm'),
    );

    return match ? match[1].trim().replace(/^["']|["']$/g, '') : null;
}

/**
 * Explicit argument wins, then $RESUME_URL, then APP_URL from .env (the local
 * lerd domain), then a plain `php artisan serve` on port 8000.
 */
const appUrl = readDotEnvValue('APP_URL');
const resumeUrl =
    process.argv[2] ??
    process.env.RESUME_URL ??
    (appUrl
        ? `${appUrl.replace(/\/$/, '')}/resume`
        : 'http://localhost:8000/resume');
const outputPath = resolve('public/Ashok-Barua-Resume.pdf');
const browserCacheDir = resolve('node_modules/.cache/puppeteer');

const systemBrowserCandidates = [
    process.env.CHROME_PATH,
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
    '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    '/usr/bin/google-chrome',
    '/usr/bin/chromium',
    '/usr/bin/chromium-browser',
].filter(Boolean);

const systemBrowserPath = systemBrowserCandidates.find((candidate) =>
    existsSync(candidate),
);

/**
 * Resolve a project-local chrome-headless-shell, downloading the stable build
 * into node_modules/.cache/puppeteer when it is not there yet.
 */
async function resolveProjectLocalBrowser() {
    const platform = detectBrowserPlatform();

    if (!platform) {
        throw new Error(`Unsupported platform: ${process.platform}`);
    }

    const browsers = await getInstalledBrowsers({
        cacheDir: browserCacheDir,
        platform,
    });
    const installed = browsers.find(
        (entry) => entry.browser === Browser.CHROMEHEADLESSSHELL,
    );

    if (installed) {
        return installed.executablePath;
    }

    console.log(
        'No system Chrome found; downloading chrome-headless-shell into node_modules/.cache/puppeteer (one-time).',
    );

    const buildId = await resolveBuildId(
        Browser.CHROMEHEADLESSSHELL,
        platform,
        BrowserTag.STABLE,
    );

    const downloaded = await install({
        browser: Browser.CHROMEHEADLESSSHELL,
        buildId,
        cacheDir: browserCacheDir,
        platform,
    });

    return downloaded.executablePath;
}

let browserPath = systemBrowserPath;

if (!browserPath) {
    try {
        browserPath = await resolveProjectLocalBrowser();
    } catch (error) {
        console.error(
            'Could not resolve a project-local chrome-headless-shell.',
        );
        console.error(error instanceof Error ? error.message : error);
        console.error(
            'Set CHROME_PATH to a Chrome/Chromium binary, or retry once the network is available.',
        );
        process.exit(1);
    }
}

// chrome-headless-shell is headless by design and rejects --headless variants.
const isHeadlessShell = browserPath.includes('chrome-headless-shell');

try {
    execFileSync(
        browserPath,
        [
            ...(isHeadlessShell ? [] : ['--headless=new']),
            '--disable-gpu',
            '--no-pdf-header-footer',
            '--virtual-time-budget=10000',
            `--print-to-pdf=${outputPath}`,
            resumeUrl,
        ],
        { stdio: 'inherit' },
    );
} catch {
    console.error(`\nFailed to render ${resumeUrl}.`);
    console.error(
        'Is the app running? (lerd site, or `php artisan serve` on port 8000.)',
    );
    console.error(
        'Pass an explicit URL: npm run resume:pdf -- https://your-site.test/resume',
    );
    process.exit(1);
}

console.log(`Wrote ${outputPath}`);
