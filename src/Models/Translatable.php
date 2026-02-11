<?php

namespace Marshmallow\Translatable\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Translatable extends Model
{
    protected $table = 'translatables';

    protected $casts = [
        'is_locked' => 'boolean',
    ];

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForModel(Builder $query, Model $model): void
    {
        $query->where('translatable_type', $model->getMorphClass())
            ->where('translatable_id', $model->getKey());
    }

    public function scopeForField(Builder $query, string $field): void
    {
        $query->where('field', $field);
    }

    public function scopeForLanguage(Builder $query, string|int $language): void
    {
        if (is_string($language)) {
            $query->whereHas('language', fn ($q) => $q->where('code', $language));
        } else {
            $query->where('language_id', $language);
        }
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

    public function getValue(): mixed
    {
        $value = $this->value;

        if ($value === null) {
            return null;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return $value;
    }

    public function setValue(mixed $value): void
    {
        if (is_array($value)) {
            $this->value = json_encode($value);
        } else {
            $this->value = $value;
        }
    }
}
