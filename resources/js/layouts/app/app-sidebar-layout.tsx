import { usePage } from '@inertiajs/react';
import { AlertCircle, CheckCircle2 } from 'lucide-react';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import type { SharedData, AppLayoutProps } from '@/types';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    const { props } = usePage<SharedData>();
    const { flash } = props;

    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                
                {flash.success && (
                    <div className="px-4 pt-4 sm:px-6">
                        <Alert variant="default" className="border-green-500/50 bg-green-50 text-green-900 dark:border-green-500/30 dark:bg-green-900/20 dark:text-green-200">
                            <CheckCircle2 className="h-4 w-4 text-green-600 dark:text-green-400" />
                            <AlertTitle>Éxito</AlertTitle>
                            <AlertDescription>{flash.success}</AlertDescription>
                        </Alert>
                    </div>
                )}

                {flash.error && (
                    <div className="px-4 pt-4 sm:px-6">
                        <Alert variant="destructive">
                            <AlertCircle className="h-4 w-4" />
                            <AlertTitle>Error</AlertTitle>
                            <AlertDescription>{flash.error}</AlertDescription>
                        </Alert>
                    </div>
                )}

                {children}
            </AppContent>
        </AppShell>
    );
}
