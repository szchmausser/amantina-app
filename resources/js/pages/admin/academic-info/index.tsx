import { Head } from '@inertiajs/react';
import {
    BookOpen,
    Calendar,
    ChevronDown,
    ChevronRight,
    GraduationCap,
    Users,
    User as UserIcon,
} from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { BreadcrumbItem } from '@/types';

interface Student {
    id: number;
    name: string;
    cedula: string;
}

interface Teacher {
    id: number;
    name: string;
}

interface Section {
    id: number;
    name: string;
    enrollment_count: number;
    students: Student[];
    teachers: Teacher[];
}

interface Grade {
    id: number;
    name: string;
    sections: Section[];
}

interface Props {
    activeYear: {
        id: number;
        name: string;
        start_date: string;
        end_date: string;
        total_enrolled: number;
    } | null;
    currentTerm: {
        id: number;
        term_type_name: string;
        start_date: string;
        end_date: string;
    } | null;
    grades: Grade[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Información Académica',
        href: '/admin/academic-info',
    },
];

export default function AcademicInfo({
    activeYear,
    currentTerm,
    grades,
}: Props) {
    const [openGrades, setOpenGrades] = useState<number[]>([]);

    const toggleGrade = (gradeId: number) => {
        setOpenGrades((prev) =>
            prev.includes(gradeId)
                ? prev.filter((id) => id !== gradeId)
                : [...prev, gradeId],
        );
    };

    if (!activeYear) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Información Académica" />
                <div className="flex h-[450px] flex-col items-center justify-center space-y-4 rounded-xl border-2 border-dashed border-neutral-200 dark:border-neutral-800">
                    <Calendar className="h-12 w-12 text-neutral-400" />
                    <div className="text-center">
                        <h3 className="text-lg font-semibold">
                            No hay Año Escolar activo
                        </h3>
                        <p className="text-sm text-muted-foreground">
                            Debe configurar un año escolar como activo para
                            visualizar la estructura académica.
                        </p>
                    </div>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Información Académica" />

            <div className="space-y-8 p-4 md:p-8">
                {/* Cabecera / Status */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card className="overflow-hidden border-none bg-linear-to-br from-indigo-500 to-purple-600 text-white shadow-lg">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium opacity-90">
                                Año Escolar Activo
                            </CardTitle>
                            <Calendar className="h-5 w-5 opacity-80" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {activeYear.name}
                            </div>
                            <p className="mt-1 text-xs opacity-80">
                                {activeYear.start_date} al {activeYear.end_date}
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="overflow-hidden border-none bg-linear-to-br from-emerald-500 to-teal-600 text-white shadow-lg">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium opacity-90">
                                Lapso Actual
                            </CardTitle>
                            <BookOpen className="h-5 w-5 opacity-80" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {currentTerm
                                    ? currentTerm.term_type_name
                                    : 'Fuera de Período'}
                            </div>
                            <p className="mt-1 text-xs opacity-80">
                                {currentTerm
                                    ? `${currentTerm.start_date} al ${currentTerm.end_date}`
                                    : 'Sin lapso activo actualmente'}
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="hidden border-none bg-linear-to-br from-amber-500 to-orange-600 text-white shadow-lg lg:block">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium opacity-90">
                                Resumen General
                            </CardTitle>
                            <Users className="h-5 w-5 opacity-80" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {activeYear.total_enrolled} Alumnos
                            </div>
                            <p className="mt-1 text-xs opacity-80">
                                {grades.length} Grados ·{' '}
                                {grades.reduce(
                                    (acc, g) => acc + g.sections.length,
                                    0,
                                )}{' '}
                                Secciones
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Estructura por Grados */}
                <div className="space-y-4">
                    <h2 className="text-xl font-bold tracking-tight">
                        Estructura Académica
                    </h2>

                    {grades.length === 0 ? (
                        <div className="rounded-lg border border-dashed p-12 text-center">
                            <GraduationCap className="mx-auto h-8 w-8 text-muted-foreground" />
                            <p className="mt-2 text-sm text-muted-foreground">
                                No se han definido grados para este año escolar.
                            </p>
                        </div>
                    ) : (
                        <div className="space-y-3">
                            {grades.map((grade) => (
                                <Collapsible
                                    key={grade.id}
                                    open={openGrades.includes(grade.id)}
                                    onOpenChange={() => toggleGrade(grade.id)}
                                    className="overflow-hidden rounded-xl border bg-card shadow-sm transition-all duration-200"
                                >
                                    <CollapsibleTrigger asChild>
                                        <div className="flex cursor-pointer items-center justify-between p-4 hover:bg-neutral-50 dark:hover:bg-neutral-900">
                                            <div className="flex items-center space-x-3">
                                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-neutral-100 font-bold text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400">
                                                    {
                                                        grade.name.split(
                                                            ' ',
                                                        )[0][0]
                                                    }
                                                </div>
                                                <div>
                                                    <h3 className="font-semibold">
                                                        {grade.name}
                                                    </h3>
                                                    <p className="text-xs text-muted-foreground">
                                                        {grade.sections.length}{' '}
                                                        Secciones
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="flex items-center space-x-2">
                                                {openGrades.includes(
                                                    grade.id,
                                                ) ? (
                                                    <ChevronDown className="h-5 w-5 text-neutral-400" />
                                                ) : (
                                                    <ChevronRight className="h-5 w-5 text-neutral-400" />
                                                )}
                                            </div>
                                        </div>
                                    </CollapsibleTrigger>

                                    <CollapsibleContent>
                                        <div className="border-t bg-neutral-50/50 p-4 dark:bg-neutral-900/50">
                                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                                {grade.sections.map(
                                                    (section) => (
                                                        <Card
                                                            key={section.id}
                                                            className="group relative overflow-hidden transition-all hover:border-indigo-200 hover:shadow-md dark:hover:border-indigo-900"
                                                        >
                                                            <CardHeader className="pb-2">
                                                                <div className="flex items-center justify-between">
                                                                    <Badge
                                                                        variant="outline"
                                                                        className="bg-white font-bold dark:bg-black"
                                                                    >
                                                                        Sección{' '}
                                                                        {
                                                                            section.name
                                                                        }
                                                                    </Badge>
                                                                    <Users className="h-4 w-4 text-neutral-400 transition-colors group-hover:text-indigo-500" />
                                                                </div>
                                                            </CardHeader>
                                                            <CardContent className="space-y-4">
                                                                <div>
                                                                    <p className="text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                                                        Profesor
                                                                        Asignado
                                                                    </p>
                                                                    <div className="mt-1 flex items-center space-x-2">
                                                                        <div className="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">
                                                                            <UserIcon className="h-3 w-3" />
                                                                        </div>
                                                                        <span className="text-sm font-medium">
                                                                            {section
                                                                                .teachers
                                                                                .length >
                                                                            0
                                                                                ? section.teachers
                                                                                      .map(
                                                                                          (
                                                                                              t,
                                                                                          ) =>
                                                                                              t.name,
                                                                                      )
                                                                                      .join(
                                                                                          ', ',
                                                                                      )
                                                                                : 'Sin asignar'}
                                                                        </span>
                                                                    </div>
                                                                </div>

                                                                <div className="flex flex-col space-y-3">
                                                                    <div className="flex items-center justify-between">
                                                                        <p className="text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                                                            Alumnos
                                                                            Inscritos
                                                                        </p>
                                                                        <span className="text-sm font-bold text-indigo-600 dark:text-indigo-400">
                                                                            {
                                                                                section.enrollment_count
                                                                            }
                                                                        </span>
                                                                    </div>

                                                                    <Dialog>
                                                                        <DialogTrigger
                                                                            asChild
                                                                        >
                                                                            <Button
                                                                                variant="secondary"
                                                                                size="sm"
                                                                                className="w-full text-xs font-semibold shadow-sm transition-all hover:bg-indigo-600 hover:text-white"
                                                                            >
                                                                                Ver
                                                                                Alumnos
                                                                            </Button>
                                                                        </DialogTrigger>
                                                                        <DialogContent className="max-w-2xl">
                                                                            <DialogHeader>
                                                                                <DialogTitle className="flex items-center space-x-2">
                                                                                    <Users className="h-5 w-5" />
                                                                                    <span>
                                                                                        Listado
                                                                                        de
                                                                                        Alumnos
                                                                                        -{' '}
                                                                                        {
                                                                                            grade.name
                                                                                        }{' '}
                                                                                        Sección{' '}
                                                                                        {
                                                                                            section.name
                                                                                        }
                                                                                    </span>
                                                                                </DialogTitle>
                                                                                <DialogDescription>
                                                                                    Alumnos
                                                                                    inscritos
                                                                                    en
                                                                                    el
                                                                                    período
                                                                                    académico
                                                                                    actual.
                                                                                </DialogDescription>
                                                                            </DialogHeader>

                                                                            <div className="mt-4 max-h-[400px] overflow-y-auto">
                                                                                {section
                                                                                    .students
                                                                                    .length ===
                                                                                0 ? (
                                                                                    <p className="py-8 text-center text-sm text-muted-foreground">
                                                                                        No
                                                                                        hay
                                                                                        alumnos
                                                                                        inscritos
                                                                                        en
                                                                                        esta
                                                                                        sección.
                                                                                    </p>
                                                                                ) : (
                                                                                    <table className="w-full border-collapse">
                                                                                        <thead className="sticky top-0 bg-background shadow-sm">
                                                                                            <tr className="border-b text-left text-xs font-semibold text-muted-foreground uppercase">
                                                                                                <th className="px-4 py-3">
                                                                                                    #
                                                                                                </th>
                                                                                                <th className="px-4 py-3 text-right">
                                                                                                    Cédula
                                                                                                </th>
                                                                                                <th className="px-4 py-3">
                                                                                                    Nombre
                                                                                                    Completo
                                                                                                </th>
                                                                                            </tr>
                                                                                        </thead>
                                                                                        <tbody>
                                                                                            {section.students.map(
                                                                                                (
                                                                                                    student,
                                                                                                    idx,
                                                                                                ) => (
                                                                                                    <tr
                                                                                                        key={
                                                                                                            student.id
                                                                                                        }
                                                                                                        className="border-b text-sm transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                                                                                    >
                                                                                                        <td className="px-4 py-3 font-mono text-xs">
                                                                                                            {idx +
                                                                                                                1}
                                                                                                        </td>
                                                                                                        <td className="px-4 py-3 text-right font-mono text-xs">
                                                                                                            {
                                                                                                                student.cedula
                                                                                                            }
                                                                                                        </td>
                                                                                                        <td className="px-4 py-3 font-medium">
                                                                                                            {
                                                                                                                student.name
                                                                                                            }
                                                                                                        </td>
                                                                                                    </tr>
                                                                                                ),
                                                                                            )}
                                                                                        </tbody>
                                                                                    </table>
                                                                                )}
                                                                            </div>
                                                                        </DialogContent>
                                                                    </Dialog>
                                                                </div>
                                                            </CardContent>
                                                        </Card>
                                                    ),
                                                )}
                                            </div>
                                        </div>
                                    </CollapsibleContent>
                                </Collapsible>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
