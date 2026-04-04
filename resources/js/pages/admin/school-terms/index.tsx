import { Head, Link, router } from '@inertiajs/react';
import { formatDate } from '@/lib/utils';
import { Clock, Edit, Plus, Trash2 } from 'lucide-react';
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

interface SchoolTerm {
    id: number;
    term_number: number;
    start_date: string;
    end_date: string;
    academic_year_id: number;
}

interface AcademicYear {
    id: number;
    name: string;
}

interface PaginatedTerms {
    data: SchoolTerm[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
}

interface Props {
    schoolTerms: PaginatedTerms;
    academicYears: AcademicYear[];
    selectedYearId: number;
}

export default function SchoolTermsIndex({
    schoolTerms,
    academicYears,
    selectedYearId,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Lapsos Académicos', href: '/admin/school-terms' },
    ];

    const [perPage, setPerPage] = useState(schoolTerms.per_page || 10);
    const isFirstPerPageRender = useRef(true);

    useEffect(() => {
        if (isFirstPerPageRender.current) {
            isFirstPerPageRender.current = false;
            return;
        }

        router.get(
            '/admin/school-terms',
            { academic_year_id: selectedYearId, per_page: perPage },
            { preserveState: true, replace: true },
        );
    }, [perPage]);

    const handleYearChange = (yearId: string) => {
        router.get(
            '/admin/school-terms',
            { academic_year_id: yearId, per_page: perPage },
            { preserveState: true },
        );
    };

    const handleDelete = (termId: number) => {
        if (
            confirm(
                '¿Estás seguro de que deseas eliminar este lapso académico?',
            )
        ) {
            router.delete(`/admin/school-terms/${termId}`, {
                preserveState: true,
            });
        }
    };

    const pagination: PaginationInfo | undefined =
        schoolTerms.last_page > 1
            ? {
                  links: schoolTerms.links,
                  total: schoolTerms.total,
                  current_page: schoolTerms.current_page,
                  last_page: schoolTerms.last_page,
              }
            : undefined;

    const tableColumns = (
        <>
            <DataTableHead>
                <DataTableTH className="w-16">#</DataTableTH>
                <DataTableTH>Lapso</DataTableTH>
                <DataTableTH>Fecha de Inicio</DataTableTH>
                <DataTableTH>Fecha de Cierre</DataTableTH>
                <DataTableTH className="w-40">Año Escolar</DataTableTH>
                <DataTableTH className="w-24 text-right">Acciones</DataTableTH>
            </DataTableHead>
            <DataTableBody>
                {schoolTerms.data.map((term, index) => (
                    <DataTableTR key={term.id}>
                        <DataTableTD className="font-mono text-xs text-neutral-400">
                            {(schoolTerms.current_page - 1) * perPage +
                                index +
                                1}
                        </DataTableTD>
                        <DataTableTD>
                            <div className="flex items-center gap-3">
                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/20">
                                    <Clock className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                </div>
                                <span className="font-semibold text-neutral-900 dark:text-neutral-100">
                                    Lapso {term.term_number}
                                </span>
                            </div>
                        </DataTableTD>
                        <DataTableTD className="font-mono text-neutral-600 dark:text-neutral-400">
                            {formatDate(term.start_date)}
                        </DataTableTD>
                        <DataTableTD className="font-mono text-neutral-600 dark:text-neutral-400">
                            {formatDate(term.end_date)}
                        </DataTableTD>
                        <DataTableTD className="text-neutral-500">
                            {academicYears.find(
                                (y) => y.id === term.academic_year_id,
                            )?.name || 'N/A'}
                        </DataTableTD>
                        <DataTableTD className="text-right">
                            <div className="flex items-center justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-8 w-8 text-neutral-500 hover:text-blue-600"
                                    asChild
                                >
                                    <Link
                                        href={`/admin/school-terms/${term.id}/edit`}
                                    >
                                        <Edit className="h-4 w-4" />
                                        <span className="sr-only">Editar</span>
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-8 w-8 text-red-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/30"
                                    onClick={() => handleDelete(term.id)}
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
            <Head title="Lapsos Académicos" />

            <SettingsLayout>
                <div className="flex flex-col gap-6">
                    {/* Encabezado */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                Lapsos Académicos
                            </h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Administra los periodos de evaluación para cada
                                ciclo escolar.
                            </p>
                        </div>
                        <Button asChild>
                            <Link
                                href={`/admin/school-terms/create?academic_year_id=${selectedYearId}`}
                            >
                                <Plus className="mr-2 h-4 w-4" />
                                Nuevo Lapso
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
                        data={schoolTerms.data}
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
                        emptyMessage="No se encontraron lapsos configurados para este año escolar."
                    />
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
