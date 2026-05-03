import { Head, Link, router } from '@inertiajs/react';
import { Edit, Eye, Layers, Plus, Trash2 } from 'lucide-react';
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

interface Grade {
    id: number;
    name: string;
}

interface AcademicYear {
    id: number;
    name: string;
}

interface Section {
    id: number;
    name: string;
    grade_id: number;
    academic_year_id: number;
    grade?: Grade;
}

interface PaginatedSections {
    data: Section[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
}

interface Props {
    sections: PaginatedSections;
    grades: Grade[];
    academicYears: AcademicYear[];
    selectedYearId: number;
    selectedGradeId: number | null;
}

export default function SectionsIndex({
    sections,
    grades,
    academicYears,
    selectedYearId,
    selectedGradeId,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Secciones', href: '/admin/sections' },
    ];

    const [perPage, setPerPage] = useState(sections.per_page || 10);
    const [confirmDialogOpen, setConfirmDialogOpen] = useState(false);
    const [pendingDeleteId, setPendingDeleteId] = useState<number | null>(null);
    const isFirstPerPageRender = useRef(true);

    useEffect(() => {
        if (isFirstPerPageRender.current) {
            isFirstPerPageRender.current = false;
            return;
        }

        router.get(
            '/admin/sections',
            {
                academic_year_id: selectedYearId,
                grade_id: selectedGradeId,
                per_page: perPage,
            },
            { preserveState: true, replace: true },
        );
    }, [perPage]);

    const handleFilterChange = (key: string, value: string) => {
        const params: Record<string, any> = {
            academic_year_id: selectedYearId,
            per_page: perPage,
        };
        if (selectedGradeId) params.grade_id = selectedGradeId;

        params[key] = value === 'all' ? null : value;

        router.get('/admin/sections', params, { preserveState: true });
    };

    const handleDelete = (id: number) => {
        setPendingDeleteId(id);
        setConfirmDialogOpen(true);
    };

    const confirmDelete = () => {
        if (!pendingDeleteId) return;
        router.delete(`/admin/sections/${pendingDeleteId}`);
        setConfirmDialogOpen(false);
        setPendingDeleteId(null);
    };

    const pagination: PaginationInfo | undefined =
        sections.last_page > 1
            ? {
                  links: sections.links,
                  total: sections.total,
                  current_page: sections.current_page,
                  last_page: sections.last_page,
              }
            : undefined;

    const tableColumns = (
        <>
            <DataTableHead>
                <DataTableTH className="w-16">#</DataTableTH>
                <DataTableTH>Sección</DataTableTH>
                <DataTableTH className="w-40">Grado</DataTableTH>
                <DataTableTH className="w-40">Año Escolar</DataTableTH>
                <DataTableTH className="w-32 text-right">Acciones</DataTableTH>
            </DataTableHead>
            <DataTableBody>
                {sections.data.map((section, index) => (
                    <DataTableTR key={section.id}>
                        <DataTableTD className="font-mono text-xs text-neutral-400">
                            {(sections.current_page - 1) * perPage + index + 1}
                        </DataTableTD>
                        <DataTableTD>
                            <div className="flex items-center gap-3">
                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/20">
                                    <Layers className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                </div>
                                <span className="font-semibold text-neutral-900 dark:text-neutral-100">
                                    {section.name}
                                </span>
                            </div>
                        </DataTableTD>
                        <DataTableTD>
                            <Badge variant="secondary" className="text-xs">
                                {section.grade?.name || 'N/A'}
                            </Badge>
                        </DataTableTD>
                        <DataTableTD className="text-neutral-500">
                            {academicYears.find(
                                (y) => y.id === section.academic_year_id,
                            )?.name || 'N/A'}
                        </DataTableTD>
                        <DataTableTD className="text-right">
                            <div className="flex items-center justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-8 w-8 text-neutral-500 hover:text-blue-600"
                                    asChild
                                    title="Ver detalles"
                                >
                                    <Link
                                        href={`/admin/sections/${section.id}`}
                                    >
                                        <Eye className="h-4 w-4" />
                                        <span className="sr-only">Ver</span>
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
                                        href={`/admin/sections/${section.id}/edit`}
                                    >
                                        <Edit className="h-4 w-4" />
                                        <span className="sr-only">Editar</span>
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-8 w-8 text-red-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/30"
                                    onClick={() => handleDelete(section.id)}
                                    title="Eliminar"
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
            <Head title="Secciones" />

            <SettingsLayout>
                <div className="flex flex-col gap-6">
                    {/* Encabezado */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                Secciones
                            </h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Administra la división de grupos para cada grado
                                y periodo.
                            </p>
                        </div>
                        <Button asChild>
                            <Link
                                href={`/admin/sections/create?academic_year_id=${selectedYearId}${selectedGradeId ? `&grade_id=${selectedGradeId}` : ''}`}
                            >
                                <Plus className="mr-2 h-4 w-4" />
                                Nueva Sección
                            </Link>
                        </Button>
                    </div>

                    {/* Filtros */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <div className="w-full sm:w-48">
                            <Select
                                value={selectedYearId.toString()}
                                onValueChange={(v) =>
                                    handleFilterChange('academic_year_id', v)
                                }
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
                        <div className="w-full sm:w-48">
                            <Select
                                value={selectedGradeId?.toString() || 'all'}
                                onValueChange={(v) =>
                                    handleFilterChange('grade_id', v)
                                }
                                data-test="grade-filter"
                            >
                                <SelectTrigger className="h-10" data-test="grade-filter-trigger">
                                    <SelectValue placeholder="Todos los grados" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        Todos los grados
                                    </SelectItem>
                                    {grades.map((grade) => (
                                        <SelectItem
                                            key={grade.id}
                                            value={grade.id.toString()}
                                        >
                                            {grade.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    {/* Tabla */}
                    <DataTable
                        data={sections.data}
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
                        emptyMessage="No se encontraron secciones para los filtros seleccionados."
                    />
                </div>
            </SettingsLayout>

            {/* Confirmation Dialog */}
            <AlertDialog
                open={confirmDialogOpen}
                onOpenChange={setConfirmDialogOpen}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            Confirmar Eliminación
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            ¿Estás seguro de que deseas eliminar esta sección?
                            Esta acción no se puede deshacer.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={confirmDelete}
                            data-test="confirm-delete-button"
                            className="bg-red-600 hover:bg-red-700"
                        >
                            Eliminar
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
