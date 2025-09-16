<template>
    <DefaultField
        :field="field"
        :errors="errors"
        :show-help-text="showHelpText"
    >
        <template #field>
            <div
                id="mm-translation-language-toggle"
                class="languages-overview"
                v-bind:data-translating="field.translating"
                v-bind:data-source="field.source"
                v-bind:data-target="field.target"
            >
                <span
                    v-for="language in field.languages"
                    :key="language.language"
                    class="language-container"
                >
                    <a
                        v-if="field.toggler_clickable"
                        :href="language.toggle_path"
                        :title="language.name"
                        class="mr-3 language-toggler relative"
                        v-bind:class="{ selected: language.currently_selected }"
                    >
                        <img :src="language.icon" class="rounded-full" />
                        <Icon
                            name="star"
                            variant="micro"
                            class="absolute star-icon"
                            v-if="language.is_default"
                        />
                    </a>
                    <span
                        v-else
                        class="mr-3 language-toggler disabled"
                        :title="language.name"
                        v-bind:class="{ selected: language.currently_selected }"
                    >
                        <img :src="language.icon" class="rounded-full" />
                        <span v-if="language.is_default" class="star-icon"
                            >★</span
                        >
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
    import { Icon } from "laravel-nova-ui";

    export default {
        mixins: [FormField, HandlesValidationErrors],

        components: {
            Icon,
        },

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
    .language-container {
        position: relative;
        display: inline-block;
    }
    .language-toggler {
        position: relative;
        display: inline-block;
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
    .star-icon {
        top: 5px;
        right: 10px;
        width: 14px;
        height: 14px;
        background-color: rgb(228, 194, 4);
        font-size: 1.2rem;
        font-size: 10px;
        color: rgb(255, 246, 192);
    }
</style>
