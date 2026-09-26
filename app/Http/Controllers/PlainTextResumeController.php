<?php

namespace App\Http\Controllers;

use App\Actions\Portfolio\BuildPlainTextResume;
use App\Actions\Portfolio\GetPortfolioData;
use Illuminate\Http\Response;

class PlainTextResumeController extends Controller
{
    public function __construct(
        private GetPortfolioData $getPortfolioData,
        private BuildPlainTextResume $buildPlainTextResume,
    ) {}

    public function __invoke(): Response
    {
        $portfolio = $this->getPortfolioData->handle();
        $content = $this->buildPlainTextResume->handle($portfolio);

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
