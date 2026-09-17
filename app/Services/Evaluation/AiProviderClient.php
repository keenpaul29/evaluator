<?php

namespace App\Services\Evaluation;

use App\Exceptions\AiEvaluationException;
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
        $this->provider = config('services.ai.provider', 'gemini');
        $this->geminiKey = config('services.gemini.api_key', '');
        $this->geminiModel = config('services.gemini.model', 'gemini-1.5-flash');
        $this->openaiKey = config('services.openai.api_key', '');
        $this->openaiModel = config('services.openai.model', 'gpt-4o-mini');
    }

    public function callWithFallback(string $prompt): string
    {
        $providers = $this->provider === 'openai'
            ? ['openai', 'gemini']
            : ['gemini', 'openai'];

        $lastException = null;

        foreach ($providers as $provider) {
            try {
                $response = $provider === 'gemini'
                    ? $this->callGemini($prompt)
                    : $this->callOpenAI($prompt);

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

        $response = Http::timeout(120)->post($url, [
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

        $response = Http::timeout(120)->withHeaders([
            'Authorization' => 'Bearer '.$this->openaiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => $this->openaiModel,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a technical hiring evaluator. Output valid JSON only.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.3,
            'max_tokens' => 4096,
            'response_format' => ['type' => 'json_object'],
        ]);

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
}
