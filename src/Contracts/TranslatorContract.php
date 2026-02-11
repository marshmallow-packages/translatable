<?php

namespace Marshmallow\Translatable\Contracts;

interface TranslatorContract
{
    public function translate(string $text, string $from, string $to): string;

    public function translateBatch(array $texts, string $from, string $to): array;

    public function isConfigured(): bool;

    public function getName(): string;

    public function getIdentifier(): string;
}
