import { Head } from '@inertiajs/react';
import {
    Calendar,
    Clock,
    Target,
    TrendingUp,
    Activity,
    FileText,
    BookOpen,
    CheckCircle2,
} from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { StatCard, StatGrid } from '@/components/ui/stat-card';
import { ProgressCard } from '@/components/ui/progress-card';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { TrafficLightBadge } from '@/components/ui/traffic-light';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import type { StudentDashboardData } from '@/types/dashboard';

interface Props {
    activeYear: {
        id: number;
        name: string;
        requiredHours: number;
    } | null;
    progress: StudentDashboardData['progress'];
    breakdownByYear: StudentDashboardData['breakdownByYear'];
    breakdownByTerm: StudentDashboardData['breakdownByTerm'];
    sessionHistory: StudentDashboardData['sessionHistory'];
    closureProjection: StudentDashboardData['closureProjection'];
    categoryParticipation: StudentDashboardData['categoryParticipation'];
    mostRecentSession: StudentDashboardData['mostRecentSession'];
    sectionAverage: StudentDashboardData['sectionAverage'];
    evidenceCount: StudentDashboardData['evidenceCount'];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Mi Progreso',
        href: '/student/dashboard',
    },
];

export default function StudentDashboard({
    activeYear,
    progress,
    breakdownByYear,
    breakdownByTerm,
    sessionHistory,
    closureProjection,
    categoryParticipation,
    mostRecentSession,
    sectionAverage,
    evidenceCount,
}: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Mi Progreso" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-y-auto p-4 lg:p-8">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">
                            Mi Progreso
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Seguimiento de horas en Socioproductiva
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

                {/* Main Progress */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <ProgressCard
                            title="Horas Acumuladas"
                            currentHours={progress.totalHours}
                            quota={progress.quota}
                            status={progress.status}
                            subtitle="Jornada + Externas"
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
                            title="Promedio de Sección"
                            value={sectionAverage.toFixed(1)}
                            icon={<TrendingUp className="h-4 w-4" />}
                            description="Comparación"
                        />
                        <StatCard
                            title="Evidencias"
                            value={evidenceCount}
                            icon={<FileText className="h-4 w-4" />}
                            description="Archivos cargados"
                        />
                    </div>
                </div>

                {/* Closure Projection */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Target className="h-4 w-4" />
                            Proyección de Cierre
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {closureProjection.isOnTrack ? (
                            <div className="flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-950/30">
                                <CheckCircle2 className="h-8 w-8 text-emerald-600 dark:text-emerald-400" />
                                <div>
                                    <p className="font-medium text-emerald-800 dark:text-emerald-200">
                                        ¡Vas bien encaminado!
                                    </p>
                                    <p className="text-sm text-emerald-700 dark:text-emerald-300">
                                        Mantén tu ritmo actual para cumplir con
                                        el cupo.
                                    </p>
                                </div>
                            </div>
                        ) : closureProjection.daysRemaining !== null ? (
                            <div className="flex items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/30">
                                <Clock className="h-8 w-8 text-amber-600 dark:text-amber-400" />
                                <div>
                                    <p className="font-medium text-amber-800 dark:text-amber-200">
                                        Proyección:{' '}
                                        {closureProjection.projectedDate}
                                    </p>
                                    <p className="text-sm text-amber-700 dark:text-amber-300">
                                        {closureProjection.daysRemaining} días
                                        restantes para cumplir el cupo.
                                    </p>
                                </div>
                            </div>
                        ) : (
                            <div className="flex items-center gap-3 rounded-lg border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/30">
                                <Clock className="h-8 w-8 text-neutral-600 dark:text-neutral-400" />
                                <div>
                                    <p className="font-medium text-neutral-800 dark:text-neutral-200">
                                        Sin datos suficientes
                                    </p>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        Completa más sesiones para calcular la
                                        proyección.
                                    </p>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Breakdown by Year */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Calendar className="h-4 w-4" />
                            Histórico por Año
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {breakdownByYear.length === 0 ? (
                                <p className="col-span-full py-4 text-center text-sm text-muted-foreground">
                                    Sin datos disponibles
                                </p>
                            ) : (
                                breakdownByYear.map((year) => (
                                    <div
                                        key={year.yearName}
                                        className="rounded-lg border p-3"
                                    >
                                        <p className="text-sm font-medium">
                                            {year.yearName}
                                        </p>
                                        <p className="text-2xl font-bold">
                                            {year.totalHours.toFixed(0)}h
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            / {year.quota}h requeridas
                                        </p>
                                    </div>
                                ))
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Breakdown by Term & Categories */}
                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Activity className="h-4 w-4" />
                                Por Período ({activeYear?.name ?? 'Año'})
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {breakdownByTerm.length === 0 ? (
                                    <p className="py-4 text-center text-sm text-muted-foreground">
                                        Sin datos disponibles
                                    </p>
                                ) : (
                                    breakdownByTerm.map((term) => (
                                        <div
                                            key={term.termName}
                                            className="space-y-1"
                                        >
                                            <div className="flex justify-between text-sm">
                                                <span>{term.termName}</span>
                                                <span className="font-medium">
                                                    {term.totalHours.toFixed(1)}
                                                    h
                                                </span>
                                            </div>
                                            <div className="h-2 overflow-hidden rounded-full bg-muted">
                                                <div
                                                    className="h-full rounded-full bg-blue-500"
                                                    style={{
                                                        width: `${
                                                            (term.totalHours /
                                                                (breakdownByTerm[0]
                                                                    ?.totalHours ||
                                                                    1)) *
                                                            100
                                                        }%`,
                                                    }}
                                                />
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <BookOpen className="h-4 w-4" />
                                Participación por Categoría
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {categoryParticipation.length === 0 ? (
                                    <p className="py-4 text-center text-sm text-muted-foreground">
                                        Sin datos disponibles
                                    </p>
                                ) : (
                                    categoryParticipation.map((cat) => (
                                        <div
                                            key={cat.categoryName}
                                            className="space-y-1"
                                        >
                                            <div className="flex justify-between text-sm">
                                                <span>{cat.categoryName}</span>
                                                <span className="font-medium">
                                                    {cat.totalHours.toFixed(1)}h
                                                    ({cat.count})
                                                </span>
                                            </div>
                                            <div className="h-2 overflow-hidden rounded-full bg-muted">
                                                <div
                                                    className="h-full rounded-full bg-purple-500"
                                                    style={{
                                                        width: `${
                                                            (cat.totalHours /
                                                                (categoryParticipation[0]
                                                                    ?.totalHours ||
                                                                    1)) *
                                                            100
                                                        }%`,
                                                    }}
                                                />
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Recent Sessions */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Clock className="h-4 w-4" />
                            Últimas Sesiones
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {sessionHistory.length === 0 ? (
                            <p className="py-4 text-center text-sm text-muted-foreground">
                                No has registrado asistencia aún
                            </p>
                        ) : (
                            <div className="space-y-3">
                                {sessionHistory.map((session, idx) => (
                                    <div
                                        key={`${session.sessionName}-${session.date}-${idx}`}
                                        className="flex items-center justify-between rounded-lg border p-3"
                                    >
                                        <div>
                                            <p className="font-medium">
                                                {session.sessionName}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {new Date(
                                                    session.date,
                                                ).toLocaleDateString(
                                                    'es-ES',
                                                )}{' '}
                                                | {session.location}
                                            </p>
                                        </div>
                                        <div className="text-right">
                                            <p className="text-lg font-bold">
                                                {session.hours.toFixed(1)}h
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Most Recent Session */}
                {mostRecentSession && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                                Última Sesión
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center justify-between rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-950/30">
                                <div>
                                    <p className="font-medium">
                                        {mostRecentSession.name}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {new Date(
                                            mostRecentSession.date,
                                        ).toLocaleDateString('es-ES')}{' '}
                                        | {mostRecentSession.location}
                                    </p>
                                </div>
                                <p className="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                                    {mostRecentSession.hours.toFixed(1)}h
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
