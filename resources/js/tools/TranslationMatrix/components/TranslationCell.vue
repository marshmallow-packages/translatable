<template>
    <div class="translation-cell" :class="{ 'is-locked': value?.is_locked, 'is-empty': !value?.value }">
        <div class="cell-content">
            <textarea
                v-if="editing"
                ref="input"
                v-model="editValue"
                class="cell-input"
                rows="2"
                @blur="save"
                @keydown.enter.ctrl="save"
                @keydown.escape="cancel"
            />
            <div v-else class="cell-value" @dblclick="startEdit">
                <span v-if="value?.value">{{ truncatedValue }}</span>
                <span v-else class="empty-placeholder">—</span>
            </div>
        </div>

        <div class="cell-actions">
            <span v-if="value?.source" class="source-badge" :title="'Source: ' + value.source">
                {{ sourceIcon }}
            </span>

            <button
                v-if="value?.is_locked"
                type="button"
                class="action-btn"
                title="Unlock"
                @click="$emit('unlock')"
            >
                🔒
            </button>
            <button
                v-else-if="value?.id"
                type="button"
                class="action-btn"
                title="Lock"
                @click="$emit('lock')"
            >
                🔓
            </button>

            <button
                v-if="!isDefault && !value?.is_locked"
                type="button"
                class="action-btn translate-btn"
                title="AI Translate"
                @click="showTranslateMenu = !showTranslateMenu"
            >
                🤖
            </button>

            <div v-if="showTranslateMenu" class="translate-menu">
                <button
                    v-for="translator in availableTranslators"
                    :key="translator"
                    type="button"
                    class="translate-option"
                    @click="translate(translator)"
                >
                    {{ translatorNames[translator] || translator }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        value: Object,
        language: Object,
        isDefault: Boolean,
    },

    emits: ['update', 'lock', 'unlock', 'translate'],

    data() {
        return {
            editing: false,
            editValue: '',
            showTranslateMenu: false,
            availableTranslators: ['deepl', 'openai', 'anthropic'],
            translatorNames: {
                deepl: 'DeepL',
                openai: 'OpenAI',
                anthropic: 'Anthropic',
            },
        }
    },

    computed: {
        truncatedValue() {
            const val = this.value?.value || ''

            if (val.length > 100) {
                return val.substring(0, 100) + '...'
            }

            return val
        },

        sourceIcon() {
            const source = this.value?.source

            const icons = {
                manual: '✏️',
                deepl: '🔤',
                openai: '🤖',
                anthropic: '🧠',
                scan: '🔍',
                'laravel-lang': '📦',
                vendor: '📁',
            }

            return icons[source] || '❓'
        },
    },

    methods: {
        startEdit() {
            if (this.value?.is_locked) return

            this.editing = true
            this.editValue = this.value?.value || ''

            this.$nextTick(() => {
                this.$refs.input?.focus()
            })
        },

        save() {
            this.editing = false

            if (this.editValue !== (this.value?.value || '')) {
                this.$emit('update', this.editValue)
            }
        },

        cancel() {
            this.editing = false
            this.editValue = this.value?.value || ''
        },

        translate(driver) {
            this.showTranslateMenu = false
            this.$emit('translate', driver)
        },
    },
}
</script>

<style scoped>
.translation-cell {
    position: relative;
    min-height: 40px;
}

.translation-cell.is-locked {
    background: rgba(var(--colors-gray-100));
}

.translation-cell.is-empty .cell-value {
    color: rgba(var(--colors-gray-400));
}

.cell-content {
    padding-right: 60px;
}

.cell-value {
    cursor: pointer;
    min-height: 24px;
    word-break: break-word;
}

.cell-value:hover {
    background: rgba(var(--colors-primary-50));
}

.empty-placeholder {
    color: rgba(var(--colors-gray-300));
}

.cell-input {
    width: 100%;
    padding: 0.375rem;
    border: 1px solid rgb(var(--colors-primary-500));
    border-radius: 0.25rem;
    font-size: 0.875rem;
    resize: vertical;
}

.cell-actions {
    position: absolute;
    top: 0;
    right: 0;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem;
}

.source-badge {
    font-size: 0.75rem;
    cursor: help;
}

.action-btn {
    padding: 0.125rem 0.25rem;
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 0.875rem;
    opacity: 0.5;
    transition: opacity 0.15s;
}

.action-btn:hover {
    opacity: 1;
}

.translate-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid rgba(var(--colors-gray-200));
    border-radius: 0.375rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    z-index: 10;
    min-width: 100px;
}

.translate-option {
    display: block;
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: none;
    background: transparent;
    text-align: left;
    cursor: pointer;
    font-size: 0.875rem;
}

.translate-option:hover {
    background: rgba(var(--colors-gray-100));
}
</style>
