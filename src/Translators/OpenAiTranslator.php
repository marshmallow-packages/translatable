<?php

namespace Marshmallow\Translatable\Translators;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiTranslator extends AbstractTranslator
{
    public function translate(string $text, string $from, string $to): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getConfig('api_key'),
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => $this->getConfig('model', 'gpt-4'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->getSystemPrompt($from, $to),
                ],
                [
                    'role' => 'user',
                    'content' => $text,
                ],
            ],
            'temperature' => 0.3,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException("OpenAI API error: {$response->body()}");
        }

        $data = $response->json();

        return $data['choices'][0]['message']['content'] ?? $text;
    }

    public function translateBatch(array $texts, string $from, string $to): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        if (empty($texts)) {
            return [];
        }

        $jsonTexts = json_encode(array_values($texts));

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getConfig('api_key'),
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => $this->getConfig('model', 'gpt-4'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->getBatchSystemPrompt($from, $to),
                ],
                [
                    'role' => 'user',
                    'content' => $jsonTexts,
                ],
            ],
            'temperature' => 0.3,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException("OpenAI API error: {$response->body()}");
        }

        $data = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? '[]';
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
        return 'OpenAI';
    }

    public function getIdentifier(): string
    {
        return 'openai';
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
