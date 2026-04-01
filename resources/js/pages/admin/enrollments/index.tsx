import { Head, Link, router } from '@inertiajs/react';
import { BookOpen, GraduationCap, Plus, Search, Trash2, Users } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface User {
    id: number;
    name: string;
    cedula: string;
}

interface Grade {
    id: number;
    name: string;
    sections: Section[];
}

interface Section {
    id: number;
    name: string;
    grade_id: number;
}

interface Enrollment {
    id: number;
    student: User;
    grade: Grade;
    section: Section;
}

interface AcademicYear {
    id: number;
    name: string;
}

interface Props {
    activeYear: AcademicYear | null;
    hasStructure: boolean;
    enrollments: Enrollment[];
    grades: Grade[];
    totalEnrolled: number;
    pendingStudents: number;
    selectedGradeId: number | null;
    selectedSectionId: number | null;
}

export default function EnrollmentsIndex({
    activeYear,
    hasStructure,
    enrollments,
    grades,
    totalEnrolled,
    pendingStudents,
    selectedGradeId,
    selectedSectionId,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Inscripciones', href: '/admin/enrollments' },
    ];

    const handleDelete = (id: number) => {
        if (confirm('¿Estás seguro de que deseas eliminar la inscripción de este alumno?')) {
            router.delete(`/admin/enrollments/${id}`);
        }
    };

    const handleFilterGrade = (val: string) => {
        router.get(
            '/admin/enrollments',
            { grade_id: val === 'all' ? null : val, section_id: null },
            { preserveState: true }
        );
    };

    const handleFilterSection = (val: string) => {
        router.get(
            '/admin/enrollments',
            { grade_id: selectedGradeId, section_id: val === 'all' ? null : val },
            { preserveState: true }
        );
    };

    const availableSections = grades.find((g) => g.id === selectedGradeId)?.sections || [];

    if (!activeYear) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Inscripciones" />
                <div className="flex h-full flex-1 flex-col items-center justify-center p-8">
                    <div className="flex max-w-md flex-col items-center text-center">
                        <div className="mb-4 rounded-full bg-neutral-100 p-4 dark:bg-neutral-800">
                            <BookOpen className="h-8 w-8 text-neutral-400" />
                        </div>
                        <h2 className="text-xl font-bold tracking-tight">No hay un año escolar activo</h2>
                        <p className="mt-2 text-neutral-500">
                            Para gestionar inscripciones, primero debes activar un año escolar.
                        </p>
                        <Button className="mt-6" asChild>
                            <Link href="/admin/academic-years">Ir a Años Escolares</Link>
                        </Button>
                    </div>
                </div>
            </AppLayout>
        );
    }

    if (!hasStructure) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Inscripciones" />
                <div className="mx-auto max-w-5xl p-4 lg:p-8 space-y-6">
                    <div className="rounded-lg border border-yellow-200 bg-yellow-50 p-6 dark:border-yellow-900/50 dark:bg-yellow-900/20">
                        <div className="flex gap-4">
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900/50">
                                <span className="text-xl">⚠️</span>
                            </div>
                            <div>
                                <h3 className="text-lg font-semibold text-yellow-800 dark:text-yellow-200">
                                    Estructura académica incompleta
                                </h3>
                                <p className="mt-1 text-yellow-700 dark:text-yellow-300">
                                    Antes de inscribir alumnos, debes configurar los grados y secciones para el año escolar activo ({activeYear.name}).
                                </p>
                                <Button variant="outline" className="mt-4 border-yellow-300 bg-white text-yellow-800 hover:bg-yellow-100 dark:border-yellow-700 dark:bg-yellow-950 dark:text-yellow-200" asChild>
                                    <Link href={`/admin/academic-years/${activeYear.id}`}>
                                        Ir a configurar Grados y Secciones
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Inscripciones | ${activeYear.name}`} />

            <div className="mx-auto max-w-5xl p-4 lg:p-8 space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                            Inscripciones de Alumnos
                        </h1>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                            Año Escolar: <span className="font-semibold text-neutral-700 dark:text-neutral-300">{activeYear.name}</span> (Activo)
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/admin/enrollments/create">
                                <Plus className="mr-2 h-4 w-4" />
                                Nuevo Ingreso
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href="/admin/enrollments/promote">
                                <GraduationCap className="mr-2 h-4 w-4" />
                                Panel de Promoción
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-neutral-500">
                                Total Inscritos en el Año Activo
                            </CardTitle>
                            <Users className="h-4 w-4 text-neutral-400" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{totalEnrolled}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-neutral-500">
                                Pendientes por Inscribir
                            </CardTitle>
                            <Users className="h-4 w-4 text-neutral-400" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-yellow-600 dark:text-yellow-500">{pendingStudents}</div>
                            <p className="text-xs text-neutral-500">alumnos registrados en el sistema sin inscripción activa.</p>
                        </CardContent>
                    </Card>
                </div>

                <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div className="w-full sm:w-1/3">
                        <Select
                            value={selectedGradeId ? selectedGradeId.toString() : 'all'}
                            onValueChange={handleFilterGrade}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Todos los Grados" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos los Grados</SelectItem>
                                {grades.map((g) => (
                                    <SelectItem key={g.id} value={g.id.toString()}>{g.name}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="w-full sm:w-1/3">
                        <Select
                            value={selectedSectionId ? selectedSectionId.toString() : 'all'}
                            onValueChange={handleFilterSection}
                            disabled={!selectedGradeId || availableSections.length === 0}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Todas las Secciones" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todas las Secciones</SelectItem>
                                {availableSections.map((s) => (
                                    <SelectItem key={s.id} value={s.id.toString()}>{s.name}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border relative">
                    {enrollments.length === 0 ? (
                        <div className="flex flex-col items-center justify-center px-4 py-16 text-center sm:px-6 lg:px-8">
                            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 dark:bg-neutral-800">
                                <Search className="h-6 w-6 text-neutral-500" />
                            </div>
                            <h3 className="mt-4 text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                No hay inscripciones registradas
                            </h3>
                            <p className="mt-1 text-sm text-neutral-500">
                                No se encontraron alumnos inscritos en esta sección/grado.
                            </p>
                        </div>
                    ) : (
                        <table className="w-full text-left text-sm">
                            <thead className="bg-neutral-50 dark:bg-neutral-800/50">
                                <tr>
                                    <th className="px-6 py-3 font-semibold text-neutral-600 dark:text-neutral-300">Alumno</th>
                                    <th className="px-6 py-3 font-semibold text-neutral-600 dark:text-neutral-300">Cédula</th>
                                    <th className="px-6 py-3 font-semibold text-neutral-600 dark:text-neutral-300">Grado</th>
                                    <th className="px-6 py-3 font-semibold text-neutral-600 dark:text-neutral-300">Sección</th>
                                    <th className="px-6 py-3 text-right font-semibold text-neutral-600 dark:text-neutral-300">Acciones</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-sidebar-border/70">
                                {enrollments.map((enr) => (
                                    <tr key={enr.id} className="hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30">
                                        <td className="px-6 py-4 font-medium text-neutral-900 dark:text-neutral-100">
                                            {enr.student.name}
                                        </td>
                                        <td className="px-6 py-4 font-mono text-neutral-600 dark:text-neutral-400">
                                            {enr.student.cedula}
                                        </td>
                                        <td className="px-6 py-4 text-neutral-600 dark:text-neutral-400">
                                            {enr.grade.name}
                                        </td>
                                        <td className="px-6 py-4">
                                            <Badge variant="outline">{enr.section.name}</Badge>
                                        </td>
                                        <td className="px-6 py-4 text-right">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-500 dark:hover:bg-red-950/50 dark:hover:text-red-400"
                                                onClick={() => handleDelete(enr.id)}
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
