<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/bootstrap/app.php',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/public',
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
    )
    ->withPhpSets()
    ->withSkip([
        // Laravel requires run() on a seeder, even an empty one.
        RemoveEmptyClassMethodRector::class => [__DIR__ . '/database/seeders/DatabaseSeeder.php'],
        // Event discovery reads the event class from the first parameter.
        RemoveUnusedPublicMethodParameterRector::class => [__DIR__ . '/app/Listeners/SyncSignedInCookie.php'],
    ]);
