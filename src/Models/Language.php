<?php

namespace Marshmallow\Translatable\Models;

use Illuminate\Database\Eloquent\Model;
use Marshmallow\Translatable\Traits\Translatable;

class Language extends Model
{
    use Translatable;

    protected $guarded = [];

    public function translatableColumns(): array
    {
        return [
            'name',
        ];
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function (Model $language) {
            /**
             * Delete the existing seoable information.
             */
            $language->translations()->delete();
        });
    }

    public function getIcon()
    {
        if (! $this->icon) {
            return $this->getNoIconAvailableImage();
        }

        return asset("storage/$this->icon");
    }

    public function isDefault()
    {
        return ($this->language === config('app.locale'));
    }

    public function currentlySelected()
    {
        return ($this->language == request()->getTranslatableLocale());
    }

    public function setTranslatableLocaleRoute()
    {
        return route('set-translatable-locale', $this);
    }

    public function isClickable()
    {
        if (request()->has('editMode') && request()->editMode == 'create') {
            return false;
        }

        return true;
    }

    public static function currentTranslatableModel()
    {
        return self::where('language', request()->getTranslatableLocale())->first();
    }

    public static function currentTranslatableIsDefault()
    {
        return (request()->getTranslatableLocale() === config('app.locale'));
    }

    protected function getBase64StringFromImage(string $image_location): string
    {
        return 'data:image/png;base64,' . base64_encode(file_get_contents($image_location));
    }

    protected function getPrepackedImagePath(string $image):string
    {
        return __dir__ . '/../../resources/flags/' . strtoupper($image) . '.png';
    }

    protected function getNoIconAvailableImage(): string
    {
        $prepacked_image_location = $this->getPrepackedImagePath($this->language);
        if (file_exists($prepacked_image_location)) {
            return $this->getBase64StringFromImage($prepacked_image_location);
        }

        /**
         * No pre packed flag icon is available so we return the none available image.
         */
        return $this->getBase64StringFromImage(
            $this->getPrepackedImagePath('UNKNOWN')
        );
    }

    public function getPreset()
    {
        return $this->translations()->where('value', '!=', '')->get()->map(function ($user) {
            return collect($user->toArray())
                        ->only(['group', 'key', 'value'])
                        ->all();
        })->toArray();
    }

    public function translations()
    {
        return $this->hasMany(Translation::class);
    }
}
