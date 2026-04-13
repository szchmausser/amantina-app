import { Head, router } from '@inertiajs/react';
import {
    Users,
    TrendingUp,
    AlertTriangle,
    CheckCircle2,
    Clock,
    MapPin,
    Calendar,
    BookOpen,
    Activity,
} from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { StatCard, StatGrid } from '@/components/ui/stat-card';
import { ProgressCard } from '@/components/ui/progress-card';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { TrafficLightBadge } from '@/components/ui/traffic-light';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import type {
    AdminDashboardData,
    TeacherDashboardData,
    SectionProgress,
} from '@/types/dashboard';

interface Props {
    activeYear: {
        id: number;
        name: string;
        requiredHours: number;
    } | null;
    globalCompliance: AdminDashboardData['globalCompliance'];
    sectionRanking: AdminDashboardData['sectionRanking'];
    termComparison: AdminDashboardData['termComparison'];
    sessionStats: AdminDashboardData['sessionStats'];
    alerts: AdminDashboardData['alerts'];
    activityCategoryDistribution: AdminDashboardData['activityCategoryDistribution'];
    locationDistribution: AdminDashboardData['locationDistribution'];
    teacherWorkload: AdminDashboardData['teacherWorkload'];
    yearOverYear: AdminDashboardData['yearOverYear'];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Panel de Administración',
        href: '/admin/dashboard',
    },
];

export default function AdminDashboard({
    activeYear,
    globalCompliance,
    sectionRanking,
    termComparison,
    sessionStats,
    alerts,
    activityCategoryDistribution,
    locationDistribution,
    teacherWorkload,
    yearOverYear,
}: Props) {
    const handleYearChange = (yearId: number | null) => {
        router.get(dashboard().url, yearId ? { year: yearId } : {});
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Panel de Administración" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-y-auto p-4 lg:p-8">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">
                            Panel de Administración
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Vista general del rendimiento institucional
                        </p>
                    </div>
                    {activeYear && (
                        <div className="flex items-center gap-2 rounded-lg border bg-card px-4 py-2 text-sm">
                            <Calendar className="h-4 w-4 text-muted-foreground" />
                            <span className="font-medium">
                                {activeYear.name}
                            </span>
                            <span className="text-muted-foreground">
                                ({activeYear.requiredHours}h requeridas)
                            </span>
                        </div>
                    )}
                </div>

                {/* Compliance Overview */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        title="Total Estudiantes"
                        value={globalCompliance.totalStudents}
                        icon={<Users className="h-4 w-4" />}
                        description="Matriculados activos"
                    />
                    <StatCard
                        title="En Meta"
                        value={globalCompliance.metQuota}
                        icon={
                            <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                        }
                        description={`${globalCompliance.percentage.toFixed(1)}% del total`}
                    />
                    <StatCard
                        title="En Progreso"
                        value={globalCompliance.onTrack}
                        icon={<TrendingUp className="h-4 w-4 text-amber-500" />}
                        description="40-79% completado"
                    />
                    <StatCard
                        title="En Riesgo"
                        value={globalCompliance.atRisk}
                        icon={
                            <AlertTriangle className="h-4 w-4 text-red-500" />
                        }
                        description="Menos del 40%"
                    />
                </div>

                {/* Alerts Section */}
                {(alerts.zeroHourStudents > 0 ||
                    alerts.sessionsWithoutAttendance > 0) && (
                    <Card className="border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30">
                        <CardHeader className="pb-2">
                            <CardTitle className="flex items-center gap-2 text-amber-800 dark:text-amber-200">
                                <AlertTriangle className="h-5 w-5" />
                                Alertas
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {alerts.zeroHourStudents > 0 && (
                                <p className="text-sm text-amber-700 dark:text-amber-300">
                                    {alerts.zeroHourStudents} estudiante(s) sin
                                    horas registradas
                                </p>
                            )}
                            {alerts.sessionsWithoutAttendance > 0 && (
                                <p className="text-sm text-amber-700 dark:text-amber-300">
                                    {alerts.sessionsWithoutAttendance}{' '}
                                    sesión(es) sin registro de asistencia
                                </p>
                            )}
                        </CardContent>
                    </Card>
                )}

                {/* Section Ranking */}
                <Card>
                    <CardHeader>
                        <CardTitle>Ranking de Secciones</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {sectionRanking.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No hay secciones registradas
                                </p>
                            ) : (
                                sectionRanking
                                    .slice(0, 5)
                                    .map((section, index) => (
                                        <SectionRow
                                            key={section.sectionId}
                                            section={section}
                                            rank={index + 1}
                                        />
                                    ))
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Session Stats */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <StatCard
                        title="Sesiones Completadas"
                        value={sessionStats.completed}
                        icon={
                            <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                        }
                        description="Este período"
                    />
                    <StatCard
                        title="Sesiones Canceladas"
                        value={sessionStats.cancelled}
                        icon={<Clock className="h-4 w-4 text-red-500" />}
                        description="Este período"
                    />
                    <StatCard
                        title="Horas Totales"
                        value={
                            yearOverYear[
                                yearOverYear.length - 1
                            ]?.totalHours.toFixed(0) ?? 0
                        }
                        icon={<Activity className="h-4 w-4 text-blue-500" />}
                        description="Acumuladas en el año"
                    />
                </div>

                {/* Activity Categories & Locations */}
                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <BookOpen className="h-4 w-4" />
                                Distribución por Categoría
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {activityCategoryDistribution.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        Sin datos disponibles
                                    </p>
                                ) : (
                                    activityCategoryDistribution
                                        .slice(0, 5)
                                        .map((cat) => (
                                            <div
                                                key={cat.categoryName}
                                                className="space-y-1"
                                            >
                                                <div className="flex justify-between text-sm">
                                                    <span>
                                                        {cat.categoryName}
                                                    </span>
                                                    <span className="font-medium">
                                                        {cat.totalHours.toFixed(
                                                            1,
                                                        )}
                                                        h
                                                    </span>
                                                </div>
                                                <div className="h-2 overflow-hidden rounded-full bg-muted">
                                                    <div
                                                        className="h-full rounded-full bg-blue-500"
                                                        style={{
                                                            width: `${
                                                                (cat.totalHours /
                                                                    (activityCategoryDistribution[0]
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
                                <MapPin className="h-4 w-4" />
                                Distribución por Ubicación
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {locationDistribution.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        Sin datos disponibles
                                    </p>
                                ) : (
                                    locationDistribution
                                        .slice(0, 5)
                                        .map((loc) => (
                                            <div
                                                key={loc.locationName}
                                                className="space-y-1"
                                            >
                                                <div className="flex justify-between text-sm">
                                                    <span>
                                                        {loc.locationName}
                                                    </span>
                                                    <span className="font-medium">
                                                        {loc.sessionCount}{' '}
                                                        sesiones
                                                    </span>
                                                </div>
                                                <div className="h-2 overflow-hidden rounded-full bg-muted">
                                                    <div
                                                        className="h-full rounded-full bg-purple-500"
                                                        style={{
                                                            width: `${
                                                                (loc.sessionCount /
                                                                    (locationDistribution[0]
                                                                        ?.sessionCount ||
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

                {/* Teacher Workload */}
                <Card>
                    <CardHeader>
                        <CardTitle>Carga de Trabajo por Profesor</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left">
                                        <th className="pb-2 font-medium">
                                            Profesor
                                        </th>
                                        <th className="pb-2 text-right font-medium">
                                            Sesiones
                                        </th>
                                        <th className="pb-2 text-right font-medium">
                                            Horas
                                        </th>
                                        <th className="pb-2 text-right font-medium">
                                            Asistencia Promedio
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {teacherWorkload.length === 0 ? (
                                        <tr>
                                            <td
                                                colSpan={4}
                                                className="py-4 text-center text-muted-foreground"
                                            >
                                                Sin datos disponibles
                                            </td>
                                        </tr>
                                    ) : (
                                        teacherWorkload.map((teacher) => (
                                            <tr
                                                key={teacher.teacherId}
                                                className="border-b"
                                            >
                                                <td className="py-2">
                                                    {teacher.teacherName}
                                                </td>
                                                <td className="py-2 text-right">
                                                    {teacher.sessionCount}
                                                </td>
                                                <td className="py-2 text-right">
                                                    {teacher.totalHours.toFixed(
                                                        1,
                                                    )}
                                                </td>
                                                <td className="py-2 text-right">
                                                    {teacher.averageAttendance.toFixed(
                                                        1,
                                                    )}
                                                    %
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                {/* Year over Year */}
                <Card>
                    <CardHeader>
                        <CardTitle>Comparación por Año</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {yearOverYear.map((year) => (
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
                                        {year.studentCount} estudiantes |{' '}
                                        {year.averagePerStudent.toFixed(1)}
                                        h/estudiante
                                    </p>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

function SectionRow({
    section,
    rank,
}: {
    section: SectionProgress;
    rank: number;
}) {
    const status =
        section.averageProgress >= 80
            ? 'green'
            : section.averageProgress >= 40
              ? 'yellow'
              : 'red';

    return (
        <div className="flex items-center gap-4">
            <span className="flex h-6 w-6 items-center justify-center rounded-full bg-muted text-xs font-medium">
                {rank}
            </span>
            <div className="flex-1">
                <div className="flex items-center gap-2">
                    <span className="font-medium">
                        {section.sectionName} ({section.gradeName})
                    </span>
                    <TrafficLightBadge status={status} />
                </div>
                <p className="text-xs text-muted-foreground">
                    {section.studentCount} estudiantes |{' '}
                    {section.averageProgress.toFixed(1)}% promedio
                </p>
            </div>
        </div>
    );
}
