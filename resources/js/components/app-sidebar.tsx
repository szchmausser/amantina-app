import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    Calendar,
    Clock,
    FolderGit2,
    GraduationCap,
    LayoutGrid,
    Shield,
    ShieldCheck,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as academicYearsIndex } from '@/routes/admin/academic-years';
import { index as gradesIndex } from '@/routes/admin/grades';
import { index as schoolTermsIndex } from '@/routes/admin/school-terms';
import { index as userIndex } from '@/routes/admin/users';
import type { NavItem, SharedData } from '@/types';

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    if (auth.permissions?.includes('users.view')) {
        mainNavItems.push({
            title: 'Gestión de Usuarios',
            href: userIndex().url,
            icon: Users,
        });
    }

    if (auth.permissions?.includes('academic_years.view')) {
        mainNavItems.push({
            title: 'Años Escolares',
            href: academicYearsIndex().url,
            icon: Calendar,
        });
    }

    if (auth.permissions?.includes('school_terms.view')) {
        mainNavItems.push({
            title: 'Lapsos Académicos',
            href: schoolTermsIndex().url,
            icon: Clock,
        });
    }

    if (auth.permissions?.includes('grades.view')) {
        mainNavItems.push({
            title: 'Grados y Secciones',
            href: gradesIndex().url,
            icon: GraduationCap,
        });
    }

    if (auth.permissions?.includes('roles.view')) {
        mainNavItems.push({
            title: 'Gestión de Roles',
            href: '/admin/roles',
            icon: Shield,
        });
    }

    if (auth.permissions?.includes('permissions.view')) {
        mainNavItems.push({
            title: 'Gestión de Permisos',
            href: '/admin/permissions',
            icon: ShieldCheck,
        });
    }

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
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
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
