<template>
    <PanelItem :index="index" :field="field">
        <template #value>
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
                    <p class="translation-value">{{ currentValue || '—' }}</p>
                </div>
            </div>
        </template>
    </PanelItem>
</template>

<script>
export default {
    props: ['index', 'resource', 'resourceName', 'resourceId', 'field'],

    data() {
        return {
            activeLanguage: null,
        }
    },

    computed: {
        currentValue() {
            if (this.activeLanguage === this.field.defaultLanguage) {
                return this.field.defaultValue || this.field.value
            }

            return this.field.translations?.[this.activeLanguage] || ''
        },
    },

    mounted() {
        this.activeLanguage = this.field.defaultLanguage
    },

    methods: {
        setActiveLanguage(code) {
            this.activeLanguage = code
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
    margin-bottom: 0.5rem;
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
    width: 0.875rem;
    height: 0.875rem;
    border-radius: 50%;
}

.language-code {
    font-weight: 500;
}

.default-badge {
    color: rgb(var(--colors-primary-500));
    font-size: 0.5rem;
}

.translation-value {
    color: var(--text-color, #374151);
}
</style>
