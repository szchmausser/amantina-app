import { Head } from '@inertiajs/react';
import {
    Calendar,
    Clock,
    Activity,
    AlertTriangle,
    TrendingUp,
    User,
} from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { ProgressCard } from '@/components/ui/progress-card';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { StatCard } from '@/components/ui/stat-card';
import { TrafficLightBadge } from '@/components/ui/traffic-light';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import type { RepresentativeDashboardData } from '@/types/dashboard';

interface Props {
    activeYear: {
        id: number;
        name: string;
        requiredHours: number;
    } | null;
    studentName: RepresentativeDashboardData['studentName'];
    studentId: RepresentativeDashboardData['studentId'];
    progress: RepresentativeDashboardData['progress'];
    last4WeeksTrend: RepresentativeDashboardData['last4WeeksTrend'];
    nextSession: RepresentativeDashboardData['nextSession'];
    healthReminder: RepresentativeDashboardData['healthReminder'];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Panel del Representante',
        href: '/representative/dashboard',
    },
];

export default function RepresentativeDashboard({
    activeYear,
    studentName,
    studentId,
    progress,
    last4WeeksTrend,
    nextSession,
    healthReminder,
}: Props) {
    if (!studentId) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Panel del Representante" />
                <div className="flex h-full flex-1 flex-col items-center justify-center gap-4 p-8">
                    <User className="h-16 w-16 text-muted-foreground" />
                    <div className="text-center">
                        <h1 className="text-xl font-semibold">
                            Sin estudiante asignado
                        </h1>
                        <p className="mt-2 text-muted-foreground">
                            No se encontró un estudiante asociado a tu cuenta.
                            <br />
                            Contacta al administrador del sistema.
                        </p>
                    </div>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Panel del Representante" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-y-auto p-4 lg:p-8">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">
                            Panel del Representante
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Progreso de {studentName}
                        </p>
                    </div>
                    {activeYear && (
                        <div className="flex items-center gap-2 rounded-lg border bg-card px-4 py-2 text-sm">
                            <Calendar className="h-4 w-4 text-muted-foreground" />
                            <span className="font-medium">
                                {activeYear.name}
                            </span>
                        </div>
                    )}
                </div>

                {/* Health Reminder */}
                {healthReminder.hasCondition && (
                    <Card className="border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-950/30">
                        <CardHeader className="pb-2">
                            <CardTitle className="flex items-center gap-2 text-blue-800 dark:text-blue-200">
                                <AlertTriangle className="h-5 w-5" />
                                Condición de Salud
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm text-blue-700 dark:text-blue-300">
                                <strong>{studentName}</strong> tiene registrado:{' '}
                                <strong>{healthReminder.conditionName}</strong>
                            </p>
                        </CardContent>
                    </Card>
                )}

                {/* Main Progress */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <ProgressCard
                            title="Horas de mi Representado"
                            currentHours={progress.totalHours}
                            quota={progress.quota}
                            status={progress.status}
                            subtitle={studentName}
                            showProgress
                            className="h-full"
                        />
                    </div>
                    <div className="space-y-4">
                        <StatCard
                            title="Horas de Jornada"
                            value={progress.jornadaHours.toFixed(1)}
                            icon={<Clock className="h-4 w-4" />}
                            description="En terreno"
                        />
                        <StatCard
                            title="Porcentaje"
                            value={`${progress.percentage.toFixed(1)}%`}
                            icon={<TrendingUp className="h-4 w-4" />}
                            description="Del total requerido"
                        />
                    </div>
                </div>

                {/* Traffic Light Status */}
                <Card>
                    <CardHeader>
                        <CardTitle>Estado Actual</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center gap-4">
                            <TrafficLightBadge status={progress.status} />
                            <span className="text-sm text-muted-foreground">
                                {progress.status === 'green' &&
                                    'El estudiante ha cumplido con el cupo requerido.'}
                                {progress.status === 'yellow' &&
                                    'El estudiante está en camino de cumplir el cupo.'}
                                {progress.status === 'red' &&
                                    'El estudiante necesita más horas para cumplir el cupo.'}
                            </span>
                        </div>
                        <div className="mt-4 grid gap-4 sm:grid-cols-3">
                            <div className="rounded-lg border p-4 text-center">
                                <p className="text-sm text-muted-foreground">
                                    Completado
                                </p>
                                <p className="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                                    {progress.totalHours.toFixed(1)}h
                                </p>
                            </div>
                            <div className="rounded-lg border p-4 text-center">
                                <p className="text-sm text-muted-foreground">
                                    Restante
                                </p>
                                <p className="text-2xl font-bold text-amber-600 dark:text-amber-400">
                                    {Math.max(
                                        0,
                                        progress.quota - progress.totalHours,
                                    ).toFixed(1)}
                                    h
                                </p>
                            </div>
                            <div className="rounded-lg border p-4 text-center">
                                <p className="text-sm text-muted-foreground">
                                    Requerido
                                </p>
                                <p className="text-2xl font-bold">
                                    {progress.quota}h
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Next Session */}
                {nextSession && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Calendar className="h-4 w-4" />
                                Próxima Sesión
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center justify-between rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950/30">
                                <div>
                                    <p className="font-medium">
                                        {nextSession.name}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {new Date(
                                            nextSession.date,
                                        ).toLocaleDateString('es-ES', {
                                            weekday: 'long',
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric',
                                        })}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        📍 {nextSession.location}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Last 4 Weeks Trend */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <TrendingUp className="h-4 w-4" />
                            Actividad de las Últimas 4 Semanas
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {last4WeeksTrend.length === 0 ? (
                            <p className="py-4 text-center text-sm text-muted-foreground">
                                No hay actividad registrada en las últimas 4
                                semanas
                            </p>
                        ) : (
                            <div className="space-y-3">
                                {last4WeeksTrend.map((week, idx) => (
                                    <div
                                        key={`${week.week}-${idx}`}
                                        className="space-y-1"
                                    >
                                        <div className="flex justify-between text-sm">
                                            <span>
                                                Semana del{' '}
                                                {new Date(
                                                    week.week,
                                                ).toLocaleDateString('es-ES')}
                                            </span>
                                            <span className="font-medium">
                                                {week.hours.toFixed(1)}h
                                            </span>
                                        </div>
                                        <div className="h-3 overflow-hidden rounded-full bg-muted">
                                            <div
                                                className="h-full rounded-full bg-blue-500 transition-all"
                                                style={{
                                                    width: `${
                                                        (week.hours /
                                                            (Math.max(
                                                                ...last4WeeksTrend.map(
                                                                    (w) =>
                                                                        w.hours,
                                                                ),
                                                            ) || 1)) *
                                                        100
                                                    }%`,
                                                }}
                                            />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
