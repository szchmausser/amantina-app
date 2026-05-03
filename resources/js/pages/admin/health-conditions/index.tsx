import { Head, router, useForm } from '@inertiajs/react';
import { Edit, Heart, Plus, Save, Trash2, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
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

interface HealthCondition {
    id: number;
    name: string;
    is_active: boolean;
}

interface PaginatedConditions {
    data: HealthCondition[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
}

interface Props {
    healthConditions: PaginatedConditions;
}

export default function HealthConditionsIndex({ healthConditions }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Condiciones de Salud', href: '#' },
    ];

    const [editingId, setEditingId] = useState<number | null>(null);
    const [isCreating, setIsCreating] = useState(false);
    const [perPage, setPerPage] = useState(healthConditions.per_page || 10);
    const isFirstPerPageRender = useRef(true);
    const [confirmDialogOpen, setConfirmDialogOpen] = useState(false);
    const [pendingDeleteId, setPendingDeleteId] = useState<number | null>(null);

    useEffect(() => {
        if (isFirstPerPageRender.current) {
            isFirstPerPageRender.current = false;
            return;
        }

        router.get(
            '/admin/health-conditions',
            { per_page: perPage },
            { preserveState: true, replace: true },
        );
    }, [perPage]);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        id: null as number | null,
        name: '',
        is_active: true,
    });

    const startEdit = (condition: HealthCondition) => {
        setEditingId(condition.id);
        setData({
            id: condition.id,
            name: condition.name,
            is_active: condition.is_active,
        });
    };

    const cancelEdit = () => {
        setEditingId(null);
        setIsCreating(false);
        reset();
    };

    const startCreate = () => {
        setIsCreating(true);
        setData({ id: null, name: '', is_active: true });
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (data.id) {
            put(`/admin/health-conditions/${data.id}`, {
                onSuccess: () => cancelEdit(),
            });
        } else {
            post('/admin/health-conditions', {
                onSuccess: () => cancelEdit(),
            });
        }
    };

    const handleDelete = (id: number) => {
        setPendingDeleteId(id);
        setConfirmDialogOpen(true);
    };

    const confirmDelete = () => {
        if (!pendingDeleteId) return;
        router.delete(`/admin/health-conditions/${pendingDeleteId}`);
        setConfirmDialogOpen(false);
        setPendingDeleteId(null);
    };

    const toggleActive = (condition: HealthCondition) => {
        router.put(`/admin/health-conditions/${condition.id}`, {
            name: condition.name,
            is_active: !condition.is_active,
        });
    };

    const pagination: PaginationInfo | undefined =
        healthConditions.last_page > 1
            ? {
                  links: healthConditions.links,
                  total: healthConditions.total,
                  current_page: healthConditions.current_page,
                  last_page: healthConditions.last_page,
              }
            : undefined;

    const tableColumns = (
        <>
            <DataTableHead>
                <DataTableTH className="w-16">#</DataTableTH>
                <DataTableTH>Nombre</DataTableTH>
                <DataTableTH className="w-28">Estado</DataTableTH>
                <DataTableTH className="w-48 text-right">Acciones</DataTableTH>
            </DataTableHead>
            <DataTableBody>
                {healthConditions.data.map((condition, index) => (
                    <DataTableTR key={condition.id}>
                        <DataTableTD className="font-mono text-xs text-neutral-400">
                            {(healthConditions.current_page - 1) * perPage +
                                index +
                                1}
                        </DataTableTD>
                        <DataTableTD>
                            <span className="font-semibold text-neutral-900 dark:text-neutral-100">
                                {condition.name}
                            </span>
                        </DataTableTD>
                        <DataTableTD>
                            <Badge
                                variant={
                                    condition.is_active
                                        ? 'default'
                                        : 'secondary'
                                }
                                className="text-xs"
                            >
                                {condition.is_active ? 'Activa' : 'Inactiva'}
                            </Badge>
                        </DataTableTD>
                        <DataTableTD className="text-right">
                            <div className="flex items-center justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="h-7 text-xs"
                                    onClick={() => toggleActive(condition)}
                                >
                                    {condition.is_active
                                        ? 'Desactivar'
                                        : 'Activar'}
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-8 w-8 text-neutral-500 hover:text-blue-600"
                                    onClick={() => startEdit(condition)}
                                >
                                    <Edit className="h-4 w-4" />
                                    <span className="sr-only">Editar</span>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-8 w-8 text-red-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/30"
                                    onClick={() => handleDelete(condition.id)}
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
            <Head title="Condiciones de Salud" />

            <SettingsLayout>
                <div className="flex flex-col gap-6">
                    {/* Header */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                Condiciones de Salud
                            </h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Administra el catálogo de condiciones médicas de
                                los estudiantes.
                            </p>
                        </div>
                        <Button onClick={startCreate} disabled={isCreating}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nueva Condición
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
                                            placeholder="Ej: Asma, Diabetes..."
                                            required
                                        />
                                        <InputError message={errors.name} />
                                    </div>
                                    <div className="flex items-end">
                                        <div className="flex items-center space-x-2">
                                            <Checkbox
                                                id="is_active"
                                                checked={data.is_active}
                                                onCheckedChange={(checked) =>
                                                    setData(
                                                        'is_active',
                                                        checked as boolean,
                                                    )
                                                }
                                            />
                                            <Label
                                                htmlFor="is_active"
                                                className="cursor-pointer"
                                            >
                                                Condición activa
                                            </Label>
                                        </div>
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
                        data={healthConditions.data}
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
                        emptyMessage="No hay condiciones de salud configuradas."
                    />
                </div>
            </SettingsLayout>

            <AlertDialog open={confirmDialogOpen} onOpenChange={setConfirmDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>¿Eliminar condición de salud?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Esta acción no se puede deshacer. La condición de salud será eliminada permanentemente.
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
