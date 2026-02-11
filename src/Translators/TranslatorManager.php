<?php

namespace Marshmallow\Translatable\Translators;

use Illuminate\Support\Manager;
use Marshmallow\Translatable\Contracts\TranslatorContract;

class TranslatorManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('translatable.translators.default', 'deepl');
    }

    public function createDeeplDriver(): TranslatorContract
    {
        return new DeeplTranslator(
            $this->config->get('translatable.translators.deepl', [])
        );
    }

    public function createOpenaiDriver(): TranslatorContract
    {
        return new OpenAiTranslator(
            $this->config->get('translatable.translators.openai', [])
        );
    }

    public function createAnthropicDriver(): TranslatorContract
    {
        return new AnthropicTranslator(
            $this->config->get('translatable.translators.anthropic', [])
        );
    }

    public function getAvailableDrivers(): array
    {
        return ['deepl', 'openai', 'anthropic'];
    }

    public function getConfiguredDrivers(): array
    {
        return collect($this->getAvailableDrivers())
            ->filter(fn (string $driver) => $this->driver($driver)->isConfigured())
            ->values()
            ->all();
    }
}
