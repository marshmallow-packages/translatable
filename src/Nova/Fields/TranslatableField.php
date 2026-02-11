<?php

namespace Marshmallow\Translatable\Nova\Fields;

use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;
use Marshmallow\Translatable\Models\Language;
use Marshmallow\Translatable\TranslatableConfig;

class TranslatableField extends Field
{
    public $component = 'translatable-field';

    protected Field $innerField;

    protected bool $showOnIndex = false;

    public function __construct(string $name, Field $innerField)
    {
        parent::__construct($name, $innerField->attribute);

        $this->innerField = $innerField;

        $this->withMeta([
            'innerField' => $this->serializeInnerField(),
            'languages' => $this->getLanguages(),
            'defaultLanguage' => TranslatableConfig::getDefaultLanguage(),
        ]);
    }

    public static function make(mixed ...$arguments): static
    {
        return new static(...$arguments);
    }

    protected function serializeInnerField(): array
    {
        return [
            'component' => $this->innerField->component,
            'attribute' => $this->innerField->attribute,
            'name' => $this->innerField->name,
            'nullable' => $this->innerField->nullable,
            'readonly' => $this->innerField->isReadonly(app(NovaRequest::class)),
            'required' => $this->innerField->isRequired(app(NovaRequest::class)),
            'textAlign' => $this->innerField->textAlign,
            'panel' => $this->innerField->panel,
        ];
    }

    protected function getLanguages(): array
    {
        return Language::active()
            ->ordered()
            ->get()
            ->map(fn (Language $language) => [
                'code' => $language->code,
                'name' => $language->name,
                'icon' => $language->getIconUrl(),
            ])
            ->all();
    }

    public function resolve($resource, $attribute = null): void
    {
        parent::resolve($resource, $attribute);

        $attribute = $attribute ?? $this->attribute;

        if (method_exists($resource, 'getAllTranslations')) {
            $translations = $resource->getAllTranslations();
            $fieldTranslations = $translations[$attribute] ?? [];

            $defaultValue = $resource->getRawOriginal($attribute);

            $this->withMeta([
                'translations' => $fieldTranslations,
                'defaultValue' => $defaultValue,
            ]);
        }
    }

    protected function fillAttributeFromRequest(NovaRequest $request, $requestAttribute, $model, $attribute): void
    {
        $translations = $request->input("{$requestAttribute}_translations", []);

        if (is_string($translations)) {
            $translations = json_decode($translations, true) ?? [];
        }

        $defaultLanguage = TranslatableConfig::getDefaultLanguage();

        foreach ($translations as $locale => $value) {
            if ($locale === $defaultLanguage) {
                $model->{$attribute} = $value;
            } else {
                $model->setTranslation($locale, $attribute, $value);
            }
        }
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'innerField' => $this->serializeInnerField(),
        ]);
    }
}
