import { Head, router, useForm } from '@inertiajs/react';
import { Edit, MapPin, Plus, Save, Trash2, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

interface Location {
    id: number;
    name: string;
    description: string | null;
}

interface PaginatedLocations {
    data: Location[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
}

interface Props {
    locations: PaginatedLocations;
}

export default function LocationsIndex({ locations }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Ubicaciones', href: '#' },
    ];

    const [editingId, setEditingId] = useState<number | null>(null);
    const [isCreating, setIsCreating] = useState(false);
    const [perPage, setPerPage] = useState(locations.per_page || 10);
    const isFirstPerPageRender = useRef(true);
    const [confirmDialogOpen, setConfirmDialogOpen] = useState(false);
    const [pendingDeleteId, setPendingDeleteId] = useState<number | null>(null);

    useEffect(() => {
        if (isFirstPerPageRender.current) {
            isFirstPerPageRender.current = false;
            return;
        }

        router.get(
            '/admin/locations',
            { per_page: perPage },
            { preserveState: true, replace: true },
        );
    }, [perPage]);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        id: null as number | null,
        name: '',
        description: '',
    });

    const startEdit = (location: Location) => {
        setEditingId(location.id);
        setData({
            id: location.id,
            name: location.name,
            description: location.description ?? '',
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
            put(`/admin/locations/${data.id}`, {
                onSuccess: () => cancelEdit(),
            });
        } else {
            post('/admin/locations', {
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
        router.delete(`/admin/locations/${pendingDeleteId}`);
        setConfirmDialogOpen(false);
        setPendingDeleteId(null);
    };

    const pagination: PaginationInfo | undefined =
        locations.last_page > 1
            ? {
                  links: locations.links,
                  total: locations.total,
                  current_page: locations.current_page,
                  last_page: locations.last_page,
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
                {locations.data.map((location, index) => (
                    <DataTableTR key={location.id}>
                        <DataTableTD className="font-mono text-xs text-neutral-400">
                            {(locations.current_page - 1) * perPage + index + 1}
                        </DataTableTD>
                        <DataTableTD>
                            <span className="font-semibold text-neutral-900 dark:text-neutral-100">
                                {location.name}
                            </span>
                        </DataTableTD>
                        <DataTableTD className="text-neutral-600 dark:text-neutral-400">
                            {location.description || (
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
                                    onClick={() => startEdit(location)}
                                >
                                    <Edit className="h-4 w-4" />
                                    <span className="sr-only">Editar</span>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-8 w-8 text-red-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/30"
                                    onClick={() => handleDelete(location.id)}
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
            <Head title="Ubicaciones" />

            <SettingsLayout>
                <div className="flex flex-col gap-6">
                    {/* Header */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                Ubicaciones
                            </h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Listado de localizaciones frecuentes donde se
                                realizan las jornadas de campo.
                            </p>
                        </div>
                        <Button onClick={startCreate} disabled={isCreating}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nueva Ubicación
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
                                            placeholder="Ej: Huerto escolar, Cancha..."
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
                                            placeholder="Descripción breve de la ubicación"
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
                        data={locations.data}
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
                        emptyMessage="No hay ubicaciones configuradas."
                    />
                </div>
            </SettingsLayout>

            <AlertDialog open={confirmDialogOpen} onOpenChange={setConfirmDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>¿Eliminar ubicación?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Esta acción no se puede deshacer. La ubicación será eliminada permanentemente.
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
