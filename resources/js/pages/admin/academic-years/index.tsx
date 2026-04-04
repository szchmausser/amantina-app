import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Calendar,
    CheckCircle2,
    Edit,
    Plus,
    Settings,
    Trash2,
} from 'lucide-react';
import { useState, useEffect, useRef } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem, SharedData } from '@/types';
import {
    index as academicYearsIndex,
    create as academicYearsCreate,
    edit as academicYearsEdit,
    show as academicYearsShow,
    destroy as academicYearsDestroy,
} from '@/routes/admin/academic-years';
import {
    DataTable,
    DataTableHead,
    DataTableTH,
    DataTableBody,
    DataTableTR,
    DataTableTD,
    type PaginationInfo,
} from '@/components/ui/data-table';

interface AcademicYear {
    id: number;
    name: string;
    is_active: boolean;
    required_hours: number;
}

interface PaginatedYears {
    data: AcademicYear[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
}

interface Props {
    academicYears: PaginatedYears;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Años Escolares', href: academicYearsIndex().url },
];

export default function AcademicYearIndex({ academicYears }: Props) {
    const { auth } = usePage<SharedData>().props;
    const hasPermission = (p: string) => auth.permissions?.includes(p);

    const [perPage, setPerPage] = useState(academicYears.per_page || 10);
    const isFirstPerPageRender = useRef(true);

    useEffect(() => {
        if (isFirstPerPageRender.current) {
            isFirstPerPageRender.current = false;
            return;
        }

        router.get(
            academicYearsIndex().url,
            { per_page: perPage },
            { preserveState: true, replace: true },
        );
    }, [perPage]);

    const handleDelete = (id: number) => {
        if (
            confirm(
                '¿Estás seguro de que deseas eliminar este año académico? Se eliminarán también sus lapsos, grados y secciones.',
            )
        ) {
            router.delete(academicYearsDestroy(id).url);
        }
    };

    const pagination: PaginationInfo | undefined =
        academicYears.last_page > 1
            ? {
                  links: academicYears.links,
                  total: academicYears.total,
                  current_page: academicYears.current_page,
                  last_page: academicYears.last_page,
              }
            : undefined;

    const tableColumns = (
        <>
            <DataTableHead>
                <DataTableTH className="w-16">#</DataTableTH>
                <DataTableTH>Año Escolar</DataTableTH>
                <DataTableTH className="w-32 whitespace-nowrap">
                    Cupo de Horas
                </DataTableTH>
                <DataTableTH className="w-28 whitespace-nowrap">
                    Estado
                </DataTableTH>
                <DataTableTH className="w-56 text-right">Acciones</DataTableTH>
            </DataTableHead>
            <DataTableBody>
                {academicYears.data.map((year, index) => (
                    <DataTableTR key={year.id}>
                        <DataTableTD className="font-mono text-xs text-neutral-400">
                            {(academicYears.current_page - 1) * perPage +
                                index +
                                1}
                        </DataTableTD>
                        <DataTableTD>
                            <div className="flex items-center gap-3">
                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/20">
                                    <Calendar className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                </div>
                                <span className="font-semibold text-neutral-900 dark:text-neutral-100">
                                    {year.name}
                                </span>
                            </div>
                        </DataTableTD>
                        <DataTableTD className="font-mono text-neutral-600 dark:text-neutral-400">
                            {year.required_hours} horas
                        </DataTableTD>
                        <DataTableTD>
                            {year.is_active ? (
                                <Badge className="bg-green-100 text-green-700 hover:bg-green-100 dark:bg-green-900/30 dark:text-green-400">
                                    <CheckCircle2 className="mr-1 h-3 w-3" />
                                    Activo
                                </Badge>
                            ) : (
                                <span className="text-xs text-neutral-400">
                                    Inactivo
                                </span>
                            )}
                        </DataTableTD>
                        <DataTableTD className="text-right">
                            <div className="flex items-center justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-8 w-8 text-neutral-500 hover:text-blue-600"
                                    asChild
                                    title="Configurar estructura"
                                >
                                    <Link href={academicYearsShow(year.id).url}>
                                        <Settings className="h-4 w-4" />
                                        <span className="sr-only">
                                            Configurar
                                        </span>
                                    </Link>
                                </Button>
                                {hasPermission('academic_years.edit') && (
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="h-8 w-8"
                                        asChild
                                        title="Editar"
                                    >
                                        <Link
                                            href={
                                                academicYearsEdit(year.id).url
                                            }
                                        >
                                            <Edit className="h-4 w-4" />
                                            <span className="sr-only">
                                                Editar
                                            </span>
                                        </Link>
                                    </Button>
                                )}
                                {hasPermission('academic_years.delete') && (
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="h-8 w-8 text-red-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/30"
                                        onClick={() => handleDelete(year.id)}
                                        title="Eliminar"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                        <span className="sr-only">
                                            Eliminar
                                        </span>
                                    </Button>
                                )}
                            </div>
                        </DataTableTD>
                    </DataTableTR>
                ))}
            </DataTableBody>
        </>
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Años Escolares" />

            <SettingsLayout>
                <div className="flex flex-col gap-6">
                    {/* Encabezado */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                Años Escolares
                            </h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Gestión de años escolares, lapsos, grados y
                                secciones.
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

                    {/* Tabla */}
                    <DataTable
                        data={academicYears.data}
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
                        emptyMessage="No se encontraron años escolares registrados."
                    />
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
