<?php

namespace Marshmallow\Translatable\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Language extends Model
{
    protected $casts = [
        'active' => 'boolean',
        'active_for_translations' => 'boolean',
        'sequence' => 'integer',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Language $language) {
            $language->translations()->delete();
        });
    }

    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class);
    }

    public function groupedTranslations(): HasMany
    {
        return $this->hasMany(Translation::class)
            ->where('group', '!=', 'single')
            ->whereNotNull('group');
    }

    public function singleTranslations(): HasMany
    {
        return $this->hasMany(Translation::class)
            ->where('group', 'single');
    }

    public function scopeActive(Builder $builder): void
    {
        $builder->where('active', true);
    }

    public function scopeActiveForTranslations(Builder $builder): void
    {
        $builder->where('active_for_translations', true);
    }

    public function scopeOrdered(Builder $builder): void
    {
        $builder->orderBy('sequence');
    }

    public function scopeIgnoreDefault(Builder $builder): void
    {
        $builder->where('code', '!=', config('translatable.default_language', 'en'));
    }

    public function isDefault(): bool
    {
        return $this->code === config('translatable.default_language', 'en');
    }

    public function translationCount(): Attribute
    {
        return Attribute::get(fn () => $this->translations()->count());
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field === 'code' || $field === null) {
            return static::where('code', $value)->first();
        }

        return parent::resolveRouteBinding($value, $field);
    }

    public function getIconUrl(): ?string
    {
        if (! $this->icon) {
            return $this->getFallbackIcon();
        }

        return asset("storage/{$this->icon}");
    }

    protected function getFallbackIcon(): ?string
    {
        $flagPath = __DIR__ . '/../../resources/flags/' . strtoupper($this->code) . '.png';

        if (file_exists($flagPath)) {
            return 'data:image/png;base64,' . base64_encode(file_get_contents($flagPath));
        }

        $unknownPath = __DIR__ . '/../../resources/flags/UNKNOWN.png';

        if (file_exists($unknownPath)) {
            return 'data:image/png;base64,' . base64_encode(file_get_contents($unknownPath));
        }

        return null;
    }

    public static function findByCode(string $code): ?static
    {
        return static::where('code', $code)->first();
    }

    public static function getDefault(): ?static
    {
        return static::findByCode(config('translatable.default_language', 'en'));
    }
}
