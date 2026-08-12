<?php

use function Pest\Laravel\artisan;

test('routes can be cached and booted without closure serialization errors', function () {
    artisan('route:cache')->assertSuccessful();

    // Booting from the cached routes file must succeed.
    artisan('route:list')->assertSuccessful();

    artisan('route:clear')->assertSuccessful();
});
