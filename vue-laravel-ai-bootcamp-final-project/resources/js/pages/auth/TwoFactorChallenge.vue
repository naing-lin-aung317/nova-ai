<script setup lang="ts">
import AuthCard from '@/components/shell/AuthCard.vue';
import AuthSimpleLayout from '@/layouts/AuthSimpleLayout.vue';

defineOptions({ layout: AuthSimpleLayout });
</script>

<template>
    <AuthCard title-key="pages.login" />
</template>
