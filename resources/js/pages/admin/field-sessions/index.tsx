import { Head, Link, router } from '@inertiajs/react';
import { Eye, Plus, Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
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
    DataTable,
    DataTableHead,
    DataTableTH,
    DataTableBody,
    DataTableTR,
    DataTableTD,
    type PaginationInfo,
} from '@/components/ui/data-table';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Status {
    id: number;
    name: string;
    description: string | null;
}

interface FieldSession {
    id: number;
    name: string;
    start_datetime: string;
    end_datetime: string;
    base_hours: string;
    activity_name: string | null;
    location_name: string | null;
    academic_year: { name: string };
    school_term: { name: string } | null;
    teacher: { name: string };
    status: { name: string; description: string | null };
}

interface PaginatedSessions {
    data: FieldSession[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
}

interface Props {
    fieldSessions: PaginatedSessions;
    statuses: Status[];
    selectedStatusId: number | null;
}

const statusColors: Record<string, string> = {
    planned: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    realized:
        'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
};

const statusLabels: Record<string, string> = {
    planned: 'Planificada',
    realized: 'Realizada',
    cancelled: 'Cancelada',
};

export default function FieldSessionsIndex({
    fieldSessions,
    statuses,
    selectedStatusId,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Jornadas de Campo', href: '#' },
    ];

    const [perPage, setPerPage] = useState(fieldSessions.per_page || 10);
    const isFirstPerPageRender = useRef(true);

    useEffect(() => {
        if (isFirstPerPageRender.current) {
            isFirstPerPageRender.current = false;
            return;
        }

        router.get(
            '/admin/field-sessions',
            {
                status_id: selectedStatusId ?? undefined,
                per_page: perPage,
            },
            { preserveState: true, replace: true },
        );
    }, [perPage, selectedStatusId]);

    const handleFilterChange = (statusId: string | null) => {
        router.get(
            '/admin/field-sessions',
            {
                status_id: statusId ?? undefined,
                per_page: perPage,
            },
            { preserveState: true },
        );
    };

    const handleDelete = (id: number) => {
        if (
            confirm(
                '¿Estás seguro de que deseas eliminar esta jornada de campo?',
            )
        ) {
            router.delete(`/admin/field-sessions/${id}`);
        }
    };

    const pagination: PaginationInfo | undefined =
        fieldSessions.last_page > 1
            ? {
                  links: fieldSessions.links,
                  total: fieldSessions.total,
                  current_page: fieldSessions.current_page,
                  last_page: fieldSessions.last_page,
              }
            : undefined;

    const tableColumns = (
        <>
            <DataTableHead>
                <DataTableTH className="w-16">#</DataTableTH>
                <DataTableTH>Jornada</DataTableTH>
                <DataTableTH>Fecha</DataTableTH>
                <DataTableTH className="w-20">Horas</DataTableTH>
                <DataTableTH>Profesor</DataTableTH>
                <DataTableTH className="w-28">Estado</DataTableTH>
                <DataTableTH className="w-32 text-right">Acciones</DataTableTH>
            </DataTableHead>
            <DataTableBody>
                {fieldSessions.data.map((session, index) => (
                    <DataTableTR key={session.id}>
                        <DataTableTD className="font-mono text-xs text-neutral-400">
                            {(fieldSessions.current_page - 1) * perPage +
                                index +
                                1}
                        </DataTableTD>
                        <DataTableTD>
                            <div className="flex flex-col">
                                <span className="font-semibold text-neutral-900 dark:text-neutral-100">
                                    {session.name}
                                </span>
                                {session.activity_name && (
                                    <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                        {session.activity_name}
                                    </span>
                                )}
                            </div>
                        </DataTableTD>
                        <DataTableTD className="text-sm text-neutral-600 dark:text-neutral-400">
                            {new Date(
                                session.start_datetime,
                            ).toLocaleDateString('es-ES', {
                                day: '2-digit',
                                month: 'short',
                                year: 'numeric',
                            })}
                        </DataTableTD>
                        <DataTableTD className="text-center font-mono text-sm">
                            {session.base_hours}h
                        </DataTableTD>
                        <DataTableTD className="text-sm text-neutral-600 dark:text-neutral-400">
                            {session.teacher.name}
                        </DataTableTD>
                        <DataTableTD>
                            <Badge
                                variant="outline"
                                className={
                                    statusColors[session.status.name] || ''
                                }
                            >
                                {statusLabels[session.status.name] ||
                                    session.status.name}
                            </Badge>
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
                                        href={`/admin/field-sessions/${session.id}`}
                                    >
                                        <Eye className="h-4 w-4" />
                                        <span className="sr-only">Ver</span>
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-8 w-8 text-red-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/30"
                                    onClick={() => handleDelete(session.id)}
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
            <Head title="Jornadas de Campo" />

            <div className="flex flex-col gap-6 p-4 lg:p-8">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                            Jornadas de Campo
                        </h1>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                            Registro y gestión de actividades de campo
                            realizadas por los estudiantes.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/admin/field-sessions/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Nueva Jornada
                        </Link>
                    </Button>
                </div>

                {/* Filters */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div className="w-full sm:w-48">
                        <Select
                            value={selectedStatusId?.toString() ?? ''}
                            onValueChange={(val) =>
                                handleFilterChange(val || null)
                            }
                        >
                            <SelectTrigger className="h-10">
                                <SelectValue placeholder="Estado" />
                            </SelectTrigger>
                            <SelectContent>
                                {statuses.map((status) => (
                                    <SelectItem
                                        key={status.id}
                                        value={status.id.toString()}
                                    >
                                        {statusLabels[status.name] ||
                                            status.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {/* Table */}
                <DataTable
                    data={fieldSessions.data}
                    columns={tableColumns}
                    pagination={pagination}
                    onPageChange={(_, url) => {
                        router.get(
                            url,
                            {
                                status_id: selectedStatusId ?? undefined,
                                per_page: perPage,
                            },
                            { preserveState: true, replace: true },
                        );
                    }}
                    perPage={perPage}
                    onPerPageChange={setPerPage}
                    perPageOptions={[10, 15, 25, 50]}
                    emptyMessage="No hay jornadas de campo registradas."
                />
            </div>
        </AppLayout>
    );
}
