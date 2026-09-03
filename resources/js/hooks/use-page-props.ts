import { usePage } from '@inertiajs/react';

import type { SharedData } from '@/types';

export function usePageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
>() {
    return usePage<SharedData & T>().props;
}

export function useAuth() {
    return usePage<SharedData>().props.auth;
}
