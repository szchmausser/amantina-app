import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, GraduationCap, Pencil, Plus, Settings2, Trash2 } from 'lucide-react';
import { useState, useEffect, useRef } from 'react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem } from '@/types';
import {
    DataTable,
    DataTableHead,
    DataTableTH,
    DataTableBody,
    DataTableTR,
    DataTableTD,
    type PaginationInfo,
} from '@/components/ui/data-table';

interface Section {
    id: number;
    name: string;
}

interface Grade {
    id: number;
    name: string;
    order: number;
    academic_year_id: number;
    sections: Section[];
    enrollments_count: number;
}

interface AcademicYear {
    id: number;
    name: string;
}

interface PaginatedGrades {
    data: Grade[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
}

interface Props {
    grades: PaginatedGrades;
    academicYears: AcademicYear[];
    selectedYearId: number;
}

export default function GradesIndex({
    grades,
    academicYears,
    selectedYearId,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Grados', href: '/admin/grades' },
    ];

    const [perPage, setPerPage] = useState(grades.per_page || 10);
    const isFirstPerPageRender = useRef(true);
    const [confirmDialogOpen, setConfirmDialogOpen] = useState(false);
    const [pendingDeleteId, setPendingDeleteId] = useState<number | null>(null);

    useEffect(() => {
        if (isFirstPerPageRender.current) {
            isFirstPerPageRender.current = false;
            return;
        }

        router.get(
            '/admin/grades',
            { academic_year_id: selectedYearId, per_page: perPage },
            { preserveState: true, replace: true },
        );
    }, [perPage]);

    const handleYearChange = (yearId: string) => {
        router.get(
            '/admin/grades',
            { academic_year_id: yearId, per_page: perPage },
            { preserveState: true },
        );
    };

    const handleDelete = (gradeId: number) => {
        setPendingDeleteId(gradeId);
        setConfirmDialogOpen(true);
    };

    const confirmDelete = () => {
        if (!pendingDeleteId) return;
        router.delete(`/admin/grades/${pendingDeleteId}`, {
            preserveState: true,
        });
        setConfirmDialogOpen(false);
        setPendingDeleteId(null);
    };

    const pagination: PaginationInfo | undefined =
        grades.last_page > 1
            ? {
                  links: grades.links,
                  total: grades.total,
                  current_page: grades.current_page,
                  last_page: grades.last_page,
              }
            : undefined;

    const tableColumns = (
        <>
            <DataTableHead>
                <DataTableTH className="w-12">#</DataTableTH>
                <DataTableTH>Grado</DataTableTH>
                <DataTableTH className="w-28 text-center">Alumnos</DataTableTH>
                <DataTableTH className="w-48 text-right">Acciones</DataTableTH>
            </DataTableHead>
            <DataTableBody>
                {grades.data.map((grade, index) => (
                    <DataTableTR key={grade.id}>
                        <DataTableTD className="font-mono text-xs text-neutral-400">
                            {(grades.current_page - 1) * perPage + index + 1}
                        </DataTableTD>
                        <DataTableTD>
                            <div className="flex items-center gap-2">
                                <span className="font-semibold text-neutral-900 dark:text-neutral-100">
                                    {grade.name}
                                </span>
                                <span className="flex flex-wrap items-center gap-1">
                                    {grade.sections?.length > 0 ? (
                                        grade.sections.map((s) => (
                                            <Link key={s.id} href={`/admin/sections/${s.id}`}>
                                                <Badge variant="secondary" className="cursor-pointer text-xs bg-sky-50 text-sky-700 border-sky-200 hover:bg-sky-100 dark:bg-sky-950 dark:text-sky-300 dark:border-sky-800 dark:hover:bg-sky-900">
                                                    Sección {s.name}
                                                </Badge>
                                            </Link>
                                        ))
                                    ) : (
                                        <span className="text-xs italic text-neutral-400">Sin secciones</span>
                                    )}
                                    <Link href={`/admin/academic-years/${grade.academic_year_id}`}>
                                        <Badge variant="outline" className="cursor-pointer text-xs hover:bg-neutral-100 dark:hover:bg-neutral-800">
                                            {academicYears.find(
                                                (y) => y.id === grade.academic_year_id,
                                            )?.name || 'N/A'}
                                        </Badge>
                                    </Link>
                                </span>
                            </div>
                        </DataTableTD>
                        <DataTableTD className="text-center">
                            <span className="font-semibold text-neutral-700 dark:text-neutral-300">
                                {grade.enrollments_count}
                            </span>
                        </DataTableTD>
                        <DataTableTD className="text-right">
                            <div className="flex items-center justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-8 w-8 text-neutral-500 hover:text-blue-600"
                                    asChild
                                    title="Añadir sección"
                                >
                                    <Link
                                        href={`/admin/sections/create?grade_id=${grade.id}&academic_year_id=${grade.academic_year_id}`}
                                    >
                                        <Plus className="h-4 w-4" />
                                        <span className="sr-only">
                                            Añadir sección
                                        </span>
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-8 w-8 text-neutral-500 hover:text-blue-600"
                                    asChild
                                    title="Editar"
                                >
                                    <Link
                                        href={`/admin/grades/${grade.id}/edit`}
                                    >
                                        <Pencil className="h-4 w-4" />
                                        <span className="sr-only">Editar</span>
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-8 w-8 text-red-500 hover:bg-red-50 hover:text-red-600"
                                    onClick={() => handleDelete(grade.id)}
                                >
                                    <Trash2 className="h-4 w-4" />
                                    <span className="sr-only">Eliminar</span>
                                </Button>
                            </div>
                        </DataTableTD>
                    </DataTableTR>
                ))}
            </DataTableBody>
        </>
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Grados" />

            <SettingsLayout>
                <div className="flex flex-col gap-6">
                    {/* Encabezado */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                Grados Académicos
                            </h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Configura los niveles del plantel por año
                                escolar.
                            </p>
                        </div>
                        <div className="flex gap-2">
                            <Button variant="outline" size="sm" onClick={() => window.history.back()}>
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver
                            </Button>
                            <Button variant="outline" size="sm" asChild>
                                <Link href="/admin/grade-definitions">
                                    <Settings2 className="mr-2 h-4 w-4" />
                                    Definiciones de grados
                                </Link>
                            </Button>
                            <Button size="sm" asChild>
                                <Link
                                    href={`/admin/grades/create?academic_year_id=${selectedYearId}`}
                                >
                                    <Plus className="mr-2 h-4 w-4" />
                                    Nuevo Grado
                                </Link>
                            </Button>
                        </div>
                    </div>

                    {/* Filtro */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <div className="w-full sm:w-48">
                            <Select
                                value={selectedYearId.toString()}
                                onValueChange={handleYearChange}
                                data-test="academic-year-filter"
                            >
                                <SelectTrigger className="h-10" data-test="academic-year-filter-trigger">
                                    <SelectValue placeholder="Seleccionar año" />
                                </SelectTrigger>
                                <SelectContent>
                                    {academicYears.map((year) => (
                                        <SelectItem
                                            key={year.id}
                                            value={year.id.toString()}
                                        >
                                            {year.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    {/* Tabla */}
                    <DataTable
                        data={grades.data}
                        columns={tableColumns}
                        pagination={pagination}
                        onPageChange={(_, url) => {
                            router.get(
                                url,
                                { per_page: perPage },
                                {
                                    preserveState: true,
                                    replace: true,
                                },
                            );
                        }}
                        perPage={perPage}
                        onPerPageChange={setPerPage}
                        perPageOptions={[10, 15, 25, 50, 100]}
                        emptyMessage="No se encontraron grados para este periodo."
                    />
                </div>
            </SettingsLayout>

            <AlertDialog
                open={confirmDialogOpen}
                onOpenChange={setConfirmDialogOpen}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>¿Eliminar grado?</AlertDialogTitle>
                        <AlertDialogDescription>
                            <strong>Advertencia:</strong> Esta acción eliminará
                            el grado y todas sus secciones asociadas. Esta
                            operación no se puede deshacer.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={confirmDelete}
                            className="bg-red-600 hover:bg-red-700"
                            data-test="confirm-delete-button"
                        >
                            Eliminar
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
