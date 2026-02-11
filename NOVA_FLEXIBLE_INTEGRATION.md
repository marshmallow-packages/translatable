# Nova Flexible Translation Integration

This document provides instructions for implementing translation support in the `marshmallow/nova-flexible` package to work seamlessly with the `marshmallow/translatable` package.

## Overview

The goal is to allow specific fields within Flexible layouts to be translatable, while keeping the overall structure (layouts, order, non-translatable fields) shared across all languages.

### Key Concepts

1. **Shared Structure**: All languages have the same layouts in the same order
2. **Selective Translation**: Only fields specified in `translatableColumns()` get language tabs
3. **Non-Translatable Fields**: Images, booleans, etc. are shared across all languages

## Required API from Translatable Package

The `marshmallow/translatable` package provides these endpoints:

```
GET /nova-vendor/translatable/languages
```

Returns active languages:
```json
{
    "languages": [
        {"code": "en", "name": "English", "icon": "/path/to/flag.png"},
        {"code": "nl", "name": "Dutch", "icon": "/path/to/flag.png"}
    ],
    "defaultLanguage": "en"
}
```

## Storage Format Changes

### Current Format (Without Translation)

```json
{
    "layouts": [
        {
            "key": "hero",
            "attributes": {
                "title": "Welcome",
                "subtitle": "To our site",
                "background": "hero.jpg",
                "active": true
            }
        }
    ]
}
```

### New Format (With Translation)

Translatable fields store values as language-keyed objects:

```json
{
    "layouts": [
        {
            "key": "hero",
            "attributes": {
                "title": {
                    "en": "Welcome",
                    "nl": "Welkom",
                    "de": "Willkommen"
                },
                "subtitle": {
                    "en": "To our site",
                    "nl": "Op onze site"
                },
                "background": "hero.jpg",
                "active": true
            }
        }
    ]
}
```

## PHP Changes

### 1. Layout Class - translatableColumns() Method

The `translatableColumns()` method already exists in layouts. Ensure it returns an array of attribute names that should be translatable:

```php
// src/Layouts/Layout.php

abstract class Layout
{
    /**
     * Get the translatable columns for this layout.
     * Override in child classes to specify which fields are translatable.
     */
    public function translatableColumns(): array
    {
        return [];
    }

    /**
     * Check if a field is translatable.
     */
    public function isTranslatable(string $attribute): bool
    {
        return in_array($attribute, $this->translatableColumns());
    }
}
```

### 2. Example Layout Implementation

```php
// In user's project
class HeroLayout extends Layout
{
    protected $name = 'hero';

    public function translatableColumns(): array
    {
        return ['title', 'subtitle', 'content'];
    }

    public function fields()
    {
        return [
            Text::make('Title'),        // Translatable - gets language tabs
            Text::make('Subtitle'),     // Translatable - gets language tabs
            Tiptap::make('Content'),    // Translatable - gets language tabs
            Image::make('Background'),  // NOT translatable - shared
            Boolean::make('Active'),    // NOT translatable - shared
        ];
    }
}
```

### 3. Flexible Field Serialization

Update the `Flexible` field to include translation metadata:

```php
// src/Flexible.php

public function jsonSerialize(): array
{
    return array_merge(parent::jsonSerialize(), [
        'layouts' => $this->prepareLayoutsForSerialization(),
        'translationConfig' => $this->getTranslationConfig(),
    ]);
}

protected function getTranslationConfig(): ?array
{
    if (! $this->hasTranslatableLayouts()) {
        return null;
    }

    // Fetch from translatable package API
    return [
        'enabled' => true,
        'apiEndpoint' => '/nova-vendor/translatable/languages',
    ];
}

protected function hasTranslatableLayouts(): bool
{
    foreach ($this->layouts as $layout) {
        if (! empty($layout->translatableColumns())) {
            return true;
        }
    }

    return false;
}
```

### 4. Layout Serialization

Include translatableColumns in the layout serialization:

```php
// src/Layouts/Layout.php

public function jsonSerialize(): array
{
    return [
        'name' => $this->name(),
        'title' => $this->title(),
        'fields' => $this->fields(),
        'translatableColumns' => $this->translatableColumns(),
        // ... other properties
    ];
}
```

### 5. Value Resolution

When resolving values for display, handle the new format:

```php
// src/Value/FlexibleCast.php or similar

protected function resolveAttribute($attribute, $value, $locale = null)
{
    // If value is array with language keys, extract the right one
    if (is_array($value) && $this->isLanguageKeyed($value)) {
        $locale = $locale ?? app()->getLocale();
        return $value[$locale] ?? $value[config('translatable.default_language')] ?? null;
    }

    return $value;
}

protected function isLanguageKeyed(array $value): bool
{
    // Check if keys are language codes (2-3 letter codes)
    $keys = array_keys($value);

    foreach ($keys as $key) {
        if (! preg_match('/^[a-z]{2,3}$/', $key)) {
            return false;
        }
    }

    return true;
}
```

## Vue Component Changes

### 1. Main FormField.vue

Location: `resources/js/components/FormField.vue`

Add language fetching and tracking:

```vue
<script>
export default {
    data() {
        return {
            languages: [],
            defaultLanguage: 'en',
            activeLanguagePerField: {},
        }
    },

    async mounted() {
        if (this.field.translationConfig?.enabled) {
            await this.fetchLanguages()
        }
    },

    methods: {
        async fetchLanguages() {
            try {
                const response = await Nova.request().get(
                    this.field.translationConfig.apiEndpoint
                )
                this.languages = response.data.languages
                this.defaultLanguage = response.data.defaultLanguage
            } catch (error) {
                console.error('Failed to fetch languages:', error)
            }
        },

        getActiveLanguage(layoutKey, fieldAttribute) {
            const key = `${layoutKey}.${fieldAttribute}`
            return this.activeLanguagePerField[key] || this.defaultLanguage
        },

        setActiveLanguage(layoutKey, fieldAttribute, languageCode) {
            const key = `${layoutKey}.${fieldAttribute}`
            this.$set(this.activeLanguagePerField, key, languageCode)
        },
    },
}
</script>
```

### 2. New Component: LanguageTabs.vue

Create a new component for language tabs:

Location: `resources/js/components/LanguageTabs.vue`

```vue
<template>
    <div class="language-tabs-wrapper">
        <div class="language-tabs">
            <button
                v-for="language in languages"
                :key="language.code"
                type="button"
                class="language-tab"
                :class="{ active: activeLanguage === language.code }"
                @click="$emit('change', language.code)"
            >
                <img
                    v-if="language.icon"
                    :src="language.icon"
                    :alt="language.name"
                    class="language-icon"
                />
                <span class="language-code">{{ language.code.toUpperCase() }}</span>
                <span
                    v-if="language.code === defaultLanguage"
                    class="default-badge"
                >●</span>
            </button>
        </div>
        <slot :activeLanguage="activeLanguage" />
    </div>
</template>

<script>
export default {
    props: {
        languages: {
            type: Array,
            required: true,
        },
        activeLanguage: {
            type: String,
            required: true,
        },
        defaultLanguage: {
            type: String,
            required: true,
        },
    },
}
</script>

<style scoped>
.language-tabs-wrapper {
    width: 100%;
}

.language-tabs {
    display: flex;
    gap: 0.25rem;
    margin-bottom: 0.5rem;
    padding-bottom: 0.25rem;
    border-bottom: 1px solid rgba(var(--colors-gray-200));
}

.language-tab {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border: 1px solid transparent;
    border-radius: 0.25rem;
    background: transparent;
    cursor: pointer;
    font-size: 0.75rem;
    color: rgba(var(--colors-gray-500));
    transition: all 0.15s ease;
}

.language-tab:hover {
    background: rgba(var(--colors-gray-100));
}

.language-tab.active {
    background: rgba(var(--colors-primary-500), 0.1);
    border-color: rgba(var(--colors-primary-500), 0.3);
    color: rgb(var(--colors-primary-500));
}

.language-icon {
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
}

.language-code {
    font-weight: 500;
}

.default-badge {
    color: rgb(var(--colors-primary-500));
    font-size: 0.5rem;
}
</style>
```

### 3. Layout Field Rendering

Update the layout field rendering to wrap translatable fields with LanguageTabs:

Location: `resources/js/components/LayoutField.vue` (or wherever fields are rendered within layouts)

```vue
<template>
    <div class="layout-field">
        <!-- If field is translatable, wrap with language tabs -->
        <LanguageTabs
            v-if="isTranslatable && languages.length > 0"
            :languages="languages"
            :active-language="activeLanguage"
            :default-language="defaultLanguage"
            @change="handleLanguageChange"
        >
            <component
                :is="'form-' + field.component"
                :field="fieldForLanguage"
                :errors="errors"
                v-bind="$attrs"
            />
        </LanguageTabs>

        <!-- Non-translatable field renders normally -->
        <component
            v-else
            :is="'form-' + field.component"
            :field="field"
            :errors="errors"
            v-bind="$attrs"
        />
    </div>
</template>

<script>
import LanguageTabs from './LanguageTabs.vue'

export default {
    components: { LanguageTabs },

    props: {
        field: Object,
        layout: Object,
        languages: Array,
        defaultLanguage: String,
        errors: Object,
    },

    data() {
        return {
            activeLanguage: null,
            translations: {},
        }
    },

    computed: {
        isTranslatable() {
            return this.layout.translatableColumns?.includes(this.field.attribute)
        },

        fieldForLanguage() {
            const value = this.translations[this.activeLanguage] || ''

            return {
                ...this.field,
                value: value,
            }
        },
    },

    mounted() {
        this.activeLanguage = this.defaultLanguage
        this.initializeTranslations()
    },

    methods: {
        initializeTranslations() {
            // If existing value is language-keyed, use it
            if (this.isLanguageKeyed(this.field.value)) {
                this.translations = { ...this.field.value }
            } else {
                // Legacy value - assign to default language
                this.translations = {
                    [this.defaultLanguage]: this.field.value
                }
            }
        },

        isLanguageKeyed(value) {
            if (!value || typeof value !== 'object' || Array.isArray(value)) {
                return false
            }

            const keys = Object.keys(value)
            return keys.every(key => /^[a-z]{2,3}$/.test(key))
        },

        handleLanguageChange(code) {
            this.activeLanguage = code
        },

        handleInput(value) {
            this.translations[this.activeLanguage] = value
            this.$emit('update', this.translations)
        },
    },
}
</script>
```

### 4. Value Collection on Save

Update the form submission to collect translatable values properly:

```javascript
// In the layout form field collection logic

collectLayoutAttributes(layout) {
    const attributes = {}

    layout.fields.forEach(field => {
        const isTranslatable = layout.translatableColumns?.includes(field.attribute)

        if (isTranslatable && this.languages.length > 0) {
            // Collect translations object
            attributes[field.attribute] = field.translations || {}
        } else {
            // Regular value
            attributes[field.attribute] = field.value
        }
    })

    return attributes
}
```

## Migration Strategy for Existing Data

Create an artisan command to migrate existing Flexible content to the new format:

```php
// In translatable package or as documentation

class MigrateFlexibleTranslationsCommand extends Command
{
    protected $signature = 'flexible:migrate-translations
                            {model : The model class to migrate}
                            {attribute : The Flexible attribute name}
                            {--default-language=en : The language to use for existing values}';

    public function handle()
    {
        $modelClass = $this->argument('model');
        $attribute = $this->argument('attribute');
        $defaultLanguage = $this->option('default-language');

        $modelClass::query()
            ->whereNotNull($attribute)
            ->chunk(100, function ($models) use ($attribute, $defaultLanguage) {
                foreach ($models as $model) {
                    $flexible = $model->{$attribute};
                    $migrated = $this->migrateFlexibleContent($flexible, $defaultLanguage);
                    $model->{$attribute} = $migrated;
                    $model->save();
                }
            });
    }

    protected function migrateFlexibleContent($content, $defaultLanguage)
    {
        // Implementation to wrap scalar values in language key
    }
}
```

## Testing Checklist

- [ ] Layout with Text field translatable
- [ ] Layout with TipTap/rich text field translatable
- [ ] Layout with non-translatable Image field (should NOT have tabs)
- [ ] Layout with non-translatable Boolean field (should NOT have tabs)
- [ ] Mixed translatable/non-translatable fields in same layout
- [ ] Multiple layouts with different translatable fields
- [ ] Nested layouts (if supported)
- [ ] Detail view shows correct language content
- [ ] Index view truncation works correctly
- [ ] Saving and loading translations works
- [ ] Migration from old format to new format
- [ ] Empty/new layouts start with empty translations
- [ ] Default language pre-fills when other translations are missing

## API Route for Languages

Add this route to the translatable package's Nova routes:

```php
// routes/nova.php

use Illuminate\Support\Facades\Route;
use Marshmallow\Translatable\Models\Language;
use Marshmallow\Translatable\TranslatableConfig;

Route::get('/languages', function () {
    return [
        'languages' => Language::active()
            ->ordered()
            ->get()
            ->map(fn ($lang) => [
                'code' => $lang->code,
                'name' => $lang->name,
                'icon' => $lang->getIconUrl(),
            ]),
        'defaultLanguage' => TranslatableConfig::getDefaultLanguage(),
    ];
});
```

## Summary of Files to Modify in nova-flexible

1. `src/Layouts/Layout.php` - Add `isTranslatable()` method
2. `src/Flexible.php` - Add translation config to JSON serialization
3. `src/Value/FlexibleCast.php` - Handle language-keyed values
4. `resources/js/components/FormField.vue` - Fetch languages, track active language
5. `resources/js/components/LanguageTabs.vue` - NEW: Language tabs component
6. `resources/js/components/LayoutField.vue` - Wrap translatable fields with tabs
7. `webpack.mix.js` or `vite.config.js` - Include new component
