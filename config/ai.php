<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | The provider the assistant talks to. The owner can switch it and set the
    | API keys under Settings > AI, which override the values given here.
    |
    */

    'default' => env('AI_PROVIDER', 'anthropic'),

    /*
    |--------------------------------------------------------------------------
    | AI Providers
    |--------------------------------------------------------------------------
    */

    'providers' => [
        'anthropic' => [
            'driver' => 'anthropic',
            'key' => env('ANTHROPIC_API_KEY'),
            'url' => env('ANTHROPIC_URL', 'https://api.anthropic.com/v1'),
        ],

        'openrouter' => [
            'driver' => 'openrouter',
            'key' => env('OPENROUTER_API_KEY'),
        ],

        'opencode-go' => [
            'driver' => 'openai-compatible',
            'key' => env('OPENCODE_GO_API_KEY'),
            'url' => env('OPENCODE_GO_URL', 'https://opencode.ai/inference/go/openai/v1'),
        ],
    ],

];
