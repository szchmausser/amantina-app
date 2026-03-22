import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Calendar,
    ChevronRight,
    Clock,
    Edit,
    GraduationCap,
    Layers,
    Plus,
    Settings,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Section {
    id: number;
    name: string;
}

interface Grade {
    id: number;
    name: string;
    order: number;
    sections: Section[];
}

interface SchoolTerm {
    id: number;
    term_number: number;
    start_date: string;
    end_date: string;
}

interface AcademicYear {
    id: number;
    name: string;
    is_active: boolean;
    required_hours: number;
    school_terms: SchoolTerm[];
    grades: Grade[];
}

interface Props {
    academicYear: AcademicYear;
}

export default function AcademicYearShow({ academicYear }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Años Escolares', href: '/admin/academic-years' },
        { title: academicYear.name, href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Año Escolar: ${academicYear.name}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Header Section */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild className="-ml-2">
                            <Link href="/admin/academic-years">
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                    {academicYear.name}
                                </h1>
                                {academicYear.is_active && (
                                    <Badge className="bg-green-100 text-green-700 hover:bg-green-100 dark:bg-green-900/30 dark:text-green-400">
                                        Activo
                                    </Badge>
                                )}
                            </div>
                            <p className="text-sm text-neutral-500">
                                Meta Institucional: {academicYear.required_hours} horas de servicio.
                            </p>
                        </div>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={`/admin/academic-years/${academicYear.id}/edit`}>
                            <Edit className="mr-2 h-4 w-4" />
                            Modificar Ciclo
                        </Link>
                    </Button>
                </div>

                <Tabs defaultValue="structure" className="w-full">
                    <TabsList className="mb-4">
                        <TabsTrigger value="structure" className="flex items-center gap-2">
                            <GraduationCap className="h-4 w-4" />
                            Grados y Secciones
                        </TabsTrigger>
                        <TabsTrigger value="terms" className="flex items-center gap-2">
                            <Clock className="h-4 w-4" />
                            Lapsos Académicos
                        </TabsTrigger>
                        <TabsTrigger value="settings" className="flex items-center gap-2">
                            <Settings className="h-4 w-4" />
                            Configuración
                        </TabsTrigger>
                    </TabsList>

                    {/* Grados y Secciones Content */}
                    <TabsContent value="structure" className="space-y-4">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-semibold">Organización del Plantel</h2>
                            <Button size="sm" asChild>
                                <Link href={`/admin/grades/create?academic_year_id=${academicYear.id}`}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Nuevo Grado
                                </Link>
                            </Button>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {academicYear.grades?.length > 0 ? (
                                academicYear.grades.map((grade) => (
                                    <Card key={grade.id} className="overflow-hidden">
                                        <CardHeader className="bg-neutral-50/50 pb-3 dark:bg-neutral-800/30">
                                            <div className="flex items-center justify-between">
                                                <CardTitle className="text-base">{grade.name}</CardTitle>
                                                <Button variant="ghost" size="icon" className="h-8 w-8" asChild>
                                                    <Link href={`/admin/grades/${grade.id}/edit`}>
                                                        <Edit className="h-3 w-3" />
                                                    </Link>
                                                </Button>
                                            </div>
                                            <CardDescription>Orden: {grade.order}</CardDescription>
                                        </CardHeader>
                                        <CardContent className="pt-4">
                                            <div className="space-y-2">
                                                <div className="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-neutral-500">
                                                    <span>Secciones</span>
                                                    <span>{grade.sections?.length || 0}</span>
                                                </div>
                                                <div className="flex flex-wrap gap-2">
                                                    {grade.sections?.map((section) => (
                                                        <Badge
                                                            key={section.id}
                                                            variant="outline"
                                                            className="flex items-center gap-1.5 px-2 py-1"
                                                        >
                                                            {section.name}
                                                            <Link
                                                                href={`/admin/sections/${section.id}/edit`}
                                                                className="text-neutral-400 hover:text-neutral-600"
                                                            >
                                                                <Edit className="h-2.5 w-2.5" />
                                                            </Link>
                                                        </Badge>
                                                    ))}
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="h-6 px-2 text-[10px] dashed"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={`/admin/sections/create?grade_id=${grade.id}&academic_year_id=${academicYear.id}`}
                                                        >
                                                            <Plus className="mr-1 h-2.5 w-2.5" />
                                                            Añadir
                                                        </Link>
                                                    </Button>
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>
                                ))
                            ) : (
                                <div className="col-span-full flex flex-col items-center justify-center rounded-xl border border-dashed border-neutral-200 py-12 dark:border-neutral-800">
                                    <div className="rounded-full bg-neutral-100 p-3 dark:bg-neutral-800">
                                        <Layers className="h-6 w-6 text-neutral-400" />
                                    </div>
                                    <p className="mt-2 text-sm text-neutral-500">No hay grados registrados aún.</p>
                                </div>
                            )}
                        </div>
                    </TabsContent>

                    {/* Lapsos Académicos Content */}
                    <TabsContent value="terms" className="space-y-4">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-semibold">Calendario de Lapsos</h2>
                            <Button size="sm" asChild>
                                <Link href={`/admin/school-terms/create?academic_year_id=${academicYear.id}`}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Nuevo Lapso
                                </Link>
                            </Button>
                        </div>

                        <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-neutral-50 dark:bg-neutral-800/50">
                                    <tr>
                                        <th className="px-6 py-3 font-semibold text-neutral-600 dark:text-neutral-300">
                                            Lapso / Periodo
                                        </th>
                                        <th className="px-6 py-3 font-semibold text-neutral-600 dark:text-neutral-300">
                                            Fecha Inicio
                                        </th>
                                        <th className="px-6 py-3 font-semibold text-neutral-600 dark:text-neutral-300">
                                            Fecha Cierre
                                        </th>
                                        <th className="px-6 py-3 font-right text-neutral-600 dark:text-neutral-300">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-sidebar-border/70">
                                    {academicYear.school_terms?.length > 0 ? (
                                        academicYear.school_terms.map((term) => (
                                            <tr key={term.id} className="hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30">
                                                <td className="px-6 py-4">
                                                    <div className="flex items-center gap-2">
                                                        <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-50 text-xs font-bold text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                                                            {term.term_number}
                                                        </div>
                                                        <span className="font-medium text-neutral-900 dark:text-neutral-100">
                                                            Lapso {term.term_number}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 text-neutral-600 dark:text-neutral-400">
                                                    {term.start_date}
                                                </td>
                                                <td className="px-6 py-4 text-neutral-600 dark:text-neutral-400">
                                                    {term.end_date}
                                                </td>
                                                <td className="px-6 py-4 text-right">
                                                    <Button variant="ghost" size="icon" asChild className="h-8 w-8">
                                                        <Link href={`/admin/school-terms/${term.id}/edit`}>
                                                            <Edit className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan={4} className="px-6 py-12 text-center text-neutral-500">
                                                No hay lapsos registrados para este año.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </TabsContent>

                    {/* Settings Content (Simplificado por ahora) */}
                    <TabsContent value="settings">
                        <Card>
                            <CardHeader>
                                <CardTitle>Parámetros Generales</CardTitle>
                                <CardDescription>Configuración avanzada del año escolar.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center justify-between rounded-lg border p-4">
                                    <div className="space-y-1">
                                        <p className="text-sm font-medium">Nombre del Ciclo</p>
                                        <p className="text-xs text-neutral-500">{academicYear.name}</p>
                                    </div>
                                    <Button variant="ghost" size="sm" asChild>
                                        <Link href={`/admin/academic-years/${academicYear.id}/edit`}>Editar</Link>
                                    </Button>
                                </div>
                                <div className="flex items-center justify-between rounded-lg border p-4">
                                    <div className="space-y-1">
                                        <p className="text-sm font-medium">Estado</p>
                                        <p className="text-xs text-neutral-500">
                                            {academicYear.is_active ? 'Periodo vigente' : 'Inactivo'}
                                        </p>
                                    </div>
                                    <Badge variant={academicYear.is_active ? 'default' : 'secondary'}>
                                        {academicYear.is_active ? 'En curso' : 'Pasado/Futuro'}
                                    </Badge>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}

