<template>
    <table class="translation-table">
        <thead>
            <tr>
                <th class="checkbox-col">
                    <input
                        type="checkbox"
                        :checked="allSelected"
                        @change="toggleSelectAll"
                    />
                </th>
                <th class="key-col">Key</th>
                <th
                    v-for="language in languages"
                    :key="language.code"
                    class="value-col"
                >
                    <div class="language-header">
                        <img
                            v-if="language.icon"
                            :src="language.icon"
                            :alt="language.name"
                            class="language-icon"
                        />
                        <span>{{ language.code.toUpperCase() }}</span>
                        <span v-if="language.code === defaultLanguage" class="default-badge">●</span>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr v-if="loading">
                <td :colspan="languages.length + 2" class="loading-cell">
                    Loading translations...
                </td>
            </tr>
            <tr v-else-if="rows.length === 0">
                <td :colspan="languages.length + 2" class="empty-cell">
                    No translations found
                </td>
            </tr>
            <tr v-for="row in rows" :key="row.fullKey" class="translation-row">
                <td class="checkbox-col">
                    <input
                        type="checkbox"
                        :checked="isSelected(row)"
                        @change="toggleRowSelect(row)"
                    />
                </td>
                <td class="key-col">
                    <div class="key-info">
                        <span class="key-group">{{ row.group }}</span>
                        <span class="key-name">{{ row.key }}</span>
                        <span v-if="row.context" class="key-context">[{{ row.context }}]</span>
                    </div>
                </td>
                <td
                    v-for="language in languages"
                    :key="language.code"
                    class="value-col"
                >
                    <TranslationCell
                        :value="row.values[language.code]"
                        :language="language"
                        :is-default="language.code === defaultLanguage"
                        @update="(value) => handleCellUpdate(row, language.code, value)"
                        @lock="handleCellLock(row, language.code)"
                        @unlock="handleCellUnlock(row, language.code)"
                        @translate="(driver) => handleCellTranslate(row, language.code, driver)"
                    />
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script>
import TranslationCell from './TranslationCell.vue'

export default {
    components: {
        TranslationCell,
    },

    props: {
        rows: Array,
        languages: Array,
        defaultLanguage: String,
        loading: Boolean,
        selectedIds: Array,
    },

    emits: ['update', 'lock', 'unlock', 'translate', 'toggle-select', 'select-all'],

    computed: {
        allSelected() {
            if (this.rows.length === 0) return false

            const allIds = this.getAllSelectableIds()

            return allIds.length > 0 && allIds.every(id => this.selectedIds.includes(id))
        },
    },

    methods: {
        isSelected(row) {
            for (const lang of this.languages) {
                const val = row.values[lang.code]

                if (val?.id && this.selectedIds.includes(val.id)) {
                    return true
                }
            }

            return false
        },

        toggleRowSelect(row) {
            for (const lang of this.languages) {
                const val = row.values[lang.code]

                if (val?.id && !val.is_locked) {
                    this.$emit('toggle-select', val.id)
                }
            }
        },

        toggleSelectAll() {
            if (this.allSelected) {
                this.$emit('select-all', [])
            } else {
                this.$emit('select-all', this.getAllSelectableIds())
            }
        },

        getAllSelectableIds() {
            const ids = []

            for (const row of this.rows) {
                for (const lang of this.languages) {
                    const val = row.values[lang.code]

                    if (val?.id && !val.is_locked) {
                        ids.push(val.id)
                    }
                }
            }

            return ids
        },

        handleCellUpdate(row, langCode, value) {
            const translation = row.values[langCode]

            if (translation?.id) {
                this.$emit('update', translation.id, value)
            }
        },

        handleCellLock(row, langCode) {
            const translation = row.values[langCode]

            if (translation?.id) {
                this.$emit('lock', translation.id)
            }
        },

        handleCellUnlock(row, langCode) {
            const translation = row.values[langCode]

            if (translation?.id) {
                this.$emit('unlock', translation.id)
            }
        },

        handleCellTranslate(row, langCode, driver) {
            const translation = row.values[langCode]

            if (translation?.id) {
                this.$emit('translate', translation.id, driver, this.defaultLanguage, langCode)
            }
        },
    },
}
</script>

<style scoped>
.translation-table {
    width: 100%;
    border-collapse: collapse;
}

.translation-table th,
.translation-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid rgba(var(--colors-gray-200));
}

.translation-table th {
    background: rgba(var(--colors-gray-50));
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    color: rgba(var(--colors-gray-500));
}

.checkbox-col {
    width: 40px;
}

.key-col {
    min-width: 200px;
    max-width: 300px;
}

.value-col {
    min-width: 200px;
}

.language-header {
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.language-icon {
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
}

.default-badge {
    color: rgb(var(--colors-primary-500));
    font-size: 0.625rem;
}

.loading-cell,
.empty-cell {
    text-align: center;
    padding: 2rem;
    color: rgba(var(--colors-gray-500));
}

.translation-row:hover {
    background: rgba(var(--colors-gray-50));
}

.key-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.key-group {
    font-size: 0.625rem;
    color: rgba(var(--colors-gray-400));
    text-transform: uppercase;
}

.key-name {
    font-size: 0.875rem;
    font-weight: 500;
    color: rgba(var(--colors-gray-900));
}

.key-context {
    font-size: 0.75rem;
    color: rgba(var(--colors-primary-500));
}
</style>
