<?php

namespace Marshmallow\Translatable\Fields;

use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;
use Marshmallow\Translatable\Http\Resources\LanguageTogglerResource;
use Marshmallow\Translatable\Models\Language;

class LanguageToggler extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'language-toggle-field';

    /**
     * By default hide on index.
     *
     * @var bool
     */
    public $showOnIndex = false;

    /**
     * Get additional meta information to merge with the element payload.
     *
     * @return array
     */
    public function meta()
    {
        return array_merge([
            'languages' => LanguageTogglerResource::collection(Language::get()),
            'toggler_clickable' => $this->getToggleClickableStatus(),
            'toggler_notification' => $this->getToggleNotification(),
        ], parent::meta());
    }

    protected function fillAttribute(NovaRequest $request, $requestAttribute, $model, $attribute)
    {
    }

    protected function getToggleClickableStatus()
    {
        return !(request()->has('editMode') && 'create' == request()->editMode);
    }

    protected function getToggleNotification()
    {
        if ($this->getToggleClickableStatus()) {
            return null;
        }

        if (Language::currentTranslatableIsDefault()) {
            return null;
        }

        return __('
    		<strong>Please note:</strong> You have currently selected a different language in your translation settings. However, you are now creating a new resource. This needs to be done in the default language which is :language. So please keep this in mind while you fill in the field below.
    	', [
            'language' => config('app.locale'),
        ]);
    }
}
