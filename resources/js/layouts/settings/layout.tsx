import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import { edit as editInstitution } from '@/routes/institution';
import { index as academicYearsIndex } from '@/routes/admin/academic-years';
import { index as gradesIndex } from '@/routes/admin/grades';
import { index as schoolTermsIndex } from '@/routes/admin/school-terms';
import { 
    Calendar, 
    Clock, 
    GraduationCap, 
    UserPlus, 
    BookUser, 
    Shield, 
    ShieldCheck,
    User,
    Lock,
    Palette,
    Building2
} from 'lucide-react';
import type { NavItem, SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { auth } = usePage<SharedData>().props;
    const { isCurrentOrParentUrl } = useCurrentUrl();

    const sidebarNavItems: NavItem[] = [
        {
            title: 'Profile',
            href: edit(),
            icon: User,
        },
        {
            title: 'Security',
            href: editSecurity(),
            icon: Lock,
        },
        {
            title: 'Appearance',
            href: editAppearance(),
            icon: Palette,
        },
        {
            title: 'Datos Institucionales',
            href: editInstitution().url,
            icon: Building2,
        },
    ];

    if (auth.permissions?.includes('academic_years.view')) {
        sidebarNavItems.push({
            title: 'Años Escolares',
            href: academicYearsIndex().url,
            icon: Calendar,
        });
    }

    if (auth.permissions?.includes('school_terms.view')) {
        sidebarNavItems.push({
            title: 'Lapsos Académicos',
            href: schoolTermsIndex().url,
            icon: Clock,
        });
    }

    if (auth.permissions?.includes('grades.view')) {
        sidebarNavItems.push({
            title: 'Grados y Secciones',
            href: gradesIndex().url,
            icon: GraduationCap,
        });
    }

    if (auth.permissions?.includes('enrollments.view')) {
        sidebarNavItems.push({
            title: 'Inscripciones',
            href: '/admin/enrollments',
            icon: UserPlus,
        });
    }

    if (auth.permissions?.includes('assignments.view')) {
        sidebarNavItems.push({
            title: 'Asignaciones Docentes',
            href: '/admin/teacher-assignments',
            icon: BookUser,
        });
    }

    if (auth.permissions?.includes('roles.view')) {
        sidebarNavItems.push({
            title: 'Gestión de Roles',
            href: '/admin/roles',
            icon: Shield,
        });
    }

    if (auth.permissions?.includes('permissions.view')) {
        sidebarNavItems.push({
            title: 'Gestión de Permisos',
            href: '/admin/permissions',
            icon: ShieldCheck,
        });
    }

    // When server-side rendering, we only render the layout on the client...
    if (typeof window === 'undefined') {
        return null;
    }

    return (
        <div className="px-4 py-6">
            <Heading
                title="Configuración"
                description="Gestiona el perfil, la seguridad y los parámetros generales del sistema"
            />

            <div className="flex flex-col lg:flex-row lg:space-x-12">
                <aside className="w-full max-w-xl lg:w-48">
                    <nav
                        className="flex flex-col space-y-1 space-x-0"
                        aria-label="Settings"
                    >
                        {sidebarNavItems.map((item, index) => (
                            <Button
                                key={`${toUrl(item.href)}-${index}`}
                                size="sm"
                                variant="ghost"
                                asChild
                                className={cn('w-full justify-start', {
                                    'bg-muted': isCurrentOrParentUrl(item.href),
                                })}
                            >
                                <Link href={item.href}>
                                    {item.icon && (
                                        <item.icon className="h-4 w-4" />
                                    )}
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div className="flex-1 overflow-hidden">
                    <section className="space-y-12">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
