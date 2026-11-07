<script setup lang="ts">
defineProps<{
    errors?: string | string[];
}>();

function messages(errors?: string | string[]): string[] {
    if (! errors) {
        return [];
    }

    return Array.isArray(errors) ? errors : [errors];
}
</script>

<template>
    <ul v-if="messages(errors).length" class="nova-errors">
        <li v-for="(error, index) in messages(errors)" :key="index">{{ error }}</li>
    </ul>
</template>
