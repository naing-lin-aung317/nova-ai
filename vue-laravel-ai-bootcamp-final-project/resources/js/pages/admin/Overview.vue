<script setup lang="ts">
import ScreenPlaceholder from '@/components/shell/ScreenPlaceholder.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });
</script>

<template>
    <ScreenPlaceholder title-key="pages.overview" />
</template>
