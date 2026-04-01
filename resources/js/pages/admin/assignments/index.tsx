import { Head, Link, router } from '@inertiajs/react';
import { BookUser, Plus, Search, Trash2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
}

interface Section {
    id: number;
    name: string;
}

interface Assignment {
    id: number;
    teacher: User;
    grade: Grade;
    section: Section;
}

interface AcademicYear {
    id: number;
    name: string;
}

interface Props {
    activeYear: AcademicYear | null;
    assignments: Assignment[];
}

export default function TeacherAssignmentsIndex({ activeYear, assignments }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Asignaciones Docentes', href: '/admin/teacher-assignments' },
    ];

    const handleDelete = (id: number) => {
        if (confirm('¿Estás seguro de que deseas eliminar la asignación de este profesor?')) {
            router.delete(`/admin/teacher-assignments/${id}`);
        }
    };

    if (!activeYear) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Asignaciones Docentes" />
                <div className="flex h-full flex-1 flex-col items-center justify-center p-8">
                    <div className="flex max-w-md flex-col items-center text-center">
                        <div className="mb-4 rounded-full bg-neutral-100 p-4 dark:bg-neutral-800">
                            <BookUser className="h-8 w-8 text-neutral-400" />
                        </div>
                        <h2 className="text-xl font-bold tracking-tight">No hay un año escolar activo</h2>
                        <p className="mt-2 text-neutral-500">
                            Para gestionar asignaciones, primero debes activar un año escolar.
                        </p>
                        <Button className="mt-6" asChild>
                            <Link href="/admin/academic-years">Ir a Años Escolares</Link>
                        </Button>
                    </div>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Asignaciones Docentes | ${activeYear.name}`} />

            <div className="mx-auto max-w-5xl p-4 lg:p-8 space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                            Asignaciones Docentes
                        </h1>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                            Año Escolar: <span className="font-semibold text-neutral-700 dark:text-neutral-300">{activeYear.name}</span>
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/admin/teacher-assignments/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Nueva Asignación
                        </Link>
                    </Button>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-neutral-500">
                                Total Asignaciones
                            </CardTitle>
                            <BookUser className="h-4 w-4 text-neutral-400" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{assignments.length}</div>
                        </CardContent>
                    </Card>
                </div>

                <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border relative">
                    {assignments.length === 0 ? (
                        <div className="flex flex-col items-center justify-center px-4 py-16 text-center sm:px-6 lg:px-8">
                            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 dark:bg-neutral-800">
                                <Search className="h-6 w-6 text-neutral-500" />
                            </div>
                            <h3 className="mt-4 text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                No hay asignaciones registradas
                            </h3>
                            <p className="mt-1 text-sm text-neutral-500">
                                No se encontraron profesores asignados a secciones en este año escolar.
                            </p>
                        </div>
                    ) : (
                        <table className="w-full text-left text-sm">
                            <thead className="bg-neutral-50 dark:bg-neutral-800/50">
                                <tr>
                                    <th className="px-6 py-3 font-semibold text-neutral-600 dark:text-neutral-300">Profesor</th>
                                    <th className="px-6 py-3 font-semibold text-neutral-600 dark:text-neutral-300">Cédula</th>
                                    <th className="px-6 py-3 font-semibold text-neutral-600 dark:text-neutral-300">Grado</th>
                                    <th className="px-6 py-3 font-semibold text-neutral-600 dark:text-neutral-300">Sección</th>
                                    <th className="px-6 py-3 text-right font-semibold text-neutral-600 dark:text-neutral-300">Acciones</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-sidebar-border/70">
                                {assignments.map((assignment) => (
                                    <tr key={assignment.id} className="hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30">
                                        <td className="px-6 py-4 font-medium text-neutral-900 dark:text-neutral-100">
                                            {assignment.teacher.name}
                                        </td>
                                        <td className="px-6 py-4 font-mono text-neutral-600 dark:text-neutral-400">
                                            {assignment.teacher.cedula}
                                        </td>
                                        <td className="px-6 py-4 text-neutral-600 dark:text-neutral-400">
                                            {assignment.grade.name}
                                        </td>
                                        <td className="px-6 py-4">
                                            <Badge variant="outline">{assignment.section.name}</Badge>
                                        </td>
                                        <td className="px-6 py-4 text-right">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-500 dark:hover:bg-red-950/50 dark:hover:text-red-400"
                                                onClick={() => handleDelete(assignment.id)}
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
