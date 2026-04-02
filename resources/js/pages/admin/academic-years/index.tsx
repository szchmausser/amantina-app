import { Head, Link, router, usePage } from '@inertiajs/react';
import { Calendar, CheckCircle2, Edit, MoreVertical, Plus, Settings, Trash2 } from 'lucide-react';
import {
    index as academicYearsIndex,
    create as academicYearsCreate,
    edit as academicYearsEdit,
    show as academicYearsShow,
    destroy as academicYearsDestroy,
} from '@/routes/admin/academic-years';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem, SharedData } from '@/types';

interface AcademicYear {
    id: number;
    name: string;
    is_active: boolean;
    required_hours: number;
}

interface Props {
    academicYears: {
        data: AcademicYear[];
        links: any[];
        current_page: number;
        last_page: number;
        total: number;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Años Escolares', href: academicYearsIndex().url },
];

export default function AcademicYearIndex({ academicYears }: Props) {
    const { auth } = usePage<SharedData>().props;
    const hasPermission = (p: string) => auth.permissions?.includes(p);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Años Escolares" />

            <SettingsLayout>
                <div className="flex flex-col gap-4">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">Estructura Académica</h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Gestión de años escolares, lapsos, grados y secciones.
                            </p>
                        </div>
                        {hasPermission('academic_years.create') && (
                            <Button asChild>
                                <Link href={academicYearsCreate().url}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Nuevo Año Escolar
                                </Link>
                            </Button>
                        )}
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        {academicYears.data.map((year) => (
                            <div
                                key={year.id}
                                className="relative overflow-hidden rounded-xl border border-sidebar-border/70 bg-white p-6 shadow-sm transition-all hover:shadow-md dark:border-sidebar-border dark:bg-neutral-900"
                            >
                                <div className="mb-4 flex items-start justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className="rounded-lg bg-blue-100 p-2 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                                            <Calendar className="h-5 w-5" />
                                        </div>
                                        <div>
                                            <h3 className="font-semibold text-neutral-900 dark:text-neutral-100">
                                                {year.name}
                                            </h3>
                                            <p className="text-xs text-neutral-500">
                                                Meta: {year.required_hours} horas
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        {year.is_active && (
                                            <Badge className="bg-green-100 text-green-700 hover:bg-green-100 dark:bg-green-900/30 dark:text-green-400">
                                                <CheckCircle2 className="mr-1 h-3 w-3" />
                                                Activo
                                            </Badge>
                                        )}
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="icon" className="h-8 w-8">
                                                    <MoreVertical className="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                {hasPermission('academic_years.edit') && (
                                                    <DropdownMenuItem asChild>
                                                        <Link href={academicYearsEdit(year.id).url}>
                                                            <Edit className="mr-2 h-4 w-4" />
                                                            Editar
                                                        </Link>
                                                    </DropdownMenuItem>
                                                )}
                                                <DropdownMenuItem asChild>
                                                    <Link href={academicYearsShow(year.id).url}>
                                                        <Settings className="mr-2 h-4 w-4" />
                                                        Configurar Estructura
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                {hasPermission('academic_years.delete') && (
                                                    <DropdownMenuItem
                                                        className="text-red-600 focus:text-red-600 cursor-pointer"
                                                        onClick={() => {
                                                            if (confirm('¿Estás seguro de que deseas eliminar este año académico? Se eliminarán también sus lapsos, grados y secciones.')) {
                                                                router.delete(academicYearsDestroy(year.id).url);
                                                            }
                                                        }}
                                                    >
                                                        <Trash2 className="mr-2 h-4 w-4" />
                                                        Eliminar
                                                    </DropdownMenuItem>
                                                )}
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>

                                <div className="mt-6 flex items-center justify-between gap-4 border-t border-neutral-100 pt-4 dark:border-neutral-800">
                                    <div className="flex flex-col">
                                        <span className="text-[10px] uppercase tracking-wider text-neutral-500">Resumen</span>
                                        <span className="text-sm font-medium">Ciclo Lectivo</span>
                                    </div>
                                    <Button size="sm" variant="secondary" asChild>
                                        <Link href={academicYearsShow(year.id).url}>
                                            Gestionar
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}

