<?php

declare(strict_types=1);

use App\Ai\Agents\ConnectionCheck;
use App\Enums\AiProvider;
use App\Models\User;
use App\Settings\AiSettings;
use App\Settings\SettingsRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

function saveAiSettings(array $values): void
{
    resolve(SettingsRepository::class)->save(new AiSettings()->fill($values));
}

test('the owner can view the AI settings without the saved key reaching the page', function () {
    config(['ai.providers.anthropic.key' => null]);
    saveAiSettings(['anthropicApiKey' => 'sk-ant-saved-secret', 'chatModel' => 'claude-opus-5-5']);

    $response = $this->actingAs(User::factory()->create())->get(route('settings.ai.edit'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/Ai')
            ->where('settings', ['provider' => 'anthropic', 'chatModel' => 'claude-opus-5-5', 'fastModel' => null])
            ->where('apiKeys.anthropic', ['isSaved' => true, 'isInEnv' => false])
            ->where('isReady', true));

    expect($response->content())->not->toContain('sk-ant-saved-secret');
});

test('the AI settings page shows a key that comes from env', function () {
    config(['ai.providers.openrouter.key' => 'sk-or-from-env']);
    app()->forgetScopedInstances();

    $response = $this->actingAs(User::factory()->create())->get(route('settings.ai.edit'));

    $response->assertInertia(fn ($page) => $page->where('apiKeys.openrouter', ['isSaved' => false, 'isInEnv' => true]));

    expect($response->content())->not->toContain('sk-or-from-env');
});

test('the owner can choose a provider, key and models', function () {
    $this->actingAs(User::factory()->create())
        ->put(route('settings.ai.update'), [
            'provider' => 'openrouter',
            'openRouterApiKey' => 'sk-or-new-key',
            'chatModel' => 'openai/gpt-6',
        ])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toasts', [['type' => 'success', 'message' => 'Settings saved.']]);

    $settings = app(AiSettings::class);

    expect($settings->provider)->toBe(AiProvider::OpenRouter)
        ->and($settings->chatModel())->toBe('openai/gpt-6')
        ->and($settings->fastModel())->toBe('anthropic/claude-haiku-5.5')
        ->and(config('ai.default'))->toBe('openrouter')
        ->and(config('ai.providers.openrouter.key'))->toBe('sk-or-new-key')
        ->and(DB::table('settings')->where('group', 'ai')->pluck('payload')->implode(' '))->not->toContain('sk-or-new-key');
});

test('an empty key keeps the saved one and removal clears it', function () {
    saveAiSettings(['anthropicApiKey' => 'sk-ant-saved']);
    $owner = User::factory()->create();

    $this->actingAs($owner)->put(route('settings.ai.update'), ['provider' => 'anthropic', 'anthropicApiKey' => ''])->assertSessionHasNoErrors();

    expect(app(AiSettings::class)->anthropicApiKey)->toBe('sk-ant-saved');

    $this->actingAs($owner)->put(route('settings.ai.update'), ['provider' => 'anthropic', 'removeAnthropicApiKey' => '1'])->assertSessionHasNoErrors();

    expect(app(AiSettings::class)->anthropicApiKey)->toBeNull();
});

test('the provider must be one the assistant supports', function () {
    $this->actingAs(User::factory()->create())
        ->put(route('settings.ai.update'), ['provider' => 'gemini'])
        ->assertSessionHasErrors(['provider' => 'The selected provider is invalid.']);

    expect(app(AiSettings::class)->provider)->toBe(AiProvider::Anthropic);
});

test('guests cannot manage the AI settings', function () {
    ConnectionCheck::fake()->preventStrayPrompts();

    $this->get(route('settings.ai.edit'))->assertRedirect(route('login'));
    $this->put(route('settings.ai.update'), ['provider' => 'openrouter'])->assertRedirect(route('login'));
    $this->post(route('settings.ai.check.store'))->assertRedirect(route('login'));

    ConnectionCheck::assertNeverPrompted();
});

test('the owner can check the connection with the saved fast model', function () {
    ConnectionCheck::fake(['OK']);
    saveAiSettings(['anthropicApiKey' => 'sk-ant-saved', 'fastModel' => 'claude-haiku-5-5']);

    $this->actingAs(User::factory()->create())
        ->post(route('settings.ai.check.store'))
        ->assertInertiaFlash('toasts', [['type' => 'success', 'message' => 'Anthropic answered using claude-haiku-5-5.']]);

    ConnectionCheck::assertPromptedTimes(1);
});

test('the connection check asks for a key before calling the provider', function () {
    config(['ai.providers.anthropic.key' => null]);
    ConnectionCheck::fake()->preventStrayPrompts();

    $this->actingAs(User::factory()->create())
        ->post(route('settings.ai.check.store'))
        ->assertInertiaFlash('toasts', [['type' => 'error', 'message' => 'Add an API key for Anthropic first.']]);

    ConnectionCheck::assertNeverPrompted();
});

test('a rejected key is reported with the provider message', function () {
    Http::preventStrayRequests();
    Http::fake(['api.anthropic.com/v1/messages' => Http::response(['type' => 'error', 'error' => ['type' => 'authentication_error', 'message' => 'invalid x-api-key']], 401)]);
    saveAiSettings(['anthropicApiKey' => 'sk-ant-wrong']);

    $this->actingAs(User::factory()->create())
        ->post(route('settings.ai.check.store'))
        ->assertInertiaFlash('toasts', [[
            'type' => 'error',
            'message' => 'Anthropic could not be reached.',
            'description' => 'invalid x-api-key',
        ]]);
});
