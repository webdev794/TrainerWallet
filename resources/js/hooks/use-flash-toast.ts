import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { toast } from 'sonner';

import type { SharedData } from '@/types';

/**
 * Surface Laravel flash messages as toasts.
 */
export function useFlashToast() {
    const { flash } = usePage<SharedData>().props;
    const lastShown = useRef<string | null>(null);

    useEffect(() => {
        const message = flash?.success ?? flash?.error;

        if (!message || message === lastShown.current) {
            return;
        }

        lastShown.current = message;

        if (flash?.error) {
            toast.error(flash.error);
        } else if (flash?.success) {
            toast.success(flash.success);
        }
    }, [flash]);
}
