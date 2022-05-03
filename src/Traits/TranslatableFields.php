<?php

namespace Marshmallow\Translatable\Traits;

use Laravel\Nova\Http\Requests\NovaRequest;
use Marshmallow\Translatable\Fields\LanguageToggler;

trait TranslatableFields
{
    use Translatable;

    public function fields(NovaRequest $request)
    {
        if (!$request->has('editMode')) {
            return $this->translatableFields($request);
        }

        /**
         * Only add the translation block if it is activated.
         */
        if (!config('translatable.nova_translatable_fields')) {
            return $this->translatableFields($request);
        }

        if (method_exists($this, 'translatableFieldsEnabled') && !$this->translatableFieldsEnabled()) {
            return $this->translatableFields($request);
        }

        if ($this->weAreNotTranslating() || $request->editMode == 'create') {
            return $this->addTranslationToggleField(
                $this->translatableFields($request),
                $request
            );
        }

        $fields = $this->translatableFields($request);
        foreach ($fields as $key => $field) {
            if (isset($field->attribute) && !$this->isTranslatableAttribute($field->attribute)) {
                unset($fields[$key]);
            }
        }

        return $this->addTranslationToggleField(
            $fields,
            $request
        );
    }

    protected function addTranslationToggleField(array $fields, NovaRequest $request)
    {
        return array_merge([
            LanguageToggler::make(__('Select language')),
        ], $fields);
    }

    abstract public function translatableFields(NovaRequest $request);
}
