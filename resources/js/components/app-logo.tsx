import { usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import type { SharedData } from '@/types';

export default function AppLogo() {
    const { props } = usePage<SharedData>();
    const institution = props.institution;

    if (institution?.logo_url) {
        return (
            <>
                <div
                    className="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md"
                    data-testid="app-logo"
                >
                    <img
                        src={institution.logo_url}
                        alt={institution.name}
                        className="size-full object-cover"
                        data-testid="app-logo-image"
                    />
                </div>
                <div className="ml-1 grid flex-1 text-left text-sm">
                    <span className="mb-0.5 truncate leading-tight font-semibold">
                        {institution.name}
                    </span>
                </div>
            </>
        );
    }

    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                <AppLogoIcon className="size-5 fill-current text-white dark:text-black" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    {institution?.name ?? 'Amantina App'}
                </span>
            </div>
        </>
    );
}
