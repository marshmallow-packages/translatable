<?php

namespace Marshmallow\Translatable\Translators;

use Marshmallow\Translatable\Contracts\TranslatorContract;

abstract class AbstractTranslator implements TranslatorContract
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function translateBatch(array $texts, string $from, string $to): array
    {
        return array_map(
            fn (string $text) => $this->translate($text, $from, $to),
            $texts
        );
    }

    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }
}
