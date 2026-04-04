import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Calendar,
    CheckCircle2,
    Clock,
    Edit,
    GraduationCap,
    Layers,
    Trash2,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem, SharedData } from '@/types';
import { dashboard } from '@/routes';
import {
    index as academicYearsIndex,
    edit as academicYearsEdit,
    destroy as academicYearsDestroy,
} from '@/routes/admin/academic-years';
import { index as schoolTermsIndex } from '@/routes/admin/school-terms';
import { index as gradesIndex } from '@/routes/admin/grades';
import { index as sectionsIndex } from '@/routes/admin/sections';

interface AcademicYear {
    id: number;
    name: string;
    is_active: boolean;
    required_hours: number;
    school_terms_count: number;
    grades_count: number;
    sections_count: number;
}

interface Props {
    academicYear: AcademicYear;
}

export default function AcademicYearShow({ academicYear }: Props) {
    const { auth } = usePage<SharedData>().props;
    const hasPermission = (p: string) => auth.permissions?.includes(p);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Años Escolares', href: academicYearsIndex().url },
        { title: academicYear.name, href: '#' },
    ];

    const handleDelete = () => {
        if (
            confirm(
                '¿Estás seguro de que deseas eliminar este año escolar? Se eliminarán también sus lapsos, grados y secciones.',
            )
        ) {
            router.delete(academicYearsDestroy(academicYear.id).url);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Año Escolar: ${academicYear.name}`} />

            <SettingsLayout>
                <div className="flex flex-col gap-6">
                    {/* Encabezado */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-center gap-4">
                            <Button
                                variant="ghost"
                                size="icon"
                                asChild
                                className="-ml-2"
                            >
                                <Link href={academicYearsIndex().url}>
                                    <ArrowLeft className="h-5 w-5" />
                                </Link>
                            </Button>
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                    {academicYear.name}
                                </h1>
                                <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                    Detalles y configuración del año escolar.
                                </p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            {hasPermission('academic_years.edit') && (
                                <Button variant="outline" asChild>
                                    <Link
                                        href={
                                            academicYearsEdit(academicYear.id)
                                                .url
                                        }
                                    >
                                        <Edit className="mr-2 h-4 w-4" />
                                        Editar
                                    </Link>
                                </Button>
                            )}
                            {hasPermission('academic_years.delete') && (
                                <Button
                                    variant="outline"
                                    className="text-red-600 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-950/30"
                                    onClick={handleDelete}
                                >
                                    <Trash2 className="mr-2 h-4 w-4" />
                                    Eliminar
                                </Button>
                            )}
                        </div>
                    </div>

                    {/* Info general */}
                    <div className="grid gap-4 md:grid-cols-3">
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-neutral-500">
                                    Estado
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {academicYear.is_active ? (
                                    <Badge className="bg-green-100 text-green-700 hover:bg-green-100 dark:bg-green-900/30 dark:text-green-400">
                                        <CheckCircle2 className="mr-1 h-3 w-3" />
                                        Activo
                                    </Badge>
                                ) : (
                                    <span className="text-sm text-neutral-400">
                                        Inactivo
                                    </span>
                                )}
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-neutral-500">
                                    Cupo de Horas
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <span className="text-2xl font-bold">
                                    {academicYear.required_hours}
                                </span>
                                <span className="ml-1 text-sm text-neutral-500">
                                    horas
                                </span>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-neutral-500">
                                    Nombre
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="flex items-center gap-2">
                                    <Calendar className="h-4 w-4 text-neutral-400" />
                                    <span className="font-semibold">
                                        {academicYear.name}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Enlaces rápidos a gestión */}
                    <div className="grid gap-4 md:grid-cols-3">
                        <Link
                            href={
                                schoolTermsIndex({
                                    query: {
                                        academic_year_id: academicYear.id,
                                    },
                                }).url
                            }
                            className="group"
                        >
                            <Card className="cursor-pointer transition-shadow hover:shadow-md">
                                <CardHeader>
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/20">
                                            <Clock className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                        </div>
                                        <div>
                                            <CardTitle className="text-base">
                                                Lapsos Académicos
                                            </CardTitle>
                                            <CardDescription>
                                                {academicYear.school_terms_count ||
                                                    0}{' '}
                                                configurados
                                            </CardDescription>
                                        </div>
                                    </div>
                                </CardHeader>
                            </Card>
                        </Link>

                        <Link
                            href={
                                gradesIndex({
                                    query: {
                                        academic_year_id: academicYear.id,
                                    },
                                }).url
                            }
                            className="group"
                        >
                            <Card className="cursor-pointer transition-shadow hover:shadow-md">
                                <CardHeader>
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-green-50 dark:bg-green-900/20">
                                            <GraduationCap className="h-5 w-5 text-green-600 dark:text-green-400" />
                                        </div>
                                        <div>
                                            <CardTitle className="text-base">
                                                Grados
                                            </CardTitle>
                                            <CardDescription>
                                                {academicYear.grades_count || 0}{' '}
                                                registrados
                                            </CardDescription>
                                        </div>
                                    </div>
                                </CardHeader>
                            </Card>
                        </Link>

                        <Link
                            href={
                                sectionsIndex({
                                    query: {
                                        academic_year_id: academicYear.id,
                                    },
                                }).url
                            }
                            className="group"
                        >
                            <Card className="cursor-pointer transition-shadow hover:shadow-md">
                                <CardHeader>
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-50 dark:bg-purple-900/20">
                                            <Layers className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                        </div>
                                        <div>
                                            <CardTitle className="text-base">
                                                Secciones
                                            </CardTitle>
                                            <CardDescription>
                                                {academicYear.sections_count ||
                                                    0}{' '}
                                                disponibles
                                            </CardDescription>
                                        </div>
                                    </div>
                                </CardHeader>
                            </Card>
                        </Link>
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
