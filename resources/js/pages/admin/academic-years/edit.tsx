import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Calendar, Save } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

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
        start_date: academicYear?.start_date || '',
        end_date: academicYear?.end_date || '',
        required_hours: academicYear?.required_hours || 0,
        is_active: academicYear?.is_active || false,
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Años Escolares', href: '/admin/academic-years' },
        { title: isEditing ? 'Editar' : 'Nuevo', href: '#' },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEditing) {
            put(`/admin/academic-years/${academicYear.id}`);
        } else {
            post('/admin/academic-years');
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isEditing ? `Editar Año: ${academicYear.name}` : 'Nuevo Año Escolar'} />

            <div className="mx-auto max-w-2xl p-4 lg:p-8">
                <div className="mb-6">
                    <Button variant="ghost" size="sm" asChild className="-ml-2 mb-2 h-8">
                        <Link href="/admin/academic-years">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver al listado
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                        {isEditing ? 'Editar Año Escolar' : 'Nuevo Año Escolar'}
                    </h1>
                    <p className="text-sm text-neutral-500 dark:text-neutral-400">
                        {isEditing 
                            ? `Modifica los parámetros del ciclo lectivo ${academicYear.name}.`
                            : 'Registra un nuevo ciclo académico en el sistema.'}
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <div className="flex items-center gap-2 bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                            <Calendar className="h-4 w-4 text-neutral-500" />
                            <h2 className="text-sm font-semibold uppercase tracking-wide text-neutral-600 dark:text-neutral-300">
                                Datos del Periodo
                            </h2>
                        </div>
                        <div className="grid gap-6 p-6">
                            <div className="space-y-2">
                                <Label htmlFor="name" className="text-[10px] font-bold uppercase tracking-wider text-neutral-400">
                                    Nombre del Año Escolar
                                </Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="Ej: 2025-2026"
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-6 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="start_date" className="text-[10px] font-bold uppercase tracking-wider text-neutral-400">
                                        Fecha de Inicio
                                    </Label>
                                    <Input
                                        id="start_date"
                                        type="date"
                                        value={data.start_date}
                                        onChange={(e) => setData('start_date', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.start_date} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="end_date" className="text-[10px] font-bold uppercase tracking-wider text-neutral-400">
                                        Fecha de Finalización
                                    </Label>
                                    <Input
                                        id="end_date"
                                        type="date"
                                        value={data.end_date}
                                        onChange={(e) => setData('end_date', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.end_date} />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="required_hours" className="text-[10px] font-bold uppercase tracking-wider text-neutral-400">
                                    Horas Socioproductivas Requeridas
                                </Label>
                                <Input
                                    id="required_hours"
                                    type="number"
                                    value={data.required_hours}
                                    onChange={(e) => setData('required_hours', parseInt(e.target.value.toString()))}
                                    placeholder="Ej: 120"
                                    required
                                />
                                <p className="text-[11px] text-neutral-500">
                                    Cantidad de horas que cada estudiante debe cumplir en este periodo.
                                </p>
                                <InputError message={errors.required_hours} />
                            </div>

                            <div className="flex items-center justify-between rounded-lg border border-neutral-100 p-4 dark:border-neutral-800">
                                <div className="space-y-0.5">
                                    <Label htmlFor="is_active" className="text-sm font-medium">Estado del Año Escolar</Label>
                                    <p className="text-xs text-neutral-500">
                                        Activar este año marcará automáticamente los demás como inactivos.
                                    </p>
                                </div>
                                <Checkbox
                                    id="is_active"
                                    checked={data.is_active}
                                    onCheckedChange={(checked: boolean) => setData('is_active', checked)}
                                />
                            </div>
                            <InputError message={errors.is_active} />
                        </div>
                    </div>

                    <div className="flex items-center justify-end gap-3 border-t pt-6">
                        <Button variant="outline" asChild disabled={processing} className="h-10">
                            <Link href="/admin/academic-years">Cancelar</Link>
                        </Button>
                        <Button type="submit" disabled={processing} className="h-10 px-8">
                            {processing ? (
                                'Guardando...'
                            ) : (
                                <>
                                    <Save className="mr-2 h-4 w-4" />
                                    {isEditing ? 'Actualizar Año' : 'Crear Año'}
                                </>
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
