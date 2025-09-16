<template>
    <PanelItem :field="field">
        <template #value>
            <span
                v-for="language in field.languages"
                :key="language.language"
                class="language-container"
            >
                <a
                    v-if="field.toggler_clickable"
                    :href="language.toggle_path"
                    :title="language.name"
                    class="mr-3 language-toggler"
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
                    <Icon
                        name="star"
                        variant="micro"
                        class="absolute star-icon"
                        v-if="language.is_default"
                    />
                </span>
            </span>
        </template>
    </PanelItem>
</template>

<script>
    import { Icon } from "laravel-nova-ui";

    export default {
        components: {
            Icon,
        },
        props: ["resource", "resourceName", "resourceId", "field"],
    };
</script>

<style>
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
        display: inline-block;
        margin-right: 0.5rem;
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
        border-radius: 100%;
        color: rgb(255, 246, 192);
    }
</style>
