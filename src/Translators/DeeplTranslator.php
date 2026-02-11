<?php

namespace Marshmallow\Translatable\Translators;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class DeeplTranslator extends AbstractTranslator
{
    public function translate(string $text, string $from, string $to): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('DeepL API key is not configured.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'DeepL-Auth-Key ' . $this->getConfig('api_key'),
            'Content-Type' => 'application/json',
        ])->post($this->getApiUrl() . '/v2/translate', [
            'text' => [$text],
            'source_lang' => strtoupper($from),
            'target_lang' => strtoupper($to),
        ]);

        if (! $response->successful()) {
            throw new RuntimeException("DeepL API error: {$response->body()}");
        }

        $data = $response->json();

        return $data['translations'][0]['text'] ?? $text;
    }

    public function translateBatch(array $texts, string $from, string $to): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('DeepL API key is not configured.');
        }

        if (empty($texts)) {
            return [];
        }

        $response = Http::withHeaders([
            'Authorization' => 'DeepL-Auth-Key ' . $this->getConfig('api_key'),
            'Content-Type' => 'application/json',
        ])->post($this->getApiUrl() . '/v2/translate', [
            'text' => array_values($texts),
            'source_lang' => strtoupper($from),
            'target_lang' => strtoupper($to),
        ]);

        if (! $response->successful()) {
            throw new RuntimeException("DeepL API error: {$response->body()}");
        }

        $data = $response->json();
        $translations = $data['translations'] ?? [];
        $keys = array_keys($texts);

        $result = [];

        foreach ($keys as $index => $key) {
            $result[$key] = $translations[$index]['text'] ?? $texts[$key];
        }

        return $result;
    }

    public function isConfigured(): bool
    {
        return ! empty($this->getConfig('api_key'));
    }

    public function getName(): string
    {
        return 'DeepL';
    }

    public function getIdentifier(): string
    {
        return 'deepl';
    }

    protected function getApiUrl(): string
    {
        return rtrim($this->getConfig('api_url', 'https://api-free.deepl.com'), '/');
    }
}
