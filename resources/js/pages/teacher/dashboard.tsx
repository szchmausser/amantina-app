import { Head, router } from '@inertiajs/react';
import {
    BookOpen,
    CheckCircle2,
    Clock,
    AlertTriangle,
    Calendar,
    Activity,
    Users,
} from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { StatCard, StatGrid } from '@/components/ui/stat-card';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { TrafficLightBadge } from '@/components/ui/traffic-light';
import { ProgressCard } from '@/components/ui/progress-card';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import type { TeacherDashboardData, SectionProgress } from '@/types/dashboard';

interface Props {
    activeYear: {
        id: number;
        name: string;
        requiredHours: number;
    } | null;
    sections: TeacherDashboardData['sections'];
    ownSessions: TeacherDashboardData['ownSessions'];
    pendingAttendance: TeacherDashboardData['pendingAttendance'];
    lowAttendanceStudents: TeacherDashboardData['lowAttendanceStudents'];
    categoryDistribution: TeacherDashboardData['categoryDistribution'];
    sessionsPerTerm: TeacherDashboardData['sessionsPerTerm'];
    healthReminders: TeacherDashboardData['healthReminders'];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Panel del Profesor',
        href: '/teacher/dashboard',
    },
];

export default function TeacherDashboard({
    activeYear,
    sections,
    ownSessions,
    pendingAttendance,
    lowAttendanceStudents,
    categoryDistribution,
    sessionsPerTerm,
    healthReminders,
}: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Panel del Profesor" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-y-auto p-4 lg:p-8">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">
                            Panel del Profesor
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Resumen de tus secciones y sesiones
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

                {/* Session Stats */}
                <StatGrid columns={4}>
                    <StatCard
                        title="Mis Sesiones"
                        value={ownSessions.total}
                        icon={<BookOpen className="h-4 w-4" />}
                        description="Total programadas"
                    />
                    <StatCard
                        title="Completadas"
                        value={ownSessions.completed}
                        icon={
                            <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                        }
                        description="Este período"
                    />
                    <StatCard
                        title="Canceladas"
                        value={ownSessions.cancelled}
                        icon={<Clock className="h-4 w-4 text-red-500" />}
                        description="Este período"
                    />
                    <StatCard
                        title="Pendientes de Asistencia"
                        value={pendingAttendance}
                        icon={
                            <AlertTriangle className="h-4 w-4 text-amber-500" />
                        }
                        description="Sin registro"
                    />
                </StatGrid>

                {/* Alerts Section */}
                {lowAttendanceStudents.length > 0 && (
                    <Card className="border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30">
                        <CardHeader className="pb-2">
                            <CardTitle className="flex items-center gap-2 text-amber-800 dark:text-amber-200">
                                <AlertTriangle className="h-5 w-5" />
                                Estudiantes con Baja Asistencia
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                {lowAttendanceStudents.map((student) => (
                                    <div
                                        key={student.studentId}
                                        className="flex items-center justify-between rounded-lg border bg-card p-3"
                                    >
                                        <div>
                                            <p className="font-medium">
                                                {student.studentName}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {student.sectionName}
                                            </p>
                                        </div>
                                        <div className="text-right">
                                            <p className="text-sm font-medium text-red-600 dark:text-red-400">
                                                {student.attendanceCount}{' '}
                                                asistencia(s)
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Menos de 3 en el período
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Health Reminders */}
                {healthReminders.length > 0 && (
                    <Card className="border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-950/30">
                        <CardHeader className="pb-2">
                            <CardTitle className="flex items-center gap-2 text-blue-800 dark:text-blue-200">
                                <Activity className="h-5 w-5" />
                                Recordatorios de Salud
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                {healthReminders.map((reminder) => (
                                    <div
                                        key={`${reminder.studentId}-${reminder.conditionName}`}
                                        className="flex items-center justify-between rounded-lg border bg-card p-3"
                                    >
                                        <div>
                                            <p className="font-medium">
                                                {reminder.studentName}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {reminder.conditionName}
                                            </p>
                                        </div>
                                        <div className="text-right text-xs text-muted-foreground">
                                            Última sesión:{' '}
                                            {reminder.lastSessionDate
                                                ? new Date(
                                                      reminder.lastSessionDate,
                                                  ).toLocaleDateString('es-ES')
                                                : 'Sin registros'}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Sections */}
                <div className="space-y-4">
                    <h2 className="text-lg font-semibold">Mis Secciones</h2>
                    {sections.length === 0 ? (
                        <Card>
                            <CardContent className="py-8 text-center text-muted-foreground">
                                No tienes secciones asignadas
                            </CardContent>
                        </Card>
                    ) : (
                        sections.map((section) => (
                            <SectionCard
                                key={section.sectionId}
                                section={section}
                            />
                        ))
                    )}
                </div>

                {/* Category Distribution */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <BookOpen className="h-4 w-4" />
                            Distribución por Categoría
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            {categoryDistribution.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Sin datos disponibles
                                </p>
                            ) : (
                                categoryDistribution.map((cat) => (
                                    <div
                                        key={cat.categoryName}
                                        className="space-y-1"
                                    >
                                        <div className="flex justify-between text-sm">
                                            <span>{cat.categoryName}</span>
                                            <span className="font-medium">
                                                {cat.totalHours.toFixed(1)}h
                                            </span>
                                        </div>
                                        <div className="h-2 overflow-hidden rounded-full bg-muted">
                                            <div
                                                className="h-full rounded-full bg-blue-500"
                                                style={{
                                                    width: `${
                                                        (cat.totalHours /
                                                            (categoryDistribution[0]
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

                {/* Sessions per Term */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Calendar className="h-4 w-4" />
                            Sesiones por Período
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {sessionsPerTerm.length === 0 ? (
                                <p className="col-span-full text-sm text-muted-foreground">
                                    Sin datos disponibles
                                </p>
                            ) : (
                                sessionsPerTerm.map((term) => (
                                    <div
                                        key={term.termName}
                                        className="rounded-lg border p-3 text-center"
                                    >
                                        <p className="text-sm font-medium">
                                            {term.termName}
                                        </p>
                                        <p className="text-2xl font-bold">
                                            {term.count}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            sesiones
                                        </p>
                                    </div>
                                ))
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

function SectionCard({ section }: { section: SectionProgress }) {
    const sectionAvg =
        section.students.length > 0
            ? section.students.reduce((sum, s) => sum + s.hours.percentage, 0) /
              section.students.length
            : 0;

    const status =
        sectionAvg >= 80 ? 'green' : sectionAvg >= 40 ? 'yellow' : 'red';

    return (
        <Card>
            <CardHeader>
                <div className="flex items-center justify-between">
                    <CardTitle className="flex items-center gap-2">
                        <Users className="h-4 w-4" />
                        {section.sectionName} ({section.gradeName})
                    </CardTitle>
                    <TrafficLightBadge status={status} />
                </div>
            </CardHeader>
            <CardContent>
                <div className="mb-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {section.students.slice(0, 4).map((student) => (
                        <ProgressCard
                            key={student.studentId}
                            title={student.studentName}
                            currentHours={student.hours.totalHours}
                            quota={student.hours.quota}
                            status={student.hours.status}
                            subtitle={`${student.hours.percentage.toFixed(1)}%`}
                            showProgress={false}
                            className="text-sm"
                        />
                    ))}
                </div>
                {section.students.length > 4 && (
                    <p className="text-center text-sm text-muted-foreground">
                        +{section.students.length - 4} estudiantes más
                    </p>
                )}
            </CardContent>
        </Card>
    );
}
