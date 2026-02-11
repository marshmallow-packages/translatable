<?php

namespace Marshmallow\Translatable\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Translation extends Model
{
    protected $casts = [
        'is_locked' => 'boolean',
        'imported_at' => 'datetime',
    ];

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function scopeForLanguage(Builder $query, string|int $language): void
    {
        if (is_string($language)) {
            $query->whereHas('language', fn ($q) => $q->where('code', $language));
        } else {
            $query->where('language_id', $language);
        }
    }

    public function scopeForGroup(Builder $query, string $group): void
    {
        $query->where('group', $group);
    }

    public function scopeForKey(Builder $query, string $key): void
    {
        $query->where('key', $key);
    }

    public function scopeForContext(Builder $query, ?string $context): void
    {
        if ($context === null) {
            $query->whereNull('context');
        } else {
            $query->where('context', $context);
        }
    }

    public function scopeGrouped(Builder $query): void
    {
        $query->where('group', '!=', 'single')
            ->whereNotNull('group');
    }

    public function scopeSingle(Builder $query): void
    {
        $query->where('group', 'single');
    }

    public function scopeLocked(Builder $query): void
    {
        $query->where('is_locked', true);
    }

    public function scopeUnlocked(Builder $query): void
    {
        $query->where('is_locked', false);
    }

    public function scopeFromSource(Builder $query, string $source): void
    {
        $query->where('source', $source);
    }

    public function scopeMissing(Builder $query): void
    {
        $query->where(function ($q) {
            $q->whereNull('value')
                ->orWhere('value', '');
        });
    }

    public function scopeTranslated(Builder $query): void
    {
        $query->whereNotNull('value')
            ->where('value', '!=', '');
    }

    public static function getGroups(?string $languageCode = null): \Illuminate\Support\Collection
    {
        $query = static::query()
            ->whereNotNull('group')
            ->where('group', '!=', 'single')
            ->select('group')
            ->distinct();

        if ($languageCode) {
            $query->whereHas('language', fn ($q) => $q->where('code', $languageCode));
        }

        return $query->pluck('group');
    }

    public static function getContexts(?string $group = null): \Illuminate\Support\Collection
    {
        $query = static::query()
            ->whereNotNull('context')
            ->select('context')
            ->distinct();

        if ($group) {
            $query->where('group', $group);
        }

        return $query->pluck('context');
    }

    public function lock(): bool
    {
        return $this->update(['is_locked' => true]);
    }

    public function unlock(): bool
    {
        return $this->update(['is_locked' => false]);
    }

    public function isLocked(): bool
    {
        return $this->is_locked === true;
    }

    public function markAsManual(): bool
    {
        return $this->update(['source' => 'manual']);
    }

    public function markAsImported(string $source): bool
    {
        return $this->update([
            'source' => $source,
            'imported_at' => now(),
        ]);
    }
}
