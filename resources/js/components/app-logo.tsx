import { usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { useSidebar } from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';
import type { SharedData } from '@/types';

export default function AppLogo() {
    const { props } = usePage<SharedData>();
    const institution = props.institution;
    const { state } = useSidebar();
    const isCollapsed = state === 'collapsed';

    if (institution?.logo_url) {
        return (
            <div className={cn('flex items-center gap-2', !isCollapsed && 'flex-col text-center w-full pt-8 pb-4')}>
                <div
                    className={cn(
                        'flex items-center justify-center transition-all',
                        isCollapsed ? 'size-8 overflow-hidden' : 'w-40 h-auto max-h-40',
                    )}
                    data-testid="app-logo"
                >
                    <img
                        src={institution.logo_url}
                        alt={institution.name}
                        className="size-full object-contain"
                        data-testid="app-logo-image"
                    />
                </div>
                <div className={cn('grid flex-1 leading-tight', isCollapsed ? 'text-left text-sm ml-1' : 'text-center -mt-3')}>
                    <span className={cn('font-bold truncate px-2', isCollapsed ? 'text-sm' : 'text-lg block tracking-tight')}>
                        {institution.name}
                    </span>
                </div>
            </div>
        );
    }

    return (
        <div className={cn('flex items-center gap-2', !isCollapsed && 'flex-col text-center w-full pt-8 pb-4')}>
            <div
                className={cn(
                    'flex items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground transition-all',
                    isCollapsed ? 'size-8 overflow-hidden' : 'w-40 h-auto max-h-40',
                )}
            >
                <AppLogoIcon className={cn('fill-current text-white dark:text-black', isCollapsed ? 'size-5' : 'size-20')} />
            </div>
            <div className={cn('grid flex-1 leading-tight', isCollapsed ? 'text-left text-sm ml-1' : 'text-center -mt-4')}>
                <span className={cn('font-bold truncate px-2', isCollapsed ? 'text-sm' : 'text-lg block tracking-tight')}>
                    {institution?.name ?? 'Amantina App'}
                </span>
            </div>
        </div>
    );
}
