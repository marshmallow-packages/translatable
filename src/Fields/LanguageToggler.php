<?php

namespace Marshmallow\Translatable\Fields;

use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;
use Marshmallow\Translatable\Facades\Translatable;
use Marshmallow\Translatable\Http\Resources\LanguageTogglerResource;

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
        $languages = LanguageTogglerResource::collection(config('translatable.models.language')::where('active_for_translation', true)->orderBy('translatable_sequence')->get());

        $source_language = Translatable::appDefaultLanguage();
        $target_language = request()->getTranslatableLocale();

        return array_merge([
            'languages' => $languages,
            'toggler_clickable' => $this->getToggleClickableStatus(),
            'toggler_notification' => $this->getToggleNotification(),
            'translating' => $source_language != $target_language,
            'source' => $source_language,
            'target' => $target_language,
        ], parent::meta());
    }

    protected function fillAttribute(NovaRequest $request, $requestAttribute, $model, $attribute) {}

    protected function getToggleClickableStatus()
    {
        return !(request()->has('editMode') && 'create' == request()->editMode);
    }

    protected function getToggleNotification()
    {
        if ($this->getToggleClickableStatus()) {
            return null;
        }

        if (config('translatable.models.language')::currentTranslatableIsDefault()) {
            return null;
        }

        return __('
    		<strong>Please note:</strong> You have currently selected a different language in your translation settings. However, you are now creating a new resource. This needs to be done in the default language which is :language. So please keep this in mind while you fill in the field below.
    	', [
            'language' => Translatable::appDefaultLanguage(),
        ]);
    }
}
