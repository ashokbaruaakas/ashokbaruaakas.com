<?php

test('each public page has one h1 and public images define alt behavior', function (): void {
    $portfolioComponents = glob(resource_path('js/components/portfolio/*.vue')) ?: [];
    $guestLayout = resource_path('js/layouts/GuestLayout.vue');
    $backLink = resource_path('js/components/portfolio/PortfolioBackLink.vue');
    $publicPageSources = [
        'home' => [
            $guestLayout,
            resource_path('js/pages/Home.vue'),
            ...$portfolioComponents,
        ],
        'resume' => [
            $guestLayout,
            $backLink,
            resource_path('js/pages/resume/Index.vue'),
        ],
        'open-source' => [
            $guestLayout,
            $backLink,
            resource_path('js/pages/open-source/Index.vue'),
        ],
    ];
    $imageCount = 0;

    foreach ($publicPageSources as $routeName => $sourceFiles) {
        $this->get(route($routeName))->assertOk();

        $headingCount = 0;

        foreach ($sourceFiles as $sourceFile) {
            $source = (string) file_get_contents($sourceFile);
            $matches = [];

            preg_match_all('/<h1(?:\s|>)/i', $source, $matches);
            $headingCount += count($matches[0]);

            preg_match_all('/<img\b([^>]*)>/i', $source, $imageMatches);
            $imageCount += count($imageMatches[0]);

            foreach ($imageMatches[1] as $attributes) {
                $hasAltText = preg_match('/(?:^|\s):?alt\s*=\s*(["\']).*?\1/is', $attributes) === 1;
                $isExplicitlyHidden = preg_match('/(?:^|\s)aria-hidden\s*=\s*(["\'])true\1/i', $attributes) === 1;

                expect($hasAltText || $isExplicitlyHidden)->toBeTrue();
            }
        }

        expect($headingCount)->toBe(1);
    }

    expect($imageCount)->toBeGreaterThan(0);
});
