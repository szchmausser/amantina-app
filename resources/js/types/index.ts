import { PageProps } from '@inertiajs/core';
import { Auth } from './auth';

export type SharedData = PageProps & {
    name: string;
    auth: Auth;
    sidebarOpen: boolean;
    flash: {
        message?: string;
        success?: string;
        error?: string;
        warning?: string;
    };
};

export type * from './auth';
export type * from './navigation';
export type * from './ui';
