---
paths:
  - 'app/Http/**'
---

# Http

## Build SEO/absolute URLs from the URL generator, never Request::url()
TLS terminates at the edge (Traefik -> nginx -> PHP-FPM), so the raw request scheme is always http://. URL::forceScheme('https') only fixes url()/route()/asset(), not $request->url()/fullUrl(). Canonical, og:url and sitemap URLs must be built with url()/route(); root URLs get an explicit trailing slash (rtrim(route('home'), '/').'/') so canonical, og:url, JSON-LD and sitemap all agree. trustProxies in bootstrap/app.php trusts X-Forwarded-For/Port/Proto only - never add X-Forwarded-Host (host-poisoning). PublicSeoTest covers http-behind-edge and spoofed-host cases.
