import type { Auth } from '@/types/auth';
import type { InstitutionData } from '@/types/institution';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            institution: InstitutionData | null;
            [key: string]: unknown;
        };
    }
}
