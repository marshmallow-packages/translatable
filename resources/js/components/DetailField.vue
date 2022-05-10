<template>
    <PanelItem :field="field">
        <div slot="value">
            <span v-for="language in field.languages" :key="language.language">
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
    </PanelItem>
</template>

<script>
    export default {
        props: ["resource", "resourceName", "resourceId", "field"],
    };
</script>

<style type="text/css">
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
        border: 3px solid #27ae60;
    }
    .language-toggler.disabled img {
        border: 3px solid #ccc;
        opacity: 0.5;
    }
</style>
