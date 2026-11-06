<script setup lang="ts">
import ScreenPlaceholder from '@/components/shell/ScreenPlaceholder.vue';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });
</script>

<template>
    <ScreenPlaceholder title-key="pages.settings" />
</template>
