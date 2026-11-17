<script setup lang="ts">
defineProps<{
    name: 'tiktok' | 'facebook' | 'marketing' | 'caption' | 'blog' | 'tones';
}>();
</script>

<template>
    <svg
        class="nova-landing-feature-icon-svg"
        viewBox="0 0 24 24"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
    >
        <template v-if="name === 'tiktok'">
            <rect height="16" rx="4" stroke="currentColor" stroke-width="1.75" width="16" x="4" y="4" />
            <path d="M11.25 9.25v5.5l4.75-2.75-4.75-2.75z" fill="currentColor" stroke="none" />
        </template>
        <template v-else-if="name === 'facebook'">
            <path
                d="M8 17.5c3.5 0 6.5-2.7 6.5-6.2S11.5 5 8 5 1.5 7.8 1.5 11.3 4.5 17.5 8 17.5z"
                stroke="currentColor"
                stroke-width="1.75"
            />
            <path
                d="M8 17.5 6 20.5"
                stroke="currentColor"
                stroke-linecap="round"
                stroke-width="1.75"
            />
        </template>
        <template v-else-if="name === 'marketing'">
            <path
                d="M4 11v5l13.5-5.5V5.5L4 11z"
                stroke="currentColor"
                stroke-linejoin="round"
                stroke-width="1.75"
            />
            <path
                d="M17.5 5.5v11M7 16l2.5 2.5"
                stroke="currentColor"
                stroke-linecap="round"
                stroke-width="1.75"
            />
        </template>
        <template v-else-if="name === 'caption'">
            <rect height="14" rx="2.5" stroke="currentColor" stroke-width="1.75" width="18" x="3" y="5" />
            <path d="M3 10h18" stroke="currentColor" stroke-width="1.75" />
            <path d="M7 15h6" stroke="currentColor" stroke-linecap="round" stroke-width="1.75" />
        </template>
        <template v-else-if="name === 'blog'">
            <rect height="15" rx="2" stroke="currentColor" stroke-width="1.75" width="12" x="6" y="4.5" />
            <path d="M9.5 9.5h9M9.5 12.5h9M9.5 15.5h6.5" stroke="currentColor" stroke-linecap="round" stroke-width="1.75" />
            <path d="M6 7.5h.01M6 10.5h.01M6 13.5h.01" stroke="currentColor" stroke-linecap="round" stroke-width="2.5" />
        </template>
        <template v-else>
            <path d="M6 8.5h12M6 12h12M6 15.5h12" stroke="currentColor" stroke-linecap="round" stroke-width="1.75" />
            <circle cx="9" cy="8.5" fill="currentColor" r="2" stroke="none" />
            <circle cx="15" cy="12" fill="currentColor" r="2" stroke="none" />
            <circle cx="11" cy="15.5" fill="currentColor" r="2" stroke="none" />
        </template>
    </svg>
</template>
