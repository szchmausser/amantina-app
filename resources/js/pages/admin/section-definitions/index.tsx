import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Edit, Layout, Plus, Save, Trash2 } from 'lucide-react';
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
import type { BreadcrumbItem, SharedData } from '@/types';
import { index as sectionsIndex } from '@/routes/admin/sections';

interface SectionDefinition {
    id: number;
    name: string;
    is_active: boolean;
}

interface Props {
    sectionDefinitions: SectionDefinition[];
}

export default function SectionDefinitionsIndex({ sectionDefinitions }: Props) {
    const { auth } = usePage<SharedData>().props;
    const permissions = auth.permissions ?? [];

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Definiciones de Secciones', href: '#' },
    ];

    const [editingId, setEditingId] = useState<number | null>(null);
    const [isCreating, setIsCreating] = useState(false);
    const [confirmDialogOpen, setConfirmDialogOpen] = useState(false);
    const [pendingDeleteId, setPendingDeleteId] = useState<number | null>(null);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        id: null as number | null,
        name: '',
    });

    const startEdit = (definition: SectionDefinition) => {
        setEditingId(definition.id);
        setData({ id: definition.id, name: definition.name });
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
        });
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (data.id) {
            put(`/admin/section-definitions/${data.id}`, {
                onSuccess: () => {
                    cancelEdit();
                },
            });
        } else {
            post('/admin/section-definitions', {
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
        router.delete(`/admin/section-definitions/${pendingDeleteId}`);
        setConfirmDialogOpen(false);
        setPendingDeleteId(null);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Definiciones de Secciones" />

            <SettingsLayout>
                <div className="flex flex-col gap-6">
                    {/* Header */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                Definiciones de Secciones
                            </h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Administra las plantillas de letras para las
                                secciones.
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            <Button variant="outline" size="sm" onClick={() => window.history.back()}>
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver
                            </Button>
                            {permissions.includes('section_definitions.create') && (
                                <Button onClick={startCreate} disabled={isCreating} data-test="create-button">
                                    <Plus className="mr-2 h-4 w-4" />
                                    Nuevo
                                </Button>
                            )}
                        </div>
                    </div>

                    {/* Create/Edit Form */}
                    {(isCreating || editingId) && (
                        <div className="rounded-xl border p-6">
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="name">Nombre</Label>
                                    <Input
                                        id="name"
                                        data-test={editingId ? `edit-name-input-${editingId}` : "section-definition-name-input"}
                                        value={data.name}
                                        onChange={(e) =>
                                            setData('name', e.target.value.toUpperCase())
                                        }
                                        placeholder="Ej: A, B, C"
                                        maxLength={1}
                                        required
                                    />
                                    <InputError message={errors.name} />
                                </div>
                                <div className="flex items-center justify-end gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={cancelEdit}
                                    >
                                        Cancelar
                                    </Button>
                                    <Button 
                                        type="submit" 
                                        disabled={processing}
                                        data-test={editingId ? `save-section-definition-${editingId}` : "create-section-definition-button"}
                                    >
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
                        {sectionDefinitions.length > 0 ? (
                            sectionDefinitions.map((definition) => (
                                <div
                                    key={definition.id}
                                    className="flex items-center justify-between gap-3 rounded-lg border border-neutral-200 bg-neutral-50/50 px-3 py-2 dark:border-neutral-700/50 dark:bg-neutral-800/30"
                                >
                                    <div className="flex items-center gap-3">
                                        <Layout className="h-4 w-4 text-neutral-400" />
                                        <span className="text-sm font-medium text-neutral-900 dark:text-neutral-100 uppercase">
                                            {definition.name}
                                        </span>
                                    </div>
                                        {(permissions.includes('section_definitions.edit') ||
                                        permissions.includes('section_definitions.delete')) && (
                                        <div className="flex items-center gap-1">
                                            {permissions.includes('section_definitions.edit') && (
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-neutral-500"
                                                    onClick={() => startEdit(definition)}
                                                    data-test={`edit-section-definition-${definition.id}`}
                                                >
                                                    <Edit className="h-4 w-4" />
                                                </Button>
                                            )}
                                            {permissions.includes('section_definitions.delete') && (
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-red-500 hover:bg-red-50 hover:text-red-600"
                                                    onClick={() =>
                                                        handleDelete(definition.id)
                                                    }
                                                    data-test={`delete-section-definition-${definition.id}`}
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            )}
                                        </div>
                                    )}
                                </div>
                            ))
                        ) : (
                            <div className="rounded-lg border border-dashed p-8 text-center text-neutral-500">
                                No hay definiciones de secciones configuradas.
                            </div>
                        )}
                    </div>
                </div>
            </SettingsLayout>

            <AlertDialog open={confirmDialogOpen} onOpenChange={setConfirmDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>¿Eliminar definición de sección?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Esta acción no se puede deshacer. La definición de sección será eliminada permanentemente.
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
