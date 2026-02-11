<template>
    <div class="filter-bar">
        <div class="filter-row">
            <div class="filter-group">
                <label class="filter-label">Group</label>
                <select
                    class="filter-select"
                    :value="selectedGroup"
                    @input="$emit('update:selectedGroup', $event.target.value)"
                >
                    <option value="">All Groups</option>
                    <option v-for="group in groups" :key="group" :value="group">
                        {{ group }}
                    </option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Search</label>
                <input
                    type="text"
                    class="filter-input"
                    placeholder="Search keys or values..."
                    :value="search"
                    @input="$emit('update:search', $event.target.value)"
                />
            </div>

            <div class="filter-group">
                <label class="filter-checkbox">
                    <input
                        type="checkbox"
                        :checked="missingOnly"
                        @change="$emit('update:missingOnly', $event.target.checked)"
                    />
                    <span>Missing only</span>
                </label>
            </div>
        </div>

        <div class="filter-actions">
            <button
                type="button"
                class="btn btn-secondary"
                @click="$emit('scan')"
            >
                Scan Project
            </button>

            <div class="translate-dropdown" v-if="configuredTranslators.length > 0">
                <button
                    type="button"
                    class="btn btn-primary"
                    @click="showTranslateMenu = !showTranslateMenu"
                >
                    AI Translate Selected
                    <svg class="dropdown-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>

                <div v-if="showTranslateMenu" class="dropdown-menu">
                    <div class="dropdown-section">
                        <label class="dropdown-label">From</label>
                        <select v-model="translateFrom" class="dropdown-select">
                            <option v-for="lang in languages" :key="lang.code" :value="lang.code">
                                {{ lang.name }} ({{ lang.code }})
                            </option>
                        </select>
                    </div>

                    <div class="dropdown-section">
                        <label class="dropdown-label">To</label>
                        <select v-model="translateTo" class="dropdown-select">
                            <option v-for="lang in languages" :key="lang.code" :value="lang.code">
                                {{ lang.name }} ({{ lang.code }})
                            </option>
                        </select>
                    </div>

                    <div class="dropdown-section">
                        <label class="dropdown-label">Using</label>
                        <select v-model="selectedTranslator" class="dropdown-select">
                            <option
                                v-for="translator in configuredTranslators"
                                :key="translator.identifier"
                                :value="translator.identifier"
                            >
                                {{ translator.name }}
                            </option>
                        </select>
                    </div>

                    <button
                        type="button"
                        class="btn btn-primary w-full"
                        @click="executeTranslate"
                    >
                        Translate
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        groups: Array,
        languages: Array,
        translators: Array,
        defaultLanguage: String,
        selectedGroup: String,
        search: String,
        missingOnly: Boolean,
    },

    emits: ['update:selectedGroup', 'update:search', 'update:missingOnly', 'scan', 'translate-selected'],

    data() {
        return {
            showTranslateMenu: false,
            translateFrom: '',
            translateTo: '',
            selectedTranslator: '',
        }
    },

    computed: {
        configuredTranslators() {
            return this.translators.filter(t => t.configured)
        },
    },

    watch: {
        languages: {
            immediate: true,
            handler(langs) {
                if (langs.length > 0) {
                    this.translateFrom = this.defaultLanguage || langs[0].code
                    this.translateTo = langs.find(l => l.code !== this.translateFrom)?.code || ''
                }
            },
        },
        configuredTranslators: {
            immediate: true,
            handler(translators) {
                if (translators.length > 0 && !this.selectedTranslator) {
                    this.selectedTranslator = translators[0].identifier
                }
            },
        },
    },

    methods: {
        executeTranslate() {
            this.$emit('translate-selected', this.selectedTranslator, this.translateFrom, this.translateTo)
            this.showTranslateMenu = false
        },
    },
}
</script>

<style scoped>
.filter-bar {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.filter-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: rgba(var(--colors-gray-500));
}

.filter-select,
.filter-input {
    padding: 0.5rem 0.75rem;
    border: 1px solid rgba(var(--colors-gray-300));
    border-radius: 0.375rem;
    font-size: 0.875rem;
    min-width: 150px;
}

.filter-checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    padding: 0.5rem 0;
}

.filter-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.btn {
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.btn-secondary {
    background: rgba(var(--colors-gray-100));
    border: 1px solid rgba(var(--colors-gray-300));
    color: rgba(var(--colors-gray-700));
}

.btn-secondary:hover {
    background: rgba(var(--colors-gray-200));
}

.btn-primary {
    background: rgb(var(--colors-primary-500));
    border: 1px solid rgb(var(--colors-primary-500));
    color: white;
}

.btn-primary:hover {
    background: rgb(var(--colors-primary-600));
}

.translate-dropdown {
    position: relative;
}

.dropdown-icon {
    width: 1rem;
    height: 1rem;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 0.25rem;
    padding: 1rem;
    background: white;
    border: 1px solid rgba(var(--colors-gray-200));
    border-radius: 0.5rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    min-width: 200px;
    z-index: 50;
}

.dropdown-section {
    margin-bottom: 0.75rem;
}

.dropdown-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 500;
    color: rgba(var(--colors-gray-500));
    margin-bottom: 0.25rem;
}

.dropdown-select {
    width: 100%;
    padding: 0.375rem 0.5rem;
    border: 1px solid rgba(var(--colors-gray-300));
    border-radius: 0.25rem;
    font-size: 0.875rem;
}

.w-full {
    width: 100%;
}
</style>
