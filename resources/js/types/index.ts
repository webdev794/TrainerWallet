import type { Auth, FlashData } from './auth';

export type * from './auth';

export type SharedData = {
    name: string;
    auth: Auth;
    flash: FlashData;
    sidebarOpen: boolean;
    [key: string]: unknown;
};
