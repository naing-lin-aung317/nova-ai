import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { SharedWallet } from '@/types/wallet';

export function useWallet() {
    const page = usePage();

    const wallet = computed(
        () => (page.props.wallet as SharedWallet | null | undefined) ?? null,
    );

    const availableCredits = computed(() => wallet.value?.available ?? 0);

    return { wallet, availableCredits };
}
