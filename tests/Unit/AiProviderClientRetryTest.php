<?php

namespace Tests\Unit;

use App\Exceptions\AiEvaluationException;
use App\Services\Evaluation\AiProviderClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiProviderClientRetryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.ai.provider', 'gemini');
        Config::set('services.gemini.api_key', 'test-gemini-key');
        Config::set('services.openai.api_key', '');
    }

    public function test_transient_503_is_retried_then_succeeds(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push([
                    'error' => ['message' => 'This model is currently experiencing high demand.', 'status' => 'UNAVAILABLE'],
                ], 503)
                ->push([
                    'candidates' => [
                        ['content' => ['parts' => [['text' => '{"ok":true}']]]],
                    ],
                ], 200),
        ]);

        $client = new AiProviderClient;

        $this->assertSame('{"ok":true}', $client->callWithFallback('test prompt'));
        $this->assertSame('gemini', $client->lastProviderUsed);

        Http::assertSentCount(2);
    }

    public function test_transient_503_exhausting_retries_falls_back_to_next_provider(): void
    {
        Config::set('services.openai.api_key', 'test-openai-key');
        Config::set('services.openai.model', 'gpt-test');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push([
                    'error' => ['message' => 'This model is currently experiencing high demand.', 'status' => 'UNAVAILABLE'],
                ], 503)
                ->push([
                    'error' => ['message' => 'This model is currently experiencing high demand.', 'status' => 'UNAVAILABLE'],
                ], 503)
                ->push([
                    'error' => ['message' => 'This model is currently experiencing high demand.', 'status' => 'UNAVAILABLE'],
                ], 503),
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => '{"ok":true}']]],
            ], 200),
        ]);

        $client = new AiProviderClient;

        $this->assertSame('{"ok":true}', $client->callWithFallback('test prompt'));
        $this->assertSame('openai', $client->lastProviderUsed);
    }

    public function test_non_transient_gemini_error_throws_without_retry(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => ['message' => 'Invalid key', 'status' => 'INVALID_ARGUMENT'],
            ], 400),
        ]);

        $client = new AiProviderClient;

        $this->expectException(AiEvaluationException::class);

        $client->callWithFallback('test prompt');
    }

    public function test_connection_failure_is_retried(): void
    {
        $attempt = 0;

        Http::fake([
            'generativelanguage.googleapis.com/*' => function ($request) use (&$attempt) {
                $attempt++;

                if ($attempt === 1) {
                    throw new ConnectionException('cURL error 6: Could not resolve host: generativelanguage.googleapis.com');
                }

                return Http::response([
                    'candidates' => [
                        ['content' => ['parts' => [['text' => '{"ok":true}']]]],
                    ],
                ], 200);
            },
        ]);

        $client = new AiProviderClient;

        $this->assertSame('{"ok":true}', $client->callWithFallback('test prompt'));
        $this->assertSame('gemini', $client->lastProviderUsed);

        $this->assertSame(2, $attempt);
    }
}
