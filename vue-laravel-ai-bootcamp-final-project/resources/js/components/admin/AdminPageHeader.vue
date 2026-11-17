<script setup lang="ts">
defineProps<{
    title: string;
    subtitle?: string;
    meta?: string;
}>();
</script>

<template>
    <div class="nova-admin-head nova-row">
        <div>
            <h2 style="margin: 0">{{ title }}</h2>
            <p v-if="meta" class="nova-admin-meta nova-muted">{{ meta }}</p>
            <p v-if="subtitle" class="nova-muted" style="margin: 6px 0 0">{{ subtitle }}</p>
        </div>
        <div class="nova-admin-head-end">
            <slot name="badge" />
            <div v-if="$slots.actions" class="nova-admin-head-actions">
                <slot name="actions" />
            </div>
        </div>
    </div>
</template>
