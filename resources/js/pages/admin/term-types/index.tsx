import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, CalendarDays, Edit, Plus, Save, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
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
import { index as schoolTermsIndex } from '@/routes/admin/school-terms';

interface TermType {
    id: number;
    name: string;
    order: number;
    is_active: boolean;
}

interface Props {
    termTypes: TermType[];
}

export default function TermTypesIndex({ termTypes }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Lapsos Académicos', href: schoolTermsIndex().url },
        { title: 'Tipos de Lapsos', href: '#' },
    ];

    const [editingId, setEditingId] = useState<number | null>(null);
    const [isCreating, setIsCreating] = useState(false);
    const [confirmDialogOpen, setConfirmDialogOpen] = useState(false);
    const [pendingDeleteId, setPendingDeleteId] = useState<number | null>(null);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        id: null as number | null,
        name: '',
        order: 1,
    });

    const startEdit = (type: TermType) => {
        setEditingId(type.id);
        setData({ id: type.id, name: type.name, order: type.order });
    };

    const cancelEdit = () => {
        setEditingId(null);
        setIsCreating(false);
        reset();
    };

    const startCreate = () => {
        setIsCreating(true);
        setData({
            id: null,
            name: '',
            order: Math.max(...termTypes.map((t) => t.order), 0) + 1,
        });
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (data.id) {
            put(`/admin/term-types/${data.id}`, {
                onSuccess: () => {
                    cancelEdit();
                },
            });
        } else {
            post('/admin/term-types', {
                onSuccess: () => {
                    cancelEdit();
                },
            });
        }
    };

    const handleDelete = (id: number) => {
        setPendingDeleteId(id);
        setConfirmDialogOpen(true);
    };

    const confirmDelete = () => {
        if (!pendingDeleteId) return;
        router.delete(`/admin/term-types/${pendingDeleteId}`);
        setConfirmDialogOpen(false);
        setPendingDeleteId(null);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tipos de Lapsos" />

            <SettingsLayout>
                <div className="flex flex-col gap-6">
                    {/* Header */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                Tipos de Lapsos
                            </h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Administra las plantillas de nombres para los
                                lapsos académicos.
                            </p>
                        </div>
                        <div className="flex items-center gap-3">
                            <Button variant="outline" size="sm" onClick={() => window.history.back()}>
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver
                            </Button>
                            <Button onClick={startCreate} disabled={isCreating}>
                                <Plus className="mr-2 h-4 w-4" />
                                Nuevo
                            </Button>
                        </div>
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
                                            placeholder="Ej: Lapso 1, Periodo Extracurricular"
                                            required
                                        />
                                        <InputError message={errors.name} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="order">Orden</Label>
                                        <Input
                                            id="order"
                                            type="number"
                                            min="1"
                                            value={data.order}
                                            onChange={(e) =>
                                                setData(
                                                    'order',
                                                    parseInt(e.target.value),
                                                )
                                            }
                                            required
                                        />
                                        <InputError message={errors.order} />
                                    </div>
                                </div>
                                <div className="flex items-center justify-end gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={cancelEdit}
                                    >
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

                    {/* List */}
                    <div className="space-y-2">
                        {/* Header labels */}
                        <div className="flex px-1">
                            <span className="text-sm font-semibold text-neutral-500 dark:text-neutral-400">
                                Nombre
                            </span>
                        </div>
                        {termTypes.length > 0 ? (
                            termTypes.map((type) => (
                                <div
                                    key={type.id}
                                    className="flex items-center justify-between gap-3 rounded-lg border border-neutral-200 bg-neutral-50/50 px-3 py-2 dark:border-neutral-700/50 dark:bg-neutral-800/30"
                                >
                                    <div className="flex items-center gap-3">
                                        <CalendarDays className="h-4 w-4 text-neutral-400" />
                                        <span className="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                            {type.name}
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="h-8 w-8 text-neutral-500"
                                            onClick={() => startEdit(type)}
                                        >
                                            <Edit className="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="h-8 w-8 text-red-500 hover:bg-red-50 hover:text-red-600"
                                            onClick={() =>
                                                handleDelete(type.id)
                                            }
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            ))
                        ) : (
                            <div className="rounded-lg border border-dashed p-8 text-center text-neutral-500">
                                No hay tipos de lapsos configurados.
                            </div>
                        )}
                    </div>
                </div>
            </SettingsLayout>

            <AlertDialog open={confirmDialogOpen} onOpenChange={setConfirmDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>¿Eliminar tipo de lapso?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Esta acción no se puede deshacer. El tipo de lapso será eliminado permanentemente.
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
