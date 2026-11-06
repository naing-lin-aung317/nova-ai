<script setup lang="ts">
import AuthCard from '@/components/shell/AuthCard.vue';
import AuthSimpleLayout from '@/layouts/AuthSimpleLayout.vue';

defineOptions({ layout: AuthSimpleLayout });

defineProps<{
    email?: string;
    token?: string;
}>();
</script>

<template>
    <AuthCard title-key="pages.reset" />
</template>
