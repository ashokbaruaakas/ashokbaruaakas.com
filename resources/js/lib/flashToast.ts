import { router } from '@inertiajs/vue3';
import type { FlashToast } from '@/types/ui';

export function initializeFlashToast(): void {
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;
        const data = flash?.toast as FlashToast | undefined;

        if (!data) {
            return;
        }

        void import('vue-sonner').then(({ toast }) => {
            toast[data.type](data.message);
        });
    });
}
