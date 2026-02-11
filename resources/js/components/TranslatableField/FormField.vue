<template>
    <DefaultField
        :field="field"
        :errors="errors"
        :show-help-text="showHelpText"
        :full-width-content="fullWidthContent"
    >
        <template #field>
            <div class="translatable-field">
                <div class="language-tabs">
                    <button
                        v-for="language in field.languages"
                        :key="language.code"
                        type="button"
                        class="language-tab"
                        :class="{ active: activeLanguage === language.code }"
                        @click="setActiveLanguage(language.code)"
                    >
                        <img
                            v-if="language.icon"
                            :src="language.icon"
                            :alt="language.name"
                            class="language-icon"
                        />
                        <span class="language-code">{{ language.code.toUpperCase() }}</span>
                        <span
                            v-if="language.code === field.defaultLanguage"
                            class="default-badge"
                            title="Default language"
                        >●</span>
                    </button>
                </div>

                <div class="field-content">
                    <component
                        :is="'form-' + field.innerField.component"
                        :field="innerFieldForLanguage"
                        :errors="errors"
                        :resource-name="resourceName"
                        :resource-id="resourceId"
                        :via-resource="viaResource"
                        :via-resource-id="viaResourceId"
                        :via-relationship="viaRelationship"
                        @input="handleInput"
                    />
                </div>
            </div>
        </template>
    </DefaultField>
</template>

<script>
import { FormField, HandlesValidationErrors } from 'laravel-nova'

export default {
    mixins: [FormField, HandlesValidationErrors],

    props: ['resourceName', 'resourceId', 'field'],

    data() {
        return {
            activeLanguage: null,
            translations: {},
        }
    },

    computed: {
        innerFieldForLanguage() {
            const value = this.translations[this.activeLanguage] || ''

            return {
                ...this.field.innerField,
                value: value,
                attribute: `${this.field.attribute}_${this.activeLanguage}`,
            }
        },
    },

    mounted() {
        this.activeLanguage = this.field.defaultLanguage

        if (this.field.translations) {
            this.translations = { ...this.field.translations }
        }

        if (this.field.defaultValue && !this.translations[this.field.defaultLanguage]) {
            this.translations[this.field.defaultLanguage] = this.field.defaultValue
        }
    },

    methods: {
        setInitialValue() {
            this.value = this.field.value || ''
        },

        setActiveLanguage(code) {
            this.activeLanguage = code
        },

        handleInput(value) {
            this.translations[this.activeLanguage] = value
        },

        fill(formData) {
            formData.append(
                `${this.field.attribute}_translations`,
                JSON.stringify(this.translations)
            )
        },
    },
}
</script>

<style scoped>
.translatable-field {
    width: 100%;
}

.language-tabs {
    display: flex;
    gap: 0.25rem;
    margin-bottom: 0.75rem;
    border-bottom: 1px solid var(--border-color, #e5e7eb);
    padding-bottom: 0.5rem;
}

.language-tab {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.375rem 0.75rem;
    border: 1px solid transparent;
    border-radius: 0.375rem;
    background: transparent;
    cursor: pointer;
    font-size: 0.875rem;
    color: var(--text-color, #6b7280);
    transition: all 0.15s ease;
}

.language-tab:hover {
    background: var(--bg-hover, #f3f4f6);
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

.field-content {
    width: 100%;
}
</style>
