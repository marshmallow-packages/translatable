<?php

namespace Marshmallow\Translatable\Translators;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class AnthropicTranslator extends AbstractTranslator
{
    public function translate(string $text, string $from, string $to): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Anthropic API key is not configured.');
        }

        $response = Http::withHeaders([
            'x-api-key' => $this->getConfig('api_key'),
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => $this->getConfig('model', 'claude-3-sonnet-20240229'),
            'max_tokens' => 4096,
            'system' => $this->getSystemPrompt($from, $to),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $text,
                ],
            ],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException("Anthropic API error: {$response->body()}");
        }

        $data = $response->json();

        return $data['content'][0]['text'] ?? $text;
    }

    public function translateBatch(array $texts, string $from, string $to): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Anthropic API key is not configured.');
        }

        if (empty($texts)) {
            return [];
        }

        $jsonTexts = json_encode(array_values($texts));

        $response = Http::withHeaders([
            'x-api-key' => $this->getConfig('api_key'),
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => $this->getConfig('model', 'claude-3-sonnet-20240229'),
            'max_tokens' => 4096,
            'system' => $this->getBatchSystemPrompt($from, $to),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $jsonTexts,
                ],
            ],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException("Anthropic API error: {$response->body()}");
        }

        $data = $response->json();
        $content = $data['content'][0]['text'] ?? '[]';
        $translations = json_decode($content, true) ?? [];
        $keys = array_keys($texts);

        $result = [];

        foreach ($keys as $index => $key) {
            $result[$key] = $translations[$index] ?? $texts[$key];
        }

        return $result;
    }

    public function isConfigured(): bool
    {
        return ! empty($this->getConfig('api_key'));
    }

    public function getName(): string
    {
        return 'Anthropic';
    }

    public function getIdentifier(): string
    {
        return 'anthropic';
    }

    protected function getSystemPrompt(string $from, string $to): string
    {
        return "You are a professional translator. Translate the following text from {$from} to {$to}. " .
            "Return only the translated text, nothing else. Preserve any HTML tags, placeholders like :name or {name}, and formatting.";
    }

    protected function getBatchSystemPrompt(string $from, string $to): string
    {
        return "You are a professional translator. You will receive a JSON array of texts to translate from {$from} to {$to}. " .
            "Return a JSON array with the translations in the same order. Return only the JSON array, nothing else. " .
            "Preserve any HTML tags, placeholders like :name or {name}, and formatting.";
    }
}
