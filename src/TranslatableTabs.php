<?php

namespace Marshmallow\Translatable;

use App\Nova\Resource;
use Eminiarts\Tabs\Tabs;

class TranslatableTabs
{
    public function make(Resource $resource, string $name, array $tabs): Tabs
    {
        if ($resource->weAreNotTranslating() || (request()->has('editMode') && 'create' == request()->editMode)) {
            return new Tabs($name, $tabs);
        }

        foreach ($tabs as $tab_name => $fields) {
            foreach ($fields as $key => $field) {
                if (isset($field->attribute)) {
                    $attribute = $field->attribute;
                    if (!$resource->isTranslatableAttribute($attribute)) {
                        unset($tabs[$tab_name][$key]);
                    }
                }
            }
        }
        return new Tabs($name, $tabs);
    }
}
