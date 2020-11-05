<?php

namespace Marshmallow\Translatable\Traits;

use Illuminate\Http\Request;
use Marshmallow\Translatable\Fields\LanguageToggler;

trait TranslatableFields
{
    use Translatable;

    public function fields(Request $request)
    {
        if ($this->weAreNotTranslating() || ($request->has('editMode') && 'create' == $request->editMode)) {
            return $this->addTranslationTogglerField(
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

        return $this->addTranslationTogglerField(
            $fields,
            $request
        );
    }

    protected function addTranslationTogglerField(array $fields, Request $request)
    {
        return array_merge([
            LanguageToggler::make(__('Select language')),
        ], $fields);
    }

    abstract public function translatableFields(Request $request);
}
