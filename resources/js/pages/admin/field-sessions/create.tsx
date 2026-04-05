import { Head, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Clock, Save, Tag } from 'lucide-react';
import { useEffect, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { ComboboxInput } from '@/components/ui/combobox-input';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

interface Teacher {
    id: number;
    name: string;
    cedula: string;
}

interface Status {
    id: number;
    name: string;
}

interface CatalogItem {
    id: number;
    name: string;
}

interface Props {
    activeYearId: number;
    activeYearName: string;
    teachers: Teacher[];
    statuses: Status[];
    activityCategories: CatalogItem[];
    locations: CatalogItem[];
}

export default function FieldSessionCreate({
    activeYearId,
    activeYearName,
    teachers,
    statuses,
    activityCategories,
    locations,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Jornadas de Campo', href: '/admin/field-sessions' },
        { title: 'Nueva Jornada', href: '#' },
    ];

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        school_term_id: '',
        user_id: '',
        activity_name: '',
        location_name: '',
        start_datetime: '',
        end_datetime: '',
        status_id: 'planned',
        cancellation_reason: '',
    });

    const [baseHours, setBaseHours] = useState<string>('0');

    useEffect(() => {
        if (data.start_datetime && data.end_datetime) {
            const start = new Date(data.start_datetime);
            const end = new Date(data.end_datetime);
            if (end > start) {
                const hours = (
                    (end.getTime() - start.getTime()) /
                    3600000
                ).toFixed(2);
                setBaseHours(hours);
            } else {
                setBaseHours('0');
            }
        }
    }, [data.start_datetime, data.end_datetime]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.post('/admin/field-sessions', {
            name: data.name,
            description: data.description,
            school_term_id: data.school_term_id
                ? parseInt(data.school_term_id)
                : null,
            user_id: data.user_id ? parseInt(data.user_id) : null,
            activity_name: data.activity_name || null,
            location_name: data.location_name || null,
            start_datetime: data.start_datetime,
            end_datetime: data.end_datetime,
            status_id: data.status_id ? parseInt(data.status_id) : null,
            cancellation_reason: data.cancellation_reason || null,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva Jornada de Campo" />

            <SettingsLayout>
                <div className="flex flex-col gap-6">
                    {/* Header */}
                    <div className="flex items-center gap-4">
                        <Button
                            variant="ghost"
                            size="icon"
                            className="h-8 w-8"
                            onClick={() =>
                                router.visit('/admin/field-sessions')
                            }
                        >
                            <ArrowLeft className="h-4 w-4" />
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                Nueva Jornada de Campo
                            </h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Registra una nueva actividad de campo, ya sea
                                planificada o realizada.
                            </p>
                        </div>
                    </div>

                    {/* Form */}
                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Basic Info */}
                        <div className="space-y-4 rounded-xl border p-6">
                            <h3 className="text-sm font-semibold tracking-wider text-neutral-500 uppercase">
                                Información Básica
                            </h3>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2 sm:col-span-2">
                                    <Label htmlFor="name">
                                        Nombre de la Jornada
                                    </Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) =>
                                            setData('name', e.target.value)
                                        }
                                        placeholder="Ej: Jornada de siembra en el huerto escolar"
                                        required
                                    />
                                    <InputError message={errors.name} />
                                </div>
                                <div className="space-y-2 sm:col-span-2">
                                    <Label htmlFor="description">
                                        Descripción (opcional)
                                    </Label>
                                    <textarea
                                        id="description"
                                        className="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                        value={data.description}
                                        onChange={(
                                            e: React.ChangeEvent<HTMLTextAreaElement>,
                                        ) =>
                                            setData(
                                                'description',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Describe los detalles de la jornada..."
                                        rows={3}
                                    />
                                    <InputError message={errors.description} />
                                </div>
                            </div>
                        </div>

                        {/* Academic Context */}
                        <div className="space-y-4 rounded-xl border p-6">
                            <h3 className="text-sm font-semibold tracking-wider text-neutral-500 uppercase">
                                Contexto Académico
                            </h3>
                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="space-y-2">
                                    <Label>Año Escolar</Label>
                                    <div className="flex h-10 items-center rounded-md border border-input bg-transparent px-3 text-sm text-neutral-700 dark:text-neutral-300">
                                        {activeYearName}
                                    </div>
                                    <p className="text-xs text-neutral-500">
                                        Año escolar activo
                                    </p>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="user_id">
                                        Profesor Responsable
                                    </Label>
                                    <Select
                                        value={data.user_id}
                                        onValueChange={(val) =>
                                            setData('user_id', val)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar profesor" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {teachers.map((teacher) => (
                                                <SelectItem
                                                    key={teacher.id}
                                                    value={teacher.id.toString()}
                                                >
                                                    {teacher.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.user_id} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="status_id">Estado</Label>
                                    <Select
                                        value={data.status_id}
                                        onValueChange={(val) =>
                                            setData('status_id', val)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {statuses.map((status) => (
                                                <SelectItem
                                                    key={status.id}
                                                    value={status.id.toString()}
                                                >
                                                    {status.name ===
                                                        'planned' &&
                                                        'Planificada'}
                                                    {status.name ===
                                                        'realized' &&
                                                        'Realizada'}
                                                    {status.name ===
                                                        'cancelled' &&
                                                        'Cancelada'}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.status_id} />
                                </div>
                            </div>
                        </div>

                        {/* Schedule */}
                        <div className="space-y-4 rounded-xl border p-6">
                            <h3 className="flex items-center gap-2 text-sm font-semibold tracking-wider text-neutral-500 uppercase">
                                <Clock className="h-4 w-4" />
                                Horario
                            </h3>
                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="space-y-2">
                                    <Label htmlFor="start_datetime">
                                        Fecha y Hora de Inicio
                                    </Label>
                                    <Input
                                        id="start_datetime"
                                        type="datetime-local"
                                        value={data.start_datetime}
                                        onChange={(e) =>
                                            setData(
                                                'start_datetime',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                    <InputError
                                        message={errors.start_datetime}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="end_datetime">
                                        Fecha y Hora de Fin
                                    </Label>
                                    <Input
                                        id="end_datetime"
                                        type="datetime-local"
                                        value={data.end_datetime}
                                        onChange={(e) =>
                                            setData(
                                                'end_datetime',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                    <InputError message={errors.end_datetime} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Horas Base</Label>
                                    <div className="flex h-10 items-center rounded-md border border-input bg-transparent px-3 font-mono text-sm">
                                        {baseHours} horas
                                    </div>
                                    <p className="text-xs text-neutral-500">
                                        Calculado automáticamente
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Activity & Location */}
                        <div className="space-y-4 rounded-xl border p-6">
                            <h3 className="flex items-center gap-2 text-sm font-semibold tracking-wider text-neutral-500 uppercase">
                                <Tag className="h-4 w-4" />
                                Actividad y Ubicación
                            </h3>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="activity_name">
                                        Categoría de Actividad
                                    </Label>
                                    <ComboboxInput
                                        value={data.activity_name}
                                        onChange={(val) =>
                                            setData('activity_name', val)
                                        }
                                        options={activityCategories.map(
                                            (cat) => ({
                                                value: cat.name,
                                                label: cat.name,
                                            }),
                                        )}
                                        placeholder="Seleccionar o escribir..."
                                        emptyMessage="Sin categorías"
                                    />
                                    <InputError
                                        message={errors.activity_name}
                                    />
                                    <p className="text-xs text-neutral-500">
                                        Selecciona del catálogo o crea una nueva
                                    </p>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="location_name">
                                        Ubicación
                                    </Label>
                                    <ComboboxInput
                                        value={data.location_name}
                                        onChange={(val) =>
                                            setData('location_name', val)
                                        }
                                        options={locations.map((loc) => ({
                                            value: loc.name,
                                            label: loc.name,
                                        }))}
                                        placeholder="Seleccionar o escribir..."
                                        emptyMessage="Sin ubicaciones"
                                    />
                                    <InputError
                                        message={errors.location_name}
                                    />
                                    <p className="text-xs text-neutral-500">
                                        Selecciona del catálogo o crea una nueva
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Cancellation Reason */}
                        {data.status_id ===
                            (() => {
                                const cancelled = statuses.find(
                                    (s) => s.name === 'cancelled',
                                );
                                return cancelled ? cancelled.id.toString() : '';
                            })() && (
                            <div className="space-y-4 rounded-xl border border-red-200 bg-red-50 p-6 dark:border-red-900/30 dark:bg-red-950/20">
                                <h3 className="text-sm font-semibold tracking-wider text-red-600 uppercase">
                                    Motivo de Cancelación
                                </h3>
                                <div className="space-y-2">
                                    <Label htmlFor="cancellation_reason">
                                        Explica por qué se cancela la jornada
                                    </Label>
                                    <textarea
                                        id="cancellation_reason"
                                        className="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                        value={data.cancellation_reason}
                                        onChange={(
                                            e: React.ChangeEvent<HTMLTextAreaElement>,
                                        ) =>
                                            setData(
                                                'cancellation_reason',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Motivo de la cancelación..."
                                        rows={3}
                                        required
                                    />
                                    <InputError
                                        message={errors.cancellation_reason}
                                    />
                                </div>
                            </div>
                        )}

                        {/* Actions */}
                        <div className="flex items-center justify-end gap-2">
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() =>
                                    router.visit('/admin/field-sessions')
                                }
                            >
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={processing}>
                                <Save className="mr-2 h-4 w-4" />
                                Crear Jornada
                            </Button>
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
