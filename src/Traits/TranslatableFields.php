<?php

namespace Marshmallow\Translatable\Traits;

use Laravel\Nova\Http\Requests\NovaRequest;
use Illuminate\Http\Request;
use Marshmallow\Translatable\Fields\LanguageToggler;

trait TranslatableFields
{
    use Translatable;

    protected bool $force_translating_status = false;

    public function forceTranslating()
    {
        $this->force_translating_status = true;
        return $this;
    }

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

        if ($this->weAreNotTranslating() || $request->editMode === 'create') {
            return $this->addTranslationToggleField(
                $this->translatableFields($request),
                $request
            );
        }

        $fields = $this->translatableFields($request);

        foreach ($fields as $key => $field) {

            if (is_object($field) && get_class($field) == 'Laravel\Nova\Tabs\TabsGroup') {
                $translatable_data_fields = [];
                foreach ($field->data as $data_field) {
                    if (isset($data_field->attribute) && $this->isTranslatableAttribute($data_field->attribute)) {
                        $translatable_data_fields[] = $data_field;
                    }
                }

                $field->data = $translatable_data_fields;
                $fields[$key] = $field;
                continue;
            }

            if (isset($field->attribute) && !$this->isTranslatableAttribute($field->attribute)) {
                unset($fields[$key]);
            }
        }


        return $this->addTranslationToggleField(
            $fields,
            $request
        );
    }

    public function translatableTabFields(array $fields): array
    {
        if (!request()->has('editMode')) {
            return $fields;
        }

        /**
         * Only add the translation block if it is activated.
         */
        if (!config('translatable.nova_translatable_fields')) {
            return $fields;
        }

        if (method_exists($this, 'translatableFieldsEnabled') && !$this->translatableFieldsEnabled()) {
            return $fields;
        }

        if ($this->weAreNotTranslating() || request()->editMode == 'create') {
            return $fields;
        }

        foreach ($fields as $key => $field) {
            if (isset($field->attribute) && !$this->isTranslatableAttribute($field->attribute)) {
                unset($fields[$key]);
            }
        }
        return $fields;
    }

    protected function addTranslationToggleField(array $fields, NovaRequest $request)
    {
        return array_merge([
            LanguageToggler::make(__('Select language')),
        ], $fields);
    }

    abstract public function translatableFields(NovaRequest $request);
}
