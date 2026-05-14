import { Head } from '@inertiajs/react';
import {
    ArrowLeft,
    Calendar,
    Clock,
    Target,
    TrendingUp,
    Activity,
    BookOpen,
    CheckCircle2,
    MapPin,
    Trophy,
} from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { StatCard, StatGrid } from '@/components/ui/stat-card';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { Info } from 'lucide-react';
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
    backUrl?: string;
    viewingAs?: string;
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
    backUrl,
    viewingAs,
}: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Mi Progreso" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-y-auto p-4 lg:p-8">
                <TooltipProvider>
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">
                            {viewingAs ? `Progreso de ${viewingAs}` : 'Mi Progreso'}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {viewingAs ? 'Vista como representante' : 'Seguimiento de horas en Socioproductiva'}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        {backUrl && (
                            <button
                                onClick={() => window.history.back()}
                                className="inline-flex items-center gap-1.5 rounded-lg border bg-card px-3 py-1.5 text-sm transition-colors hover:bg-accent"
                            >
                                <ArrowLeft className="h-4 w-4" />
                                Volver
                            </button>
                        )}
                        {activeYear && (
                        <div className="flex items-center gap-2 rounded-lg border bg-card px-4 py-2 text-sm">
                            <Calendar className="h-4 w-4 text-muted-foreground" />
                            <span className="font-medium">
                                {activeYear.name}
                            </span>
                        </div>
                    )}
                    </div>
                </div>

                {/* Histórico por Año — primera tarjeta */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Calendar className="h-4 w-4" />
                            Histórico por Año
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {breakdownByYear.length === 0 ? (
                            <p className="py-4 text-center text-sm text-muted-foreground">
                                Sin datos disponibles
                            </p>
                        ) : (
                            <div className="space-y-4">
                                {/* Per-year rows */}
                                {breakdownByYear.map((year) => {
                                    const pct = (year.totalHours / year.quota) * 100;
                                    const isCurrent = activeYear && year.yearName === activeYear.name;
                                    return (
                                        <div key={year.yearName} className="space-y-1">
                                            <div className="flex items-end justify-between text-sm">
                                                <span className="font-medium">
                                                    {year.yearName}
                                                    {isCurrent && (
                                                        <span className="ml-1.5 rounded bg-blue-100 px-1.5 py-0.5 text-[10px] font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                                            Actual
                                                        </span>
                                                    )}
                                                </span>
                                                <span className="tabular-nums">
                                                    <span className="font-medium">{year.totalHours.toFixed(0)}h</span>
                                                    <span className="text-muted-foreground"> / {year.quota}h</span>
                                                </span>
                                            </div>
                                            <div className="h-2 overflow-hidden rounded-full bg-muted">
                                                <div
                                                    className={`h-full rounded-full ${
                                                        pct >= 100
                                                            ? 'bg-emerald-500'
                                                            : pct >= 80
                                                              ? 'bg-emerald-400'
                                                              : pct >= 40
                                                                ? 'bg-amber-400'
                                                                : 'bg-red-400'
                                                    }`}
                                                    style={{ width: `${Math.min(pct, 100)}%` }}
                                                />
                                            </div>
                                        </div>
                                    );
                                })}
                                {/* Total row */}
                                {(() => {
                                    const totalJornada = breakdownByYear.reduce((sum, y) => sum + y.totalHours, 0);
                                    const totalExternas = progress.externalHours ?? 0;
                                    const totalHours = totalJornada + totalExternas;
                                    const totalQuota = breakdownByYear.reduce((sum, y) => sum + y.quota, 0);
                                    const totalPct = totalQuota > 0 ? (totalHours / totalQuota) * 100 : 0;
                                    return (
                                        <div className="space-y-1 border-t pt-4">
                                            <div className="flex items-end justify-between text-sm">
                                                <span className="font-semibold">Total acumulado</span>
                                                <span className="tabular-nums font-semibold">
                                                    {totalHours.toFixed(0)}h / {totalQuota}h
                                                </span>
                                            </div>
                                            {totalExternas > 0 && (
                                                <p className="text-xs text-muted-foreground">
                                                    Incluye {totalJornada.toFixed(0)}h de jornadas + {totalExternas.toFixed(1)}h externas
                                                </p>
                                            )}
                                            <div className="h-2 overflow-hidden rounded-full bg-muted">
                                                <div
                                                    className="h-full rounded-full bg-purple-500"
                                                    style={{ width: `${Math.min(totalPct, 100)}%` }}
                                                />
                                            </div>
                                            <p className="text-xs text-muted-foreground">
                                                {totalPct.toFixed(0)}% del total requerido (
                                                {breakdownByYear.length} años)
                                            </p>
                                        </div>
                                    );
                                })()}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Top Row: Closure Projection + Most Recent Session */}
                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Target className="h-4 w-4" />
                                Proyección de Cierre
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <Info className="h-3.5 w-3.5 cursor-help text-muted-foreground" />
                                    </TooltipTrigger>
                                    <TooltipContent side="right" className="max-w-xs">
                                        <div className="space-y-1 text-xs">
                                            <p className="mb-1 font-medium">Tu ruta de progreso:</p>
                                            <p>🏆 100% — ¡LO LOGRASTE!</p>
                                            <p>✅ 90% — ¡A un paso!</p>
                                            <p>✅ 80% — ¡Casi lo logras!</p>
                                            <p>✅ 70% — ¡Excelente progreso!</p>
                                            <p>✅ 60% — ¡Sigue así!</p>
                                            <p>📈 50% — ¡A mitad de camino!</p>
                                            <p>📈 40% — ¡Buen avance!</p>
                                            <p>📈 30% — ¡Vas progresando!</p>
                                            <p>📈 20% — ¡Agarrando ritmo!</p>
                                            <p>📈 10% — ¡Buen comienzo!</p>
                                            <p>📈 1% — ¡Primeros pasos!</p>
                                            <p>🎯 0% — ¡Tu viaje comienza!</p>
                                            <p className="mt-1 font-medium text-emerald-600 dark:text-emerald-400">
                                                ⬤ Estás aquí: {progress.percentage.toFixed(0)}%
                                            </p>
                                        </div>
                                    </TooltipContent>
                                </Tooltip>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {(() => {
                                const pct = progress.percentage;
                                const tiers = [
                                    { min: 100, Icon: Trophy, colors: { card: 'border-emerald-300 bg-emerald-100 dark:border-emerald-700 dark:bg-emerald-950/40', icon: 'text-emerald-600 dark:text-emerald-400', title: 'text-emerald-800 dark:text-emerald-200', text: 'text-emerald-700 dark:text-emerald-300' }, title: '🎉 ¡LO LOGRASTE!', desc: `Completaste las ${activeYear?.requiredHours ?? 0}h requeridas. ¡Meta cumplida!` },
                                    { min: 90,  Icon: TrendingUp, colors: { card: 'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/30', icon: 'text-emerald-600 dark:text-emerald-400', title: 'text-emerald-800 dark:text-emerald-200', text: 'text-emerald-700 dark:text-emerald-300' }, title: '¡A un paso!', desc: 'Solo te falta un 10%. ¡Último tramo!' },
                                    { min: 80,  Icon: CheckCircle2, colors: { card: 'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/30', icon: 'text-emerald-600 dark:text-emerald-400', title: 'text-emerald-800 dark:text-emerald-200', text: 'text-emerald-700 dark:text-emerald-300' }, title: '¡Casi lo logras!', desc: 'Superaste el 80%. ¡No aflojes ahora!' },
                                    { min: 70,  Icon: CheckCircle2, colors: { card: 'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/30', icon: 'text-emerald-600 dark:text-emerald-400', title: 'text-emerald-800 dark:text-emerald-200', text: 'text-emerald-700 dark:text-emerald-300' }, title: '¡Excelente progreso!', desc: 'Ya superaste el 70%. Cada vez más cerca.' },
                                    { min: 60,  Icon: TrendingUp, colors: { card: 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30', icon: 'text-amber-600 dark:text-amber-400', title: 'text-amber-800 dark:text-amber-200', text: 'text-amber-700 dark:text-amber-300' }, title: '¡Sigue así!', desc: 'Superaste el 60%. Vas por buen camino.' },
                                    { min: 50,  Icon: TrendingUp, colors: { card: 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30', icon: 'text-amber-600 dark:text-amber-400', title: 'text-amber-800 dark:text-amber-200', text: 'text-amber-700 dark:text-amber-300' }, title: '¡A mitad de camino!', desc: 'Llegaste al 50%. ¡Lo estás logrando!' },
                                    { min: 40,  Icon: TrendingUp, colors: { card: 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30', icon: 'text-amber-600 dark:text-amber-400', title: 'text-amber-800 dark:text-amber-200', text: 'text-amber-700 dark:text-amber-300' }, title: '¡Buen avance!', desc: 'Superaste el 40%. Mantén el ritmo.' },
                                    { min: 30,  Icon: TrendingUp, colors: { card: 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30', icon: 'text-amber-600 dark:text-amber-400', title: 'text-amber-800 dark:text-amber-200', text: 'text-amber-700 dark:text-amber-300' }, title: '¡Vas progresando!', desc: 'Ya tienes un 30%. ¡No te detengas!' },
                                    { min: 20,  Icon: TrendingUp, colors: { card: 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30', icon: 'text-amber-600 dark:text-amber-400', title: 'text-amber-800 dark:text-amber-200', text: 'text-amber-700 dark:text-amber-300' }, title: '¡Agarrando ritmo!', desc: 'Superaste el 20%. Sigue adelante.' },
                                    { min: 10,  Icon: TrendingUp, colors: { card: 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30', icon: 'text-amber-600 dark:text-amber-400', title: 'text-amber-800 dark:text-amber-200', text: 'text-amber-700 dark:text-amber-300' }, title: '¡Buen comienzo!', desc: 'Ya tienes un 10%. Cada hora cuenta.' },
                                    { min: 1,   Icon: TrendingUp, colors: { card: 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30', icon: 'text-amber-600 dark:text-amber-400', title: 'text-amber-800 dark:text-amber-200', text: 'text-amber-700 dark:text-amber-300' }, title: '¡Primeros pasos!', desc: 'Empezaste a sumar horas. ¡Sigue!' },
                                ];
                                const tier = tiers.find((t) => pct >= t.min);
                                if (tier) {
                                    const { Icon, colors } = tier;
                                    return (
                                        <div className={`flex items-center gap-3 rounded-lg border p-4 ${colors.card}`}>
                                            <Icon className={`h-8 w-8 ${colors.icon}`} />
                                            <div>
                                                <p className={`font-medium ${colors.title}`}>{tier.title}</p>
                                                <p className={`text-sm ${colors.text}`}>{tier.desc}</p>
                                            </div>
                                        </div>
                                    );
                                }
                                return (
                                    <div className="flex items-center gap-3 rounded-lg border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/30">
                                        <Target className="h-8 w-8 text-neutral-600 dark:text-neutral-400" />
                                        <div>
                                            <p className="font-medium text-neutral-800 dark:text-neutral-200">¡Tu viaje comienza aquí!</p>
                                            <p className="text-sm text-neutral-600 dark:text-neutral-400">Participa en tu primera jornada para empezar a acumular horas.</p>
                                        </div>
                                    </div>
                                );
                            })()}
                        </CardContent>
                    </Card>

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

                {/* Breakdown by Term */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Activity className="h-4 w-4" />
                            Horas por Lapso ({activeYear?.name ?? 'Año'})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-col gap-6 lg:flex-row">
                            {/* Total acumulado */}
                            <div className="flex shrink-0 flex-col items-center justify-center rounded-lg border bg-card p-6 lg:w-48">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Total acumulado
                                </p>
                                <p className="text-3xl font-bold tabular-nums">
                                    {progress.totalHours.toFixed(1)}h
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    de {activeYear?.requiredHours ?? 0}h requeridas
                                </p>
                            </div>
                            {/* Promedio de Sección */}
                            <div className="flex shrink-0 flex-col items-center justify-center rounded-lg border bg-card p-6 lg:w-48">
                                <div className="mb-1 flex items-center gap-1 text-sm font-medium text-muted-foreground">
                                    <TrendingUp className="h-4 w-4" />
                                    Promedio de sección
                                </div>
                                <p className="text-3xl font-bold tabular-nums">
                                    {sectionAverage.toFixed(1)}h
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    Comparación
                                </p>
                            </div>
                            {/* Tarjetas por lapso */}
                            <div className="grid flex-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {breakdownByTerm.length === 0 ? (
                                    <p className="col-span-full py-4 text-center text-sm text-muted-foreground">
                                        Sin datos disponibles
                                    </p>
                                ) : (
                                    breakdownByTerm.map((term) => {
                                        const pct = activeYear
                                            ? (term.totalHours / activeYear.requiredHours) * 100
                                            : 0;
                                        const color =
                                            pct >= 80
                                                ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/30'
                                                : pct >= 40
                                                  ? 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30'
                                                  : 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950/30';
                                        return (
                                            <div
                                                key={term.termName}
                                                className={`flex flex-col items-center justify-center rounded-lg border p-3 ${color}`}
                                            >
                                                <p className="text-sm font-medium">{term.termName}</p>
                                                <p className="text-2xl font-bold">{term.totalHours.toFixed(1)}h</p>
                                                <p className="text-xs text-muted-foreground">
                                                    {pct.toFixed(0)}% de la cuota
                                                </p>
                                            </div>
                                        );
                                    })
                                )}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Categories */}
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
                            <div className="space-y-2">
                                {sessionHistory.map((session, idx) => {
                                    return (
                                        <div
                                            key={`${session.sessionName}-${session.date}-${idx}`}
                                            className="flex items-center justify-between rounded-lg border border-l-4 border-l-blue-400 bg-neutral-50 p-3 transition-colors hover:bg-neutral-200/50 dark:border-l-blue-500 dark:bg-neutral-900/20 dark:hover:bg-neutral-800/50"
                                        >
                                            <div className="min-w-0 flex-1">
                                                <p className="font-medium truncate">
                                                    {session.sessionName}
                                                </p>
                                                {session.activities && session.activities.length > 0 && (
                                                    <div className="mt-1 flex flex-wrap gap-1">
                                                        {session.activities.map((act, i) => (
                                                            <span
                                                                key={i}
                                                                className="inline-flex items-center gap-0.5 rounded bg-emerald-100 px-1.5 py-0.5 text-[11px] font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
                                                            >
                                                                {act.categoryName}: {act.hours.toFixed(1)}h
                                                            </span>
                                                        ))}
                                                    </div>
                                                )}
                                                <p className="mt-0.5 flex flex-wrap items-center gap-x-2 text-xs text-muted-foreground">
                                                    <span className="flex items-center gap-1">
                                                        <Calendar className="h-3 w-3" />
                                                        {new Date(session.date).toLocaleDateString('es-ES')}
                                                    </span>
                                                    {session.location && (
                                                        <span className="flex items-center gap-1">
                                                            <MapPin className="h-3 w-3" />
                                                            {session.location}
                                                        </span>
                                                    )}
                                                </p>
                                            </div>
                                            <span className="ml-3 shrink-0 rounded-md bg-card px-2.5 py-1 text-lg font-bold tabular-nums text-emerald-600 dark:text-emerald-400">
                                                {session.hours.toFixed(1)}h
                                            </span>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </CardContent>
                </Card>
                </TooltipProvider>
            </div>
        </AppLayout>
    );
}
