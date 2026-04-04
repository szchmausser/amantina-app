import { Head, Link, router } from '@inertiajs/react';
import { Edit, GraduationCap, Plus, Trash2 } from 'lucide-react';
import { useState, useEffect, useRef } from 'react';
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
        if (
            confirm(
                '¿Estás seguro de que deseas eliminar este grado? Se eliminarán también todas sus secciones.',
            )
        ) {
            router.delete(`/admin/grades/${gradeId}`, {
                preserveState: true,
            });
        }
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
                <DataTableTH className="w-16">#</DataTableTH>
                <DataTableTH>Grado</DataTableTH>
                <DataTableTH className="w-24">Orden</DataTableTH>
                <DataTableTH>Secciones</DataTableTH>
                <DataTableTH className="w-48 text-right">Acciones</DataTableTH>
            </DataTableHead>
            <DataTableBody>
                {grades.data.map((grade, index) => (
                    <DataTableTR key={grade.id}>
                        <DataTableTD className="font-mono text-xs text-neutral-400">
                            {(grades.current_page - 1) * perPage + index + 1}
                        </DataTableTD>
                        <DataTableTD>
                            <div className="flex items-center gap-3">
                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/20">
                                    <GraduationCap className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                </div>
                                <span className="font-semibold text-neutral-900 dark:text-neutral-100">
                                    {grade.name}
                                </span>
                            </div>
                        </DataTableTD>
                        <DataTableTD className="text-neutral-600 dark:text-neutral-400">
                            {grade.order}
                        </DataTableTD>
                        <DataTableTD>
                            <div className="flex flex-wrap items-center gap-1">
                                {grade.sections?.length > 0 ? (
                                    grade.sections.map((section) => (
                                        <Link
                                            key={section.id}
                                            href={`/admin/sections/${section.id}`}
                                            className="transition-opacity hover:opacity-75"
                                        >
                                            <Badge
                                                variant="secondary"
                                                className="cursor-pointer text-xs"
                                            >
                                                {section.name}
                                            </Badge>
                                        </Link>
                                    ))
                                ) : (
                                    <span className="text-xs text-neutral-400 italic">
                                        Sin secciones
                                    </span>
                                )}
                            </div>
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
                                        <Edit className="h-4 w-4" />
                                        <span className="sr-only">Editar</span>
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-8 w-8 text-red-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/30"
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
                        <Button asChild>
                            <Link
                                href={`/admin/grades/create?academic_year_id=${selectedYearId}`}
                            >
                                <Plus className="mr-2 h-4 w-4" />
                                Nuevo Grado
                            </Link>
                        </Button>
                    </div>

                    {/* Filtro */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <div className="w-full sm:w-48">
                            <Select
                                value={selectedYearId.toString()}
                                onValueChange={handleYearChange}
                            >
                                <SelectTrigger className="h-10">
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
        </AppLayout>
    );
}
