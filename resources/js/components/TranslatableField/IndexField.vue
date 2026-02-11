<template>
    <span>{{ displayValue }}</span>
</template>

<script>
export default {
    props: ['resourceName', 'field'],

    computed: {
        displayValue() {
            return this.field.value || '—'
        },
    },
}
</script>
