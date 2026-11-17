<script setup lang="ts">
defineProps<{
    modelValue: string;
    options: Array<{ value: string; label: string }>;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();
</script>

<template>
    <div class="nova-admin-filter-pills" role="tablist">
        <button
            v-for="opt in options"
            :key="opt.value"
            type="button"
            role="tab"
            :aria-selected="modelValue === opt.value"
            :class="{ 'is-active': modelValue === opt.value }"
            @click="emit('update:modelValue', opt.value)"
        >
            {{ opt.label }}
        </button>
    </div>
</template>
