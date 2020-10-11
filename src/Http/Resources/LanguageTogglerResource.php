<?php

namespace Marshmallow\Translatable\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LanguageTogglerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'name' => $this->name,
            'language' => $this->language,
            'icon' => $this->getIcon(),
            'currently_selected' => $this->currentlySelected(),
            'is_default' => $this->isDefault(),
            'is_clickable' => $this->isClickable(),
            'toggle_path' => $this->setTranslatableLocaleRoute(),
        ];
    }
}
