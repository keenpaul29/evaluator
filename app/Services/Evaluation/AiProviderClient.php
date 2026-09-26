<?php

namespace App\Services\Evaluation;

use App\Exceptions\AiEvaluationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiProviderClient
{
    private string $provider;

    private string $geminiKey;

    private string $geminiModel;

    private string $openaiKey;

    private string $openaiModel;

    public ?string $lastProviderUsed = null;

    public function __construct()
    {
        $this->provider = config('services.ai.provider', 'gemini') ?? 'gemini';
        $this->geminiKey = (string) (config('services.gemini.api_key') ?? '');
        $this->geminiModel = (string) (config('services.gemini.model') ?? 'gemini-1.5-flash');
        $this->openaiKey = (string) (config('services.openai.api_key') ?? '');
        $this->openaiModel = (string) (config('services.openai.model') ?? 'gpt-4o-mini');
    }

    public function callWithFallback(string $prompt): string
    {
        $providers = $this->provider === 'openai'
            ? ['openai', 'gemini']
            : ['gemini', 'openai'];

        $lastException = null;

        foreach ($providers as $provider) {
            try {
                $response = $this->callWithRetry($provider, $prompt);

                $this->lastProviderUsed = $provider;

                return $response;
            } catch (AiEvaluationException $exception) {
                $lastException = $exception;

                Log::warning('AI evaluation provider failed', [
                    'provider' => $provider,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        throw new AiEvaluationException(
            'All configured AI providers failed: '.($lastException?->getMessage() ?? 'unknown error'),
            previous: $lastException
        );
    }

    private function callWithRetry(string $provider, string $prompt): string
    {
        $maxAttempts = 3;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return match ($provider) {
                    'gemini' => $this->callGemini($prompt),
                    'openai' => $this->callOpenAI($prompt),
                    default => throw new AiEvaluationException("Unknown provider {$provider}"),
                };
            } catch (AiEvaluationException $exception) {
                $isTransient = $this->isTransientFailure($exception);

                if (! $isTransient || $attempt >= $maxAttempts) {
                    throw $exception;
                }

                Log::warning('AI provider transient failure, retrying', [
                    'provider' => $provider,
                    'attempt' => $attempt,
                    'message' => $exception->getMessage(),
                ]);

                sleep($attempt * 3);
            }
        }

        throw new AiEvaluationException("Provider {$provider} retry loop exhausted");
    }

    private function isTransientFailure(AiEvaluationException $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, 'provider connection failed')
            || str_contains($message, '"status":503')
            || str_contains($message, 'UNAVAILABLE')
            || str_contains($message, 'high demand')
            || str_contains($message, '"status":429');
    }

    public function get_model_used(): string
    {
        return $this->lastProviderUsed === 'gemini' ? $this->geminiModel : $this->openaiModel;
    }

    private function callGemini(string $prompt): string
    {
        if (! $this->geminiKey) {
            throw new AiEvaluationException('Gemini API key not configured');
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->geminiModel}:generateContent?key={$this->geminiKey}";

        $response = $this->postJson($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.3,
                'maxOutputTokens' => 4096,
                'responseMimeType' => 'application/json',
            ],
        ]);

        if ($response->failed()) {
            Log::error('Gemini API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new AiEvaluationException('Gemini API call failed: '.$response->body());
        }

        $data = $response->json();

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    private function callOpenAI(string $prompt): string
    {
        if (! $this->openaiKey) {
            throw new AiEvaluationException('OpenAI API key not configured');
        }

        $response = $this->postJson('https://api.openai.com/v1/chat/completions', [
            'model' => $this->openaiModel,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a technical hiring evaluator. Output valid JSON only.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.3,
            'max_tokens' => 4096,
            'response_format' => ['type' => 'json_object'],
        ], ['Authorization' => 'Bearer '.$this->openaiKey]);

        if ($response->failed()) {
            Log::error('OpenAI API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new AiEvaluationException('OpenAI API call failed: '.$response->body());
        }

        $data = $response->json();

        return $data['choices'][0]['message']['content'] ?? '';
    }

    private function postJson(string $url, array $payload, array $headers = []): Response
    {
        $request = Http::timeout(120);

        if (! empty($headers)) {
            $request = $request->withHeaders(array_merge([
                'Content-Type' => 'application/json',
            ], $headers));
        } else {
            $request = $request->withHeaders(['Content-Type' => 'application/json']);
        }

        try {
            return $request->post($url, $payload);
        } catch (ConnectionException|RequestException $exception) {
            throw new AiEvaluationException(
                'AI provider connection failed: '.$exception->getMessage(),
                previous: $exception
            );
        }
    }
}
