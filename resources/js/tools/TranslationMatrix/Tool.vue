<template>
    <div class="translation-matrix">
        <Head title="Translation Matrix" />

        <Heading class="mb-6">Translation Matrix</Heading>

        <Card class="mb-6">
            <div class="p-4">
                <FilterBar
                    :groups="groups"
                    :languages="languages"
                    :translators="translators"
                    :default-language="defaultLanguage"
                    v-model:selected-group="selectedGroup"
                    v-model:search="search"
                    v-model:missing-only="missingOnly"
                    @scan="scanProject"
                    @translate-selected="translateSelected"
                />
            </div>
        </Card>

        <Card>
            <div class="overflow-x-auto">
                <TranslationTable
                    :rows="rows"
                    :languages="languages"
                    :default-language="defaultLanguage"
                    :loading="loading"
                    :selected-ids="selectedIds"
                    @update="handleUpdate"
                    @lock="handleLock"
                    @unlock="handleUnlock"
                    @translate="handleTranslate"
                    @toggle-select="toggleSelect"
                    @select-all="selectAll"
                />
            </div>
        </Card>
    </div>
</template>

<script>
import FilterBar from './components/FilterBar.vue'
import TranslationTable from './components/TranslationTable.vue'

export default {
    components: {
        FilterBar,
        TranslationTable,
    },

    data() {
        return {
            rows: [],
            languages: [],
            groups: [],
            translators: [],
            defaultLanguage: 'en',
            selectedGroup: '',
            search: '',
            missingOnly: false,
            loading: false,
            selectedIds: [],
        }
    },

    watch: {
        selectedGroup() {
            this.fetchData()
        },
        search() {
            this.debounceSearch()
        },
        missingOnly() {
            this.fetchData()
        },
    },

    mounted() {
        this.fetchData()
        this.fetchTranslators()
    },

    methods: {
        debounceSearch: _.debounce(function () {
            this.fetchData()
        }, 300),

        async fetchData() {
            this.loading = true

            try {
                const params = new URLSearchParams()

                if (this.selectedGroup) {
                    params.append('group', this.selectedGroup)
                }

                if (this.search) {
                    params.append('search', this.search)
                }

                if (this.missingOnly) {
                    params.append('missing_only', '1')
                }

                const response = await Nova.request().get(
                    `/nova-vendor/translatable/translation-matrix/grouped?${params}`
                )

                this.rows = response.data.rows
                this.languages = response.data.languages
                this.groups = response.data.groups
                this.defaultLanguage = response.data.defaultLanguage
            } catch (error) {
                Nova.error('Failed to load translations')
                console.error(error)
            } finally {
                this.loading = false
            }
        },

        async fetchTranslators() {
            try {
                const response = await Nova.request().get(
                    '/nova-vendor/translatable/translation-matrix/translators'
                )

                this.translators = response.data.translators
            } catch (error) {
                console.error('Failed to load translators:', error)
            }
        },

        async handleUpdate(translationId, value) {
            try {
                await Nova.request().put(
                    `/nova-vendor/translatable/translation-matrix/${translationId}`,
                    { value }
                )

                Nova.success('Translation updated')
            } catch (error) {
                Nova.error('Failed to update translation')
                console.error(error)
            }
        },

        async handleLock(translationId) {
            try {
                await Nova.request().post(
                    `/nova-vendor/translatable/translation-matrix/${translationId}/lock`
                )

                await this.fetchData()
                Nova.success('Translation locked')
            } catch (error) {
                Nova.error('Failed to lock translation')
                console.error(error)
            }
        },

        async handleUnlock(translationId) {
            try {
                await Nova.request().post(
                    `/nova-vendor/translatable/translation-matrix/${translationId}/unlock`
                )

                await this.fetchData()
                Nova.success('Translation unlocked')
            } catch (error) {
                Nova.error('Failed to unlock translation')
                console.error(error)
            }
        },

        async handleTranslate(translationId, driver, from, to) {
            try {
                await Nova.request().post(
                    '/nova-vendor/translatable/translation-matrix/translate',
                    { id: translationId, driver, from, to }
                )

                await this.fetchData()
                Nova.success('Translation completed')
            } catch (error) {
                Nova.error(error.response?.data?.message || 'Translation failed')
                console.error(error)
            }
        },

        async translateSelected(driver, from, to) {
            if (this.selectedIds.length === 0) {
                Nova.warning('No translations selected')

                return
            }

            try {
                const response = await Nova.request().post(
                    '/nova-vendor/translatable/translation-matrix/translate-batch',
                    { ids: this.selectedIds, driver, from, to }
                )

                await this.fetchData()
                Nova.success(`Translated ${response.data.count} items`)
                this.selectedIds = []
            } catch (error) {
                Nova.error(error.response?.data?.message || 'Batch translation failed')
                console.error(error)
            }
        },

        async scanProject() {
            try {
                await Nova.request().post('/nova-vendor/translatable/scan')
                await this.fetchData()
                Nova.success('Project scanned successfully')
            } catch (error) {
                Nova.error('Failed to scan project')
                console.error(error)
            }
        },

        toggleSelect(id) {
            const index = this.selectedIds.indexOf(id)

            if (index > -1) {
                this.selectedIds.splice(index, 1)
            } else {
                this.selectedIds.push(id)
            }
        },

        selectAll(ids) {
            this.selectedIds = ids
        },
    },
}
</script>

<style scoped>
.translation-matrix {
    padding: 1.5rem;
}
</style>
