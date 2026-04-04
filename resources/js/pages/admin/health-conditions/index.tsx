import { Head, router, useForm } from '@inertiajs/react';
import {
    ChevronDown,
    ChevronRight,
    Edit,
    Plus,
    Save,
    Trash2,
    X,
} from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem } from '@/types';
import { index as healthConditionsIndex } from '@/routes/admin/health-conditions';

interface HealthCondition {
    id: number;
    name: string;
    is_active: boolean;
}

interface Props {
    healthConditions: HealthCondition[];
}

export default function HealthConditionsIndex({ healthConditions }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Condiciones de Salud', href: '#' },
    ];

    const [editingId, setEditingId] = useState<number | null>(null);
    const [isCreating, setIsCreating] = useState(false);
    const [openIds, setOpenIds] = useState<Set<number>>(new Set());

    const { data, setData, post, put, processing, errors, reset } = useForm({
        id: null as number | null,
        name: '',
        is_active: true,
    });

    const startEdit = (condition: HealthCondition) => {
        setEditingId(condition.id);
        setOpenIds((prev) => new Set([...prev, condition.id]));
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
        if (
            confirm(
                '¿Estás seguro de que deseas eliminar esta condición de salud?',
            )
        ) {
            router.delete(`/admin/health-conditions/${id}`);
        }
    };

    const toggleActive = (condition: HealthCondition) => {
        put(`/admin/health-conditions/${condition.id}`, {
            data: { ...condition, is_active: !condition.is_active },
        });
    };

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
                        <Card className="overflow-hidden">
                            <div className="flex items-center gap-2 border-b bg-neutral-50/50 px-6 py-3 dark:bg-neutral-800/30">
                                <span className="text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                    {data.id
                                        ? 'Editar Condición'
                                        : 'Nueva Condición'}
                                </span>
                            </div>
                            <CardContent className="p-6">
                                <form
                                    onSubmit={handleSubmit}
                                    className="space-y-4"
                                >
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="name">Nombre</Label>
                                            <Input
                                                id="name"
                                                value={data.name}
                                                onChange={(e) =>
                                                    setData(
                                                        'name',
                                                        e.target.value,
                                                    )
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
                                                    onCheckedChange={(
                                                        checked,
                                                    ) =>
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
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            <Save className="mr-2 h-4 w-4" />
                                            {data.id ? 'Actualizar' : 'Crear'}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    )}

                    {/* List */}
                    <div className="space-y-2">
                        {healthConditions.length > 0 ? (
                            healthConditions.map((condition) => (
                                <Card
                                    key={condition.id}
                                    className="overflow-hidden p-0"
                                >
                                    {/* Header */}
                                    <div className="flex items-center justify-between border-b bg-neutral-50/50 px-6 py-3 dark:bg-neutral-800/30">
                                        <div className="flex items-center gap-3">
                                            <span className="text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                                {condition.name}
                                            </span>
                                            <Badge
                                                variant={
                                                    condition.is_active
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                                className="text-[10px]"
                                            >
                                                {condition.is_active
                                                    ? 'Activa'
                                                    : 'Inactiva'}
                                            </Badge>
                                        </div>
                                        <div className="flex items-center gap-1">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="h-7 text-xs"
                                                onClick={() =>
                                                    toggleActive(condition)
                                                }
                                            >
                                                {condition.is_active
                                                    ? 'Desactivar'
                                                    : 'Activar'}
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-8 w-8 text-neutral-500 hover:text-blue-600"
                                                onClick={() =>
                                                    startEdit(condition)
                                                }
                                            >
                                                <Edit className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-8 w-8 text-red-500 hover:bg-red-50 hover:text-red-600"
                                                onClick={() =>
                                                    handleDelete(condition.id)
                                                }
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </Card>
                            ))
                        ) : (
                            <div className="rounded-lg border border-dashed p-8 text-center text-neutral-500">
                                No hay condiciones de salud configuradas.
                            </div>
                        )}
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
