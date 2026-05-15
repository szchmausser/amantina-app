import { Link, usePage } from '@inertiajs/react';
import {
    CalendarCheck,
    LayoutDashboard,
    LayoutGrid,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as academicInfoIndex } from '@/routes/admin/academic-info';
import type { NavItem, SharedData } from '@/types';
import { cn } from '@/lib/utils';

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;
    const { state } = useSidebar();
    const isCollapsed = state === 'collapsed';

    const mainNavItems: NavItem[] = [];

    // Always show Dashboard first
    mainNavItems.push({
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    });

    // Teacher-specific items
    if (auth.active_role === 'profesor') {
        mainNavItems.push({
            title: 'Mis Jornadas',
            href: '/admin/field-sessions',
            icon: CalendarCheck,
        });
    }

    // Admin items (hidden for profesor role)
    if (auth.active_role !== 'profesor') {
        if (auth.permissions?.includes('academic_info.view')) {
            mainNavItems.push({
                title: 'Información Académica',
                href: academicInfoIndex().url,
                icon: LayoutDashboard,
            });
        }

        if (auth.permissions?.includes('field_sessions.view')) {
            mainNavItems.push({
                title: 'Jornadas',
                href: '/admin/field-sessions',
                icon: CalendarCheck,
            });
        }
    }

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild className={cn(isCollapsed ? 'h-12' : 'h-auto py-4 px-0')}>
                            <Link
                                href={
                                    auth.permissions?.includes(
                                        'academic_info.view',
                                    )
                                        ? academicInfoIndex().url
                                        : dashboard()
                                }
                                prefetch
                            >
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
