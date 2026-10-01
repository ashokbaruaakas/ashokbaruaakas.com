<?php

test('public routes return unique indexable metadata', function (string $routeName, string $title, string $description): void {
    $response = $this->get(route($routeName));

    $response->assertOk()
        ->assertSee($title)
        ->assertSee('name="description"', false)
        ->assertSee('content="'.e($description).'"', false)
        ->assertSee('rel="canonical"', false)
        ->assertSee(route($routeName), false)
        ->assertSee('property="og:title"', false)
        ->assertSee('property="og:description"', false)
        ->assertSee('property="og:image"', false)
        ->assertSee('property="og:image:type"', false)
        ->assertSee('name="twitter:card"', false)
        ->assertSee('content="summary_large_image"', false)
        ->assertSee('name="twitter:image"', false)
        ->assertSee('portfolio-og.png', false);
})->with([
    'home' => [
        'home',
        'Senior Full-Stack Engineer | Fintech & Multi-Tenant SaaS',
        'Senior full-stack engineer focused on fintech payments and multi-tenant SaaS, with SiPay, HSM, card-saving, and 40,000+ customer platform experience.',
    ],
    'resume' => [
        'resume',
        'Resume | Senior Full-Stack Engineer',
        'Review Ashok Barua’s resume covering fintech payments, SiPay, HSM and card saving, multi-tenant SaaS ownership, technical skills, and work history.',
    ],
    'open source' => [
        'open-source',
        'Open Source | Laravel Projects & Contributions',
        'Explore Ashok Barua’s open-source contributions, Laravel projects, developer tools, and community work across GitHub, Packagist, and developer tooling.',
    ],
]);

test('canonical and og:url stay https when tls is terminated at the edge', function (string $path): void {
    $host = parse_url(route('home'), PHP_URL_HOST);
    $expected = 'https://'.$host.($path === '/' ? '/' : $path);

    $response = $this->get('http://'.$host.$path);

    $response->assertOk()
        ->assertSee('rel="canonical" href="'.$expected.'"', false)
        ->assertSee('property="og:url" content="'.$expected.'"', false);
})->with([
    'home' => '/',
    'resume' => '/resume',
    'open source' => '/open-source',
]);

test('a spoofed forwarded host cannot poison the canonical url', function (): void {
    $host = parse_url(route('home'), PHP_URL_HOST);

    $this->withHeaders(['X-Forwarded-Host' => 'evil.example'])
        ->get('http://'.$host.'/resume')
        ->assertOk()
        ->assertSee('rel="canonical" href="https://'.$host.'/resume"', false);
});

test('the home page includes Person and WebSite structured data', function (): void {
    $response = $this->get(route('home'))->assertOk();

    expect(preg_match(
        '/<script[^>]*type="application\/ld\+json"[^>]*>(.*?)<\/script>/s',
        $response->getContent(),
        $matches,
    ))->toBe(1);

    /** @var array{'@graph': array<int, array<string, mixed>>} $structuredData */
    $structuredData = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
    $person = collect($structuredData['@graph'])->firstWhere('@type', 'Person');
    $website = collect($structuredData['@graph'])->firstWhere('@type', 'WebSite');

    expect($person)->toMatchArray([
        'name' => 'Ashok Barua',
        'jobTitle' => 'Senior Full-Stack Engineer',
        'url' => rtrim(route('home'), '/').'/',
        'image' => asset('portfolio-og.png'),
        'email' => 'ashokbaruaakas@gmail.com',
        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => 'Chattogram',
            'addressCountry' => 'Bangladesh',
        ],
        'sameAs' => [
            'https://github.com/ashokbaruaakas',
            'https://x.com/ashokbaruaakas',
            'https://t.me/ashokbaruaakas',
        ],
        'alumniOf' => [
            ['@type' => 'EducationalOrganization', 'name' => 'East Delta University'],
            ['@type' => 'EducationalOrganization', 'name' => 'Bangladesh Sweden Polytechnic Institute'],
        ],
        'worksFor' => ['@type' => 'Organization', 'name' => 'Grow More Gaze'],
    ])
        ->and($person['knowsAbout'])->toContain('PHP', 'Laravel', 'HSM')
        ->and($person['knowsAbout'])->not->toContain('Go', 'Rust')
        ->and($website)->toMatchArray([
            '@type' => 'WebSite',
            'name' => 'Ashok Barua',
            'url' => rtrim(route('home'), '/').'/',
        ]);
});

test('the sitemap is valid XML with all public pages and lastmod dates', function (): void {
    $response = $this->get(route('sitemap'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    $sitemap = simplexml_load_string($response->getContent());

    expect($sitemap)->toBeInstanceOf(SimpleXMLElement::class);

    $locations = [];

    foreach ($sitemap->url as $url) {
        $locations[] = (string) $url->loc;
    }

    expect($locations)->toBe([
        rtrim(route('home'), '/').'/',
        route('resume'),
        route('open-source'),
    ]);

    foreach ($sitemap->url as $url) {
        expect((string) $url->lastmod)->toMatch('/^\d{4}-\d{2}-\d{2}$/');
    }
});

test('the public robots file advertises the sitemap and HTML language is English', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<html lang="en"', false);

    expect(file_get_contents(public_path('robots.txt')))
        ->toContain('Sitemap: https://ashokbaruaakas.com/sitemap.xml');
});
