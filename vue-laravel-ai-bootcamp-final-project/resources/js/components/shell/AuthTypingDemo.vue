<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import { useAuthTypingDemo } from '@/composables/useAuthTypingDemo';

const panelVisible = ref(false);
const enabled = ref(false);

const { currentLabel, typedText, caretVisible } = useAuthTypingDemo(enabled);

let mediaQuery: MediaQueryList | null = null;

function syncPanelVisible(matches: boolean): void {
    panelVisible.value = matches;
    enabled.value = matches;
}

function onMediaChange(event: MediaQueryListEvent): void {
    syncPanelVisible(event.matches);
}

onMounted(() => {
    if (typeof window === 'undefined') {
        return;
    }

    mediaQuery = window.matchMedia('(min-width: 801px)');
    syncPanelVisible(mediaQuery.matches);
    mediaQuery.addEventListener('change', onMediaChange);
});

onUnmounted(() => {
    mediaQuery?.removeEventListener('change', onMediaChange);
});
</script>

<template>
    <div
        v-if="panelVisible"
        class="nova-auth-typing"
        aria-hidden="true"
    >
        <p class="nova-auth-typing-label nova-muted">
            {{ currentLabel }}
        </p>
        <pre class="nova-auth-typing-body">
            <span>{{ typedText }}</span><span
                v-if="caretVisible"
                class="nova-auth-typing-caret"
            >|</span>
        </pre>
    </div>
</template>
