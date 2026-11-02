<?php

namespace Tests;

use Database\Seeders\PortfolioSeeder;
use Illuminate\Foundation\Testing\Attributes\Seed;
use Illuminate\Foundation\Testing\Attributes\Seeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

#[Seed]
#[Seeder(PortfolioSeeder::class)]
abstract class TestCase extends BaseTestCase
{
    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
