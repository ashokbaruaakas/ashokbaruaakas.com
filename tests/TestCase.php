<?php

declare(strict_types=1);

namespace Tests;

use App\Jobs\RebuildResumePdf;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Override;

abstract class TestCase extends BaseTestCase
{
    /**
     * Fail any outbound HTTP request that a test hasn't faked, and keep the
     * sync queue from launching a browser whenever a test edits the portfolio.
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        Bus::fake([RebuildResumePdf::class]);
    }
}
