import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Calendar, Clock, MapPin, Tag, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem } from '@/types';

interface FieldSession {
    id: number;
    name: string;
    description: string | null;
    start_datetime: string;
    end_datetime: string;
    base_hours: string;
    activity_name: string | null;
    location_name: string | null;
    cancellation_reason: string | null;
    academic_year: { id: number; name: string };
    school_term: { id: number; name: string } | null;
    teacher: { id: number; name: string; cedula: string };
    status: { id: number; name: string; description: string | null };
}

interface Props {
    fieldSession: FieldSession;
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

export default function FieldSessionShow({ fieldSession }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Jornadas de Campo', href: '/admin/field-sessions' },
        { title: fieldSession.name, href: '#' },
    ];

    const handleDelete = () => {
        if (
            confirm(
                '¿Estás seguro de que deseas eliminar esta jornada de campo?',
            )
        ) {
            router.delete(`/admin/field-sessions/${fieldSession.id}`);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={fieldSession.name} />

            <SettingsLayout>
                <div className="flex flex-col gap-6">
                    {/* Header */}
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <Button
                                variant="ghost"
                                size="icon"
                                className="h-8 w-8"
                                asChild
                            >
                                <Link href="/admin/field-sessions">
                                    <ArrowLeft className="h-4 w-4" />
                                </Link>
                            </Button>
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                    {fieldSession.name}
                                </h1>
                                <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                    Detalles de la jornada de campo
                                </p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Button variant="outline" asChild>
                                <Link
                                    href={`/admin/field-sessions/${fieldSession.id}/edit`}
                                >
                                    Editar
                                </Link>
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="h-8 w-8 text-red-500 hover:bg-red-50 hover:text-red-600"
                                onClick={handleDelete}
                            >
                                <Trash2 className="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    {/* Status */}
                    <div className="flex items-center gap-2">
                        <Badge
                            variant="outline"
                            className={
                                statusColors[fieldSession.status.name] || ''
                            }
                        >
                            {statusLabels[fieldSession.status.name] ||
                                fieldSession.status.name}
                        </Badge>
                    </div>

                    {/* Details */}
                    <div className="grid gap-6 sm:grid-cols-2">
                        {/* Info */}
                        <div className="space-y-4 rounded-xl border p-6">
                            <h3 className="text-sm font-semibold tracking-wider text-neutral-500 uppercase">
                                Información
                            </h3>
                            {fieldSession.description && (
                                <p className="text-sm text-neutral-700 dark:text-neutral-300">
                                    {fieldSession.description}
                                </p>
                            )}
                            <div className="space-y-2">
                                <div className="flex justify-between text-sm">
                                    <span className="text-neutral-500">
                                        Profesor
                                    </span>
                                    <span className="font-medium text-neutral-900 dark:text-neutral-100">
                                        {fieldSession.teacher.name}
                                    </span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-neutral-500">
                                        Año Escolar
                                    </span>
                                    <span className="font-medium text-neutral-900 dark:text-neutral-100">
                                        {fieldSession.academic_year.name}
                                    </span>
                                </div>
                                {fieldSession.school_term && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-neutral-500">
                                            Lapso
                                        </span>
                                        <span className="font-medium text-neutral-900 dark:text-neutral-100">
                                            {fieldSession.school_term.name}
                                        </span>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Schedule */}
                        <div className="space-y-4 rounded-xl border p-6">
                            <h3 className="flex items-center gap-2 text-sm font-semibold tracking-wider text-neutral-500 uppercase">
                                <Clock className="h-4 w-4" />
                                Horario
                            </h3>
                            <div className="space-y-2 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-neutral-500">
                                        Inicio
                                    </span>
                                    <span className="font-medium text-neutral-900 dark:text-neutral-100">
                                        {new Date(
                                            fieldSession.start_datetime,
                                        ).toLocaleString('es-ES')}
                                    </span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-neutral-500">
                                        Fin
                                    </span>
                                    <span className="font-medium text-neutral-900 dark:text-neutral-100">
                                        {new Date(
                                            fieldSession.end_datetime,
                                        ).toLocaleString('es-ES')}
                                    </span>
                                </div>
                                <div className="flex justify-between border-t pt-2">
                                    <span className="font-medium text-neutral-500">
                                        Horas Base
                                    </span>
                                    <span className="font-mono font-bold text-neutral-900 dark:text-neutral-100">
                                        {fieldSession.base_hours}h
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Activity & Location */}
                        <div className="space-y-4 rounded-xl border p-6 sm:col-span-2">
                            <h3 className="flex items-center gap-2 text-sm font-semibold tracking-wider text-neutral-500 uppercase">
                                <Tag className="h-4 w-4" />
                                Actividad y Ubicación
                            </h3>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-1">
                                    <span className="text-xs text-neutral-500">
                                        Categoría
                                    </span>
                                    <div className="flex items-center gap-2">
                                        <Tag className="h-4 w-4 text-neutral-400" />
                                        <span className="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                            {fieldSession.activity_name ||
                                                'No especificada'}
                                        </span>
                                    </div>
                                </div>
                                <div className="space-y-1">
                                    <span className="text-xs text-neutral-500">
                                        Ubicación
                                    </span>
                                    <div className="flex items-center gap-2">
                                        <MapPin className="h-4 w-4 text-neutral-400" />
                                        <span className="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                            {fieldSession.location_name ||
                                                'No especificada'}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Cancellation */}
                        {fieldSession.status.name === 'cancelled' &&
                            fieldSession.cancellation_reason && (
                                <div className="space-y-2 rounded-xl border border-red-200 bg-red-50 p-6 sm:col-span-2 dark:border-red-900/30 dark:bg-red-950/20">
                                    <h3 className="text-sm font-semibold tracking-wider text-red-600 uppercase">
                                        Motivo de Cancelación
                                    </h3>
                                    <p className="text-sm text-red-800 dark:text-red-300">
                                        {fieldSession.cancellation_reason}
                                    </p>
                                </div>
                            )}
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
