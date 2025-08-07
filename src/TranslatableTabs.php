<?php

namespace Marshmallow\Translatable;

use Error;
use App\Nova\Resource;
use Laravel\Nova\Tabs\Tab;

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

    public function make(Resource $resource, string $name, array $tabs)
    {
        if ($this->shouldLoadNormalTabs($resource)) {
            return $this->createTabGroup($name, $tabs);
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

        return $this->createTabGroup($name, $tabs);
    }

    protected function createTabGroup(string $name, array $tabs)
    {
        $novaTabs = [];

        foreach ($tabs as $tabName => $fields) {
            if (is_array($fields)) {
                $novaTabs[] = Tab::make($tabName, $fields);
            } else {
                $novaTabs[] = Tab::make($fields->name, [
                    $fields,
                ]);
            }
        }

        return Tab::group($name, $novaTabs);
    }
}
