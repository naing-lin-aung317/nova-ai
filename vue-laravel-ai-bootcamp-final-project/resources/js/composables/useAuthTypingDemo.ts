import { computed, onMounted, onUnmounted, ref, watch, type Ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useLocaleStore } from '@/stores/locale';

export type AuthTypingSampleId = 'tiktok' | 'facebook' | 'caption';

const ORDER_EN: AuthTypingSampleId[] = ['tiktok', 'facebook', 'caption'];
const ORDER_MY: AuthTypingSampleId[] = ['caption', 'tiktok', 'facebook'];

const TYPE_MS = 30;
const HOLD_MS = 2400;

function labelKey(id: AuthTypingSampleId): string {
    return `authHero.typing.${id}Label`;
}

function bodyKey(id: AuthTypingSampleId): string {
    return `authHero.typing.${id}Body`;
}

function splitGraphemes(text: string, locale: 'en' | 'my'): string[] {
    try {
        if (typeof Intl !== 'undefined' && 'Segmenter' in Intl) {
            const segmenter = new Intl.Segmenter(locale, { granularity: 'grapheme' });

            return Array.from(segmenter.segment(text), (part) => part.segment);
        }
    } catch {
        // fall through
    }

    return Array.from(text);
}

export function useAuthTypingDemo(enabled: Ref<boolean>) {
    const { t } = useI18n();
    const localeStore = useLocaleStore();

    const reducedMotion = ref(false);
    const pageHidden = ref(typeof document !== 'undefined' ? document.hidden : false);
    const sampleIndex = ref(0);
    const typedText = ref('');
    const caretVisible = ref(true);
    const isStatic = ref(false);

    let typeTimer: ReturnType<typeof setTimeout> | null = null;
    let holdTimer: ReturnType<typeof setTimeout> | null = null;
    let caretTimer: ReturnType<typeof setInterval> | null = null;
    let graphemeIndex = 0;
    let graphemes: string[] = [];

    const order = computed(() =>
        localeStore.locale === 'my' ? ORDER_MY : ORDER_EN,
    );

    const currentId = computed(
        () => order.value[sampleIndex.value % order.value.length],
    );

    const currentLabel = computed(() => t(labelKey(currentId.value)));

    const fullBody = computed(() => t(bodyKey(currentId.value)));

    function clearTypeTimer(): void {
        if (typeTimer !== null) {
            clearTimeout(typeTimer);
            typeTimer = null;
        }
    }

    function clearHoldTimer(): void {
        if (holdTimer !== null) {
            clearTimeout(holdTimer);
            holdTimer = null;
        }
    }

    function clearCaretTimer(): void {
        if (caretTimer !== null) {
            clearInterval(caretTimer);
            caretTimer = null;
        }
    }

    function clearAllTimers(): void {
        clearTypeTimer();
        clearHoldTimer();
        clearCaretTimer();
    }

    function startCaretBlink(): void {
        clearCaretTimer();

        if (reducedMotion.value || isStatic.value) {
            caretVisible.value = false;

            return;
        }

        caretTimer = setInterval(() => {
            caretVisible.value = !caretVisible.value;
        }, 530);
    }

    function beginSample(): void {
        if (!enabled.value || pageHidden.value) {
            return;
        }

        graphemes = splitGraphemes(fullBody.value, localeStore.locale);
        graphemeIndex = 0;
        typedText.value = '';

        if (reducedMotion.value) {
            isStatic.value = true;
            typedText.value = fullBody.value;
            caretVisible.value = false;

            return;
        }

        const tick = (): void => {
            if (!enabled.value || pageHidden.value) {
                return;
            }

            if (graphemeIndex >= graphemes.length) {
                clearTypeTimer();
                holdTimer = setTimeout(() => {
                    sampleIndex.value =
                        (sampleIndex.value + 1) % order.value.length;
                    beginSample();
                }, HOLD_MS);

                return;
            }

            typedText.value += graphemes[graphemeIndex];
            graphemeIndex += 1;
            typeTimer = setTimeout(tick, TYPE_MS);
        };

        tick();
    }

    function startLoop(): void {
        clearAllTimers();

        if (!enabled.value) {
            typedText.value = '';
            caretVisible.value = false;

            return;
        }

        if (reducedMotion.value) {
            isStatic.value = true;
            sampleIndex.value = 0;
            typedText.value = fullBody.value;
            caretVisible.value = false;

            return;
        }

        isStatic.value = false;
        startCaretBlink();
        beginSample();
    }

    function onVisibilityChange(): void {
        pageHidden.value = document.hidden;

        if (pageHidden.value) {
            clearAllTimers();

            return;
        }

        if (enabled.value && !reducedMotion.value) {
            startLoop();
        }
    }

    onMounted(() => {
        reducedMotion.value =
            typeof window !== 'undefined' &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        document.addEventListener('visibilitychange', onVisibilityChange);

        startLoop();
    });

    onUnmounted(() => {
        clearAllTimers();
        document.removeEventListener('visibilitychange', onVisibilityChange);
    });

    watch(enabled, (active) => {
        if (active) {
            startLoop();
        } else {
            clearAllTimers();
            typedText.value = '';
        }
    });

    watch(
        () => localeStore.locale,
        () => {
            sampleIndex.value = 0;
            startLoop();
        },
    );

    return {
        currentLabel,
        typedText,
        caretVisible,
        isStatic,
        reducedMotion,
    };
}
