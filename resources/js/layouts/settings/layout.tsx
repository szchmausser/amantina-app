import { Link } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';
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
    Building2,
    Layers,
    Heart,
    Tag,
    MapPin,
} from 'lucide-react';
import type { PropsWithChildren } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { index as academicYearsIndex } from '@/routes/admin/academic-years';
import { index as gradesIndex } from '@/routes/admin/grades';
import { index as schoolTermsIndex } from '@/routes/admin/school-terms';
import { index as sectionsIndex } from '@/routes/admin/sections';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editInstitution } from '@/routes/institution';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavEntry, NavItem, SharedData } from '@/types';

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { auth } = usePage<SharedData>().props;
    const { isCurrentOrParentUrl } = useCurrentUrl();

    const sidebarNavItems: NavEntry[] = [
        // ── Cuenta ──
        { title: 'Cuenta', type: 'separator' },
        {
            title: 'Perfil',
            href: edit(),
            icon: User,
        },
        {
            title: 'Seguridad',
            href: editSecurity(),
            icon: Lock,
        },
        {
            title: 'Apariencia',
            href: editAppearance(),
            icon: Palette,
        },
        // ── Institución ──
        { title: 'Institución', type: 'separator' },
        {
            title: 'Datos Institucionales',
            href: editInstitution().url,
            icon: Building2,
        },
    ];

    // ── Académico ──
    const academicItems: NavItem[] = [];

    if (auth.permissions?.includes('academic_years.view')) {
        academicItems.push({
            title: 'Años Escolares',
            href: academicYearsIndex().url,
            icon: Calendar,
        });
    }

    if (auth.permissions?.includes('school_terms.view')) {
        academicItems.push({
            title: 'Lapsos Académicos',
            href: schoolTermsIndex().url,
            icon: Clock,
        });
    }

    if (auth.permissions?.includes('grades.view')) {
        academicItems.push({
            title: 'Grados',
            href: gradesIndex().url,
            icon: GraduationCap,
        });
    }

    if (auth.permissions?.includes('sections.view')) {
        academicItems.push({
            title: 'Secciones',
            href: sectionsIndex().url,
            icon: Layers,
        });
    }

    if (academicItems.length > 0) {
        sidebarNavItems.push(
            { title: 'Académico', type: 'separator' },
            ...academicItems,
        );
    }

    // ── Operativo ──
    const operationalItems: NavItem[] = [];

    if (auth.permissions?.includes('health_conditions.view')) {
        operationalItems.push({
            title: 'Condiciones de Salud',
            href: '/admin/health-conditions',
            icon: Heart,
        });
    }

    if (auth.permissions?.includes('activity_categories.view')) {
        operationalItems.push({
            title: 'Categorías',
            href: '/admin/activity-categories',
            icon: Tag,
        });
    }

    if (auth.permissions?.includes('locations.view')) {
        operationalItems.push({
            title: 'Ubicaciones',
            href: '/admin/locations',
            icon: MapPin,
        });
    }

    if (auth.permissions?.includes('enrollments.view')) {
        operationalItems.push({
            title: 'Inscripciones',
            href: '/admin/enrollments',
            icon: UserPlus,
        });
    }

    if (auth.permissions?.includes('assignments.view')) {
        operationalItems.push({
            title: 'Asignaciones Docentes',
            href: '/admin/teacher-assignments',
            icon: BookUser,
        });
    }

    if (operationalItems.length > 0) {
        sidebarNavItems.push(
            { title: 'Operativo', type: 'separator' },
            ...operationalItems,
        );
    }

    // ── Accesos ──
    const accessItems: NavItem[] = [];

    if (auth.permissions?.includes('roles.view')) {
        accessItems.push({
            title: 'Gestión de Roles',
            href: '/admin/roles',
            icon: Shield,
        });
    }

    if (auth.permissions?.includes('permissions.view')) {
        accessItems.push({
            title: 'Gestión de Permisos',
            href: '/admin/permissions',
            icon: ShieldCheck,
        });
    }

    if (accessItems.length > 0) {
        sidebarNavItems.push(
            { title: 'Accesos', type: 'separator' },
            ...accessItems,
        );
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
                        {sidebarNavItems.map((entry) => {
                            if (entry.type === 'separator') {
                                return (
                                    <div
                                        key={`sep-${entry.title}`}
                                        className="pt-3 pb-1"
                                    >
                                        <p className="px-3 text-[11px] font-semibold tracking-wider text-muted-foreground/70 uppercase">
                                            {entry.title}
                                        </p>
                                    </div>
                                );
                            }

                            const item = entry as NavItem;

                            return (
                                <Button
                                    key={toUrl(item.href)}
                                    size="sm"
                                    variant="ghost"
                                    asChild
                                    className={cn('w-full justify-start', {
                                        'bg-muted': isCurrentOrParentUrl(
                                            item.href,
                                        ),
                                    })}
                                >
                                    <Link href={item.href}>
                                        {item.icon && (
                                            <item.icon className="h-4 w-4" />
                                        )}
                                        {item.title}
                                    </Link>
                                </Button>
                            );
                        })}
                    </nav>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div className="flex-1 overflow-hidden">
                    <section className="space-y-12">{children}</section>
                </div>
            </div>
        </div>
    );
}
