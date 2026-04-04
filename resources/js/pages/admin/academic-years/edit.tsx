import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Calendar, Save } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';
import {
    index as academicYearsIndex,
    store as academicYearsStore,
    update as academicYearsUpdate,
} from '@/routes/admin/academic-years';

interface AcademicYear {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    is_active: boolean;
    required_hours: number;
}

interface Props {
    academicYear?: AcademicYear;
}

export default function AcademicYearEdit({ academicYear }: Props) {
    const isEditing = !!academicYear;

    const { data, setData, post, put, processing, errors } = useForm({
        name: academicYear?.name || '',
        start_date: academicYear?.start_date
            ? academicYear.start_date.substring(0, 10)
            : '',
        end_date: academicYear?.end_date
            ? academicYear.end_date.substring(0, 10)
            : '',
        required_hours: academicYear?.required_hours || 0,
        is_active: academicYear?.is_active || false,
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Años Escolares', href: academicYearsIndex().url },
        { title: isEditing ? 'Editar' : 'Nuevo', href: '#' },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEditing) {
            put(academicYearsUpdate(academicYear.id).url);
        } else {
            post(academicYearsStore().url);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={
                    isEditing
                        ? `Editar Año: ${academicYear.name}`
                        : 'Nuevo Año Escolar'
                }
            />

            <SettingsLayout>
                <div className="px-4 py-4">
                    {/* Header */}
                    <div className="mb-6">
                        <Button
                            variant="ghost"
                            size="sm"
                            asChild
                            className="mb-2 -ml-2 h-8"
                        >
                            <Link href={academicYearsIndex().url}>
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver al listado
                            </Link>
                        </Button>
                        <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                            {isEditing
                                ? 'Editar Año Escolar'
                                : 'Nuevo Año Escolar'}
                        </h1>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                            {isEditing
                                ? `Modifica los parámetros del ciclo lectivo ${academicYear.name}.`
                                : 'Registra un nuevo ciclo académico en el sistema.'}
                        </p>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Card */}
                        <div className="overflow-hidden rounded-xl border">
                            <div className="flex items-center gap-2 border-b bg-neutral-50 px-6 py-4 dark:bg-neutral-800/50">
                                <Calendar className="h-4 w-4 text-neutral-500" />
                                <h2 className="text-sm font-semibold">
                                    Datos del Periodo
                                </h2>
                            </div>
                            <div className="grid gap-6 p-6">
                                <div className="space-y-2">
                                    <Label htmlFor="name">
                                        Nombre del Año Escolar
                                    </Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) =>
                                            setData('name', e.target.value)
                                        }
                                        placeholder="Ej: 2025-2026"
                                        required
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-6 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="start_date">
                                            Fecha de Inicio
                                        </Label>
                                        <Input
                                            id="start_date"
                                            type="date"
                                            value={data.start_date}
                                            onChange={(e) =>
                                                setData(
                                                    'start_date',
                                                    e.target.value,
                                                )
                                            }
                                            required
                                        />
                                        <InputError
                                            message={errors.start_date}
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="end_date">
                                            Fecha de Finalización
                                        </Label>
                                        <Input
                                            id="end_date"
                                            type="date"
                                            value={data.end_date}
                                            onChange={(e) =>
                                                setData(
                                                    'end_date',
                                                    e.target.value,
                                                )
                                            }
                                            required
                                        />
                                        <InputError message={errors.end_date} />
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="required_hours">
                                        Horas Socioproductivas Requeridas
                                    </Label>
                                    <Input
                                        id="required_hours"
                                        type="number"
                                        value={data.required_hours}
                                        onChange={(e) =>
                                            setData(
                                                'required_hours',
                                                parseInt(
                                                    e.target.value.toString(),
                                                ),
                                            )
                                        }
                                        placeholder="Ej: 120"
                                        required
                                    />
                                    <p className="text-xs text-neutral-500">
                                        Cantidad de horas que cada estudiante
                                        debe cumplir en este periodo.
                                    </p>
                                    <InputError
                                        message={errors.required_hours}
                                    />
                                </div>

                                <div className="flex items-center justify-between rounded-lg border p-4">
                                    <div className="space-y-0.5">
                                        <Label
                                            htmlFor="is_active"
                                            className="text-sm font-medium"
                                        >
                                            Estado del Año Escolar
                                        </Label>
                                        <p className="text-xs text-neutral-500">
                                            Activar este año marcará
                                            automáticamente los demás como
                                            inactivos.
                                        </p>
                                    </div>
                                    <Checkbox
                                        id="is_active"
                                        checked={data.is_active}
                                        onCheckedChange={(checked: boolean) =>
                                            setData('is_active', !!checked)
                                        }
                                    />
                                </div>
                                <InputError message={errors.is_active} />
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="flex items-center justify-end gap-3">
                            <Button
                                variant="outline"
                                asChild
                                disabled={processing}
                            >
                                <Link href={academicYearsIndex().url}>
                                    Cancelar
                                </Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? (
                                    'Guardando...'
                                ) : (
                                    <>
                                        <Save className="mr-2 h-4 w-4" />
                                        {isEditing
                                            ? 'Actualizar Año'
                                            : 'Crear Año'}
                                    </>
                                )}
                            </Button>
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
