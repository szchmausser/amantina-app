import { PageProps } from '@inertiajs/core';
import { Auth } from './auth';
import { InstitutionData } from './institution';

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
    institution?: InstitutionData | null;
};

export type * from './auth';
export type * from './institution';
export type * from './navigation';
export type * from './ui';
export type * from './dashboard';
