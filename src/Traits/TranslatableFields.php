<?php

namespace Marshmallow\Translatable\Traits;

use Illuminate\Http\Request;
use Marshmallow\Translatable\Fields\LanguageToggler;

trait TranslatableFields
{
    use Translatable;

    public function fields(Request $request)
    {
        /**
         * Only add the translation block if it is activated.
         */
        if (!config('translatable.nova_translatable_fields')) {
            return $this->translatableFields($request);
        }

        if (method_exists($this, 'translatableFieldsEnabled') && !$this->translatableFieldsEnabled()) {
            return $this->translatableFields($request);
        }

        if ($this->weAreNotTranslating() || ($request->has('editMode') && 'create' == $request->editMode)) {
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

    protected function addTranslationToggleField(array $fields, Request $request)
    {
        return array_merge([
            LanguageToggler::make(__('Select language')),
        ], $fields);
    }

    abstract public function translatableFields(Request $request);
}
