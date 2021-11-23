<?php

namespace Marshmallow\Translatable;

use Error;
use App\Nova\Resource;
use Eminiarts\Tabs\Tabs;

class TranslatableTabs
{
    protected function noEditModeAvailable()
    {
        return !request()->has('editMode');
    }

    protected function createModeActive()
    {
        return (request()->has('editMode') && 'create' == request()->editMode);
    }

    protected function shouldLoadNormalTabs(Resource $resource)
    {
        return $resource->weAreNotTranslating() || $this->noEditModeAvailable() || $this->createModeActive();
    }

    public function make(Resource $resource, string $name, array $tabs): Tabs
    {
        if ($this->shouldLoadNormalTabs($resource)) {
            return new Tabs($name, $tabs);
        }

        foreach ($tabs as $tab_name => $fields) {
            foreach ($fields as $key => $field) {

                try {
                    if (isset($field->attribute)) {
                        $attribute = $field->attribute;
                        if (!$resource->isTranslatableAttribute($attribute)) {
                            unset($tabs[$tab_name][$key]);
                        }
                    }
                } catch (Error $e) {
                    //
                }
            }
        }

        return new Tabs($name, $tabs);
    }
}
