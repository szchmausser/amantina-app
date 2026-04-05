import { Head, router, useForm } from '@inertiajs/react';
import { Edit, Plus, Save, Tag, Trash2, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem } from '@/types';

interface ActivityCategory {
    id: number;
    name: string;
    description: string | null;
}

interface PaginatedCategories {
    data: ActivityCategory[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
}

interface Props {
    activityCategories: PaginatedCategories;
}

export default function ActivityCategoriesIndex({ activityCategories }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Categorías de Actividades', href: '#' },
    ];

    const [editingId, setEditingId] = useState<number | null>(null);
    const [isCreating, setIsCreating] = useState(false);
    const [perPage, setPerPage] = useState(activityCategories.per_page || 10);
    const isFirstPerPageRender = useRef(true);

    useEffect(() => {
        if (isFirstPerPageRender.current) {
            isFirstPerPageRender.current = false;
            return;
        }

        router.get(
            '/admin/activity-categories',
            { per_page: perPage },
            { preserveState: true, replace: true },
        );
    }, [perPage]);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        id: null as number | null,
        name: '',
        description: '',
    });

    const startEdit = (category: ActivityCategory) => {
        setEditingId(category.id);
        setData({
            id: category.id,
            name: category.name,
            description: category.description ?? '',
        });
    };

    const cancelEdit = () => {
        setEditingId(null);
        setIsCreating(false);
        reset();
    };

    const startCreate = () => {
        setIsCreating(true);
        setData({ id: null, name: '', description: '' });
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (data.id) {
            put(`/admin/activity-categories/${data.id}`, {
                onSuccess: () => cancelEdit(),
            });
        } else {
            post('/admin/activity-categories', {
                onSuccess: () => cancelEdit(),
            });
        }
    };

    const handleDelete = (id: number) => {
        if (
            confirm(
                '¿Estás seguro de que deseas eliminar esta categoría de actividad?',
            )
        ) {
            router.delete(`/admin/activity-categories/${id}`);
        }
    };

    const pagination: PaginationInfo | undefined =
        activityCategories.last_page > 1
            ? {
                  links: activityCategories.links,
                  total: activityCategories.total,
                  current_page: activityCategories.current_page,
                  last_page: activityCategories.last_page,
              }
            : undefined;

    const tableColumns = (
        <>
            <DataTableHead>
                <DataTableTH className="w-16">#</DataTableTH>
                <DataTableTH>Nombre</DataTableTH>
                <DataTableTH>Descripción</DataTableTH>
                <DataTableTH className="w-32 text-right">Acciones</DataTableTH>
            </DataTableHead>
            <DataTableBody>
                {activityCategories.data.map((category, index) => (
                    <DataTableTR key={category.id}>
                        <DataTableTD className="font-mono text-xs text-neutral-400">
                            {(activityCategories.current_page - 1) * perPage +
                                index +
                                1}
                        </DataTableTD>
                        <DataTableTD>
                            <span className="font-semibold text-neutral-900 dark:text-neutral-100">
                                {category.name}
                            </span>
                        </DataTableTD>
                        <DataTableTD className="text-neutral-600 dark:text-neutral-400">
                            {category.description || (
                                <span className="text-xs text-neutral-400 italic">
                                    Sin descripción
                                </span>
                            )}
                        </DataTableTD>
                        <DataTableTD className="text-right">
                            <div className="flex items-center justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-8 w-8 text-neutral-500 hover:text-blue-600"
                                    onClick={() => startEdit(category)}
                                >
                                    <Edit className="h-4 w-4" />
                                    <span className="sr-only">Editar</span>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-8 w-8 text-red-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/30"
                                    onClick={() => handleDelete(category.id)}
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
            <Head title="Categorías de Actividades" />

            <SettingsLayout>
                <div className="flex flex-col gap-6">
                    {/* Header */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                Categorías de Actividades
                            </h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Listado de categorías de actividades frecuentes
                                para las jornadas de campo.
                            </p>
                        </div>
                        <Button onClick={startCreate} disabled={isCreating}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nueva Categoría
                        </Button>
                    </div>

                    {/* Create/Edit Form */}
                    {(isCreating || editingId) && (
                        <div className="rounded-xl border p-6">
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="name">Nombre</Label>
                                        <Input
                                            id="name"
                                            value={data.name}
                                            onChange={(e) =>
                                                setData('name', e.target.value)
                                            }
                                            placeholder="Ej: Siembra, Riego..."
                                            required
                                        />
                                        <InputError message={errors.name} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="description">
                                            Descripción (opcional)
                                        </Label>
                                        <Input
                                            id="description"
                                            value={data.description}
                                            onChange={(e) =>
                                                setData(
                                                    'description',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Descripción breve de la actividad"
                                        />
                                        <InputError
                                            message={errors.description}
                                        />
                                    </div>
                                </div>
                                <div className="flex items-center justify-end gap-2">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        onClick={cancelEdit}
                                    >
                                        <X className="mr-1 h-4 w-4" />
                                        Cancelar
                                    </Button>
                                    <Button type="submit" disabled={processing}>
                                        <Save className="mr-2 h-4 w-4" />
                                        {data.id ? 'Actualizar' : 'Crear'}
                                    </Button>
                                </div>
                            </form>
                        </div>
                    )}

                    {/* Table */}
                    <DataTable
                        data={activityCategories.data}
                        columns={tableColumns}
                        pagination={pagination}
                        onPageChange={(_, url) => {
                            router.get(
                                url,
                                {},
                                { preserveState: true, replace: true },
                            );
                        }}
                        perPage={perPage}
                        onPerPageChange={setPerPage}
                        perPageOptions={[10, 15, 25, 50]}
                        emptyMessage="No hay categorías de actividad configuradas."
                    />
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
