<template>
    <DefaultField
        :field="field"
        :errors="errors"
        :show-help-text="showHelpText"
    >
        <template #field>
            <div id="mm-translation-language-toggle" class="languages-overview" v-bind:data-translating="field.translating" v-bind:data-source="field.source" v-bind:data-target="field.target">
                <span
                    v-for="language in field.languages"
                    :key="language.language"
                >
                    <a
                        v-if="field.toggler_clickable"
                        :href="language.toggle_path"
                        :title="language.name"
                        class="mr-3 language-toggler"
                        v-bind:class="{ selected: language.currently_selected }"
                    >
                        <img :src="language.icon" class="rounded-full" />
                    </a>
                    <span
                        v-else
                        class="mr-3 language-toggler disabled"
                        :title="language.name"
                        v-bind:class="{ selected: language.currently_selected }"
                    >
                        <img :src="language.icon" class="rounded-full" />
                    </span>
                </span>
            </div>
            <p v-if="field.toggler_notification" class="mt-4">
                <span v-html="field.toggler_notification"></span>
            </p>
        </template>
    </DefaultField>
</template>

<script>
    import { FormField, HandlesValidationErrors } from "laravel-nova";

    export default {
        mixins: [FormField, HandlesValidationErrors],

        props: ["resourceName", "resourceId", "field"],

        methods: {
            /*
             * Set the initial, internal value for the field.
             */
            setInitialValue() {
                this.value = this.field.value || "";
            },

            /**
             * Fill the given FormData object with the field's internal value.
             */
            fill(formData) {
                formData.append(this.field.attribute, this.value || "");
            },
        },
    };
</script>

<style>
    .mt-4 {
        margin-top: 1rem;
    }
    .language-toggler img {
        border: 3px solid transparent;
        padding: 5px;
        width: 2.5rem;
        height: 2.5rem;
    }
    .language-toggler.selected img,
    .language-toggler.selected.disabled img {
        border: 3px solid rgba(var(--colors-primary-500));
    }
    .language-toggler.disabled img {
        border: 3px solid #ccc;
        opacity: 0.5;
    }
</style>
