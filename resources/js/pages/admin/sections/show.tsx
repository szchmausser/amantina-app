import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Edit, Users, UserPlus, Plus } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem } from '@/types';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Enrollment {
    id: number;
    student: User;
}

interface TeacherAssignment {
    id: number;
    teacher: User;
}

interface AcademicYear {
    id: number;
    name: string;
    is_active: boolean;
}

interface Grade {
    id: number;
    name: string;
}

interface Section {
    id: number;
    name: string;
    grade: Grade;
    enrollments: Enrollment[];
    teacher_assignments: TeacherAssignment[];
}

interface Props {
    section: Section;
    academicYear: AcademicYear;
}

export default function SectionShow({ section, academicYear }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Años Escolares', href: '/admin/academic-years' },
        { title: academicYear.name, href: `/admin/academic-years/${academicYear.id}` },
        { title: `${section.grade.name} - ${section.name}`, href: '#' },
    ];

    const sortedEnrollments = [...section.enrollments].sort((a, b) => 
        a.student.name.localeCompare(b.student.name)
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Ficha de Sección | ${section.name}`} />

            <SettingsLayout>
                <div className="flex flex-col gap-6">
                    {/* Header Section */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-center gap-4">
                            <Button variant="ghost" size="icon" asChild className="-ml-2">
                                <Link href={`/admin/academic-years/${academicYear.id}`}>
                                    <ArrowLeft className="h-4 w-4" />
                                </Link>
                            </Button>
                            <div>
                                <div className="flex items-center gap-2">
                                    <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                        {section.name}
                                    </h1>
                                    <Badge variant="secondary">
                                        {section.grade.name}
                                    </Badge>
                                </div>
                                <p className="text-sm text-neutral-500">
                                    Perteneciente al Año Escolar: {academicYear.name}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {/* Tarjeta Profesores */}
                        <div className="md:col-span-1 space-y-6">
                            <Card>
                                <CardHeader className="bg-neutral-50/50 pb-4 dark:bg-neutral-800/30">
                                    <div className="flex items-center justify-between">
                                        <CardTitle className="flex items-center gap-2 text-lg">
                                            <Users className="h-5 w-5 text-blue-500" />
                                            Profesores (Staff)
                                        </CardTitle>
                                        <Badge variant="outline">{section.teacher_assignments.length}</Badge>
                                    </div>
                                    <CardDescription>
                                        Docentes y guías asignados a esta sección.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="pt-4 p-0">
                                    {section.teacher_assignments.length > 0 ? (
                                        <div className="divide-y">
                                            {section.teacher_assignments.map((assignment) => (
                                                <div key={assignment.id} className="flex items-center gap-3 p-4 hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors">
                                                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-400 font-semibold text-xs">
                                                        {assignment.teacher.name.charAt(0)}
                                                    </div>
                                                    <div className="flex-1 min-w-0">
                                                        <p className="text-sm font-medium text-neutral-900 dark:text-neutral-100 truncate">
                                                            {assignment.teacher.name}
                                                        </p>
                                                        <p className="text-xs text-neutral-500 truncate">
                                                            {assignment.teacher.email}
                                                        </p>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="p-6 text-center text-neutral-500 text-sm">
                                            No hay profesores asignados.
                                        </div>
                                    )}
                                    <div className="p-4 border-t bg-neutral-50/30 dark:bg-neutral-800/30">
                                        <Button variant="outline" className="w-full" size="sm" asChild>
                                            <Link href={`/admin/teacher-assignments/create?grade_id=${section.grade.id}&section_id=${section.id}`}>
                                                <UserPlus className="mr-2 h-4 w-4" />
                                                Asignar Profesor
                                            </Link>
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Tarjeta Alumnos */}
                        <div className="md:col-span-2">
                            <Card className="h-full flex flex-col">
                                <CardHeader className="bg-neutral-50/50 pb-4 dark:bg-neutral-800/30 border-b">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <CardTitle className="text-lg">Alumnos Inscritos</CardTitle>
                                            <CardDescription className="mt-1">
                                                Lista de matrícula actual para esta sección.
                                            </CardDescription>
                                        </div>
                                        <Badge variant="secondary" className="text-base px-3 py-1">
                                            {section.enrollments.length} alumnos
                                        </Badge>
                                    </div>
                                </CardHeader>
                                <CardContent className="p-0 flex-1">
                                    {sortedEnrollments.length > 0 ? (
                                        <div className="overflow-x-auto">
                                            <table className="w-full text-left text-sm whitespace-nowrap">
                                                <thead className="bg-neutral-100/50 dark:bg-neutral-800/50 sticky top-0">
                                                    <tr>
                                                        <th className="px-6 py-3 font-semibold text-neutral-600 dark:text-neutral-400 w-16 text-center">N°</th>
                                                        <th className="px-6 py-3 font-semibold text-neutral-600 dark:text-neutral-400">Nombre del Alumno</th>
                                                        <th className="px-6 py-3 font-semibold text-neutral-600 dark:text-neutral-400">Identificación (Email/Cédula)</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                                    {sortedEnrollments.map((enrollment, index) => (
                                                        <tr key={enrollment.id} className="hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30 transition-colors">
                                                            <td className="px-6 py-3 text-center text-neutral-500 font-medium">
                                                                {index + 1}
                                                            </td>
                                                            <td className="px-6 py-3 text-neutral-900 dark:text-neutral-100">
                                                                {enrollment.student.name}
                                                            </td>
                                                            <td className="px-6 py-3 text-neutral-500 font-mono text-xs">
                                                                {enrollment.student.email}
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    ) : (
                                        <div className="flex flex-col items-center justify-center h-48 sm:h-64 border-2 border-dashed border-neutral-200 dark:border-neutral-800 rounded-lg m-6">
                                            <Users className="h-8 w-8 text-neutral-300 mb-3" />
                                            <p className="text-sm font-medium text-neutral-900 dark:text-neutral-100">Sección vacía</p>
                                            <p className="text-xs text-neutral-500 mt-1 mb-4">Aún no hay alumnos inscritos aquí.</p>
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={`/admin/enrollments/create?grade_id=${section.grade.id}&section_id=${section.id}`}>
                                                    Inscribir Alumno
                                                </Link>
                                            </Button>
                                        </div>
                                    )}
                                </CardContent>
                                
                                {/* Footer for action */}
                                {section.enrollments.length > 0 && (
                                    <div className="p-4 border-t bg-neutral-50/30 dark:bg-neutral-800/30 flex justify-end">
                                        <Button variant="outline" size="sm" asChild>
                                            <Link href={`/admin/enrollments/create?grade_id=${section.grade.id}&section_id=${section.id}`}>
                                                <Plus className="mr-2 h-4 w-4" />
                                                Añadir Alumno Individual
                                            </Link>
                                        </Button>
                                    </div>
                                )}
                            </Card>
                        </div>
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
