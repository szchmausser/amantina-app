import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    BookOpen,
    Calendar,
    CalendarPlus,
    CheckCircle2,
    ChevronDown,
    ChevronUp,
    ClipboardCheck,
    Clock,
    ExternalLink,
    Info,
    MapPin,
    Star,
    TrendingUp,
    Trophy,
    Users,
    XCircle,
} from 'lucide-react';
import { useState, useMemo } from 'react';
import AppLayout from '@/layouts/app-layout';
import { StatCard, StatGrid } from '@/components/ui/stat-card';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { StudentListBadge } from '@/components/ui/student-list-badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { TrafficLightBadge } from '@/components/ui/traffic-light';
import { ProgressCard } from '@/components/ui/progress-card';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import type {
    TeacherDashboardData,
    EnhancedSectionProgress,
    TeacherScopedStudent,
    UpcomingSession,
    TrafficLightStatus,
} from '@/types/dashboard';

// --- Helper: maps TeacherScopedStudent to StudentListBadge-compatible shape ---
function toBadgeStudent(s: TeacherScopedStudent) {
    return {
        id: s.id,
        name: s.name,
        hours: s.hours,
        percentage: s.percentage,
        section: s.sectionName,
        grade: s.gradeName,
        status: s.status,
    };
}

// --- Props ---
interface Props {
    activeYear: TeacherDashboardData['activeYear'];
    availableYears: { id: number; name: string; isActive: boolean }[];
    sections: TeacherDashboardData['sections'];
    ownSessions: TeacherDashboardData['ownSessions'];
    pendingAttendance: TeacherDashboardData['pendingAttendance'];
    lowAttendanceStudents: TeacherDashboardData['lowAttendanceStudents'];
    categoryDistribution: TeacherDashboardData['categoryDistribution'];
    sessionsPerTerm: TeacherDashboardData['sessionsPerTerm'];
    healthReminders: TeacherDashboardData['healthReminders'];
    totalStudents: TeacherDashboardData['totalStudents'];
    distribution: TeacherDashboardData['distribution'];
    onTrackStudents: TeacherDashboardData['onTrackStudents'];
    inProgressStudents: TeacherDashboardData['inProgressStudents'];
    atRiskStudents: TeacherDashboardData['atRiskStudents'];
    outstandingStudents: TeacherDashboardData['outstandingStudents'];
    topStudents: TeacherDashboardData['topStudents'];
    studentsWithNoHours: TeacherDashboardData['studentsWithNoHours'];
    upcomingSessions: TeacherDashboardData['upcomingSessions'];
    grades: TeacherDashboardData['grades'];
    filterSections: TeacherDashboardData['filterSections'];
    selectedGradeId: TeacherDashboardData['selectedGradeId'];
    selectedSectionId: TeacherDashboardData['selectedSectionId'];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Panel del Profesor', href: '/teacher/dashboard' },
];

// --- Main Component ---
export default function TeacherDashboard({
    activeYear,
    availableYears,
    sections,
    ownSessions,
    pendingAttendance,
    lowAttendanceStudents,
    categoryDistribution,
    sessionsPerTerm,
    healthReminders,
    totalStudents,
    distribution,
    onTrackStudents,
    inProgressStudents,
    atRiskStudents,
    outstandingStudents,
    topStudents,
    studentsWithNoHours,
    upcomingSessions,
    grades,
    filterSections,
    selectedGradeId,
    selectedSectionId,
}: Props) {
    const yearRequiredHours = activeYear?.requiredHours ?? 0;

    // Low attendance collapse state
    const [lowAttendanceExpanded, setLowAttendanceExpanded] = useState(false);

    // Category expansion state — multiple categories can be open simultaneously
    const [expandedCategories, setExpandedCategories] = useState<Set<string>>(new Set());

    const toggleCategory = (categoryName: string) => {
        setExpandedCategories(prev => {
            const next = new Set(prev);
            if (next.has(categoryName)) next.delete(categoryName);
            else next.add(categoryName);
            return next;
        });
    };

    // Scroll to sections handler
    const scrollToSections = () => {
        document
            .getElementById('teacher-sections')
            ?.scrollIntoView({ behavior: 'smooth' });
    };

    // Handle grade/section filter changes for category distribution
    const handleGradeChange = (value: string) => {
        router.get(
            dashboard().url,
            {
                year: activeYear?.id,
                grade_id: value === 'all' ? null : value,
                section_id: null,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleSectionChange = (value: string) => {
        router.get(
            dashboard().url,
            {
                year: activeYear?.id,
                grade_id: selectedGradeId,
                section_id: value === 'all' ? null : value,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    // Filter sections by selected grade
    const filteredSections = useMemo(() => {
        if (selectedGradeId) {
            return filterSections.filter(s => s.grade_id === selectedGradeId);
        }
        return filterSections;
    }, [filterSections, selectedGradeId]);

    // Compute average hours from distribution data
    const totalHours =
        onTrackStudents.reduce((sum, s) => sum + s.hours, 0) +
        inProgressStudents.reduce((sum, s) => sum + s.hours, 0) +
        atRiskStudents.reduce((sum, s) => sum + s.hours, 0) +
        studentsWithNoHours.reduce((sum, s) => sum + s.hours, 0);

    const averageHours =
        totalStudents > 0
            ? parseFloat((totalHours / totalStudents).toFixed(1))
            : 0;

    const totalCategoryHours = useMemo(
        () => categoryDistribution.reduce((sum, cat) => sum + cat.totalHours, 0),
        [categoryDistribution],
    );

    const handleYearChange = (value: string) => {
        router.get(
            dashboard().url,
            {
                year: value,
                grade_id: null,
                section_id: null,
            },
            { preserveState: false, preserveScroll: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Panel del Profesor" />

            <TooltipProvider>
                <div className="flex h-full flex-1 flex-col gap-6 overflow-y-auto p-4 lg:p-8">
                    {/* ===== HEADER ===== */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-foreground">
                                Panel del Profesor
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                Vista general de tus secciones y estudiantes
                            </p>
                        </div>
                        <div className="flex flex-wrap items-center gap-2">
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
                            <Select
                                value={activeYear?.id.toString()}
                                onValueChange={handleYearChange}
                            >
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Año escolar" />
                                </SelectTrigger>
                                <SelectContent>
                                    {availableYears.map((year) => (
                                        <SelectItem key={year.id} value={year.id.toString()}>
                                            {year.name}{year.isActive ? ' (actual)' : ''}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    {/* ===== QUICK ACTIONS ===== */}
                    <QuickActions onScrollToSections={scrollToSections} />

                    {/* ===== SESSION STATS ===== */}
                    <StatGrid columns={4}>
                        <StatCard
                            title="Mis Jornadas"
                            value={ownSessions.total}
                            icon={<BookOpen className="h-4 w-4" />}
                            description="Total de jornadas"
                            tooltip="Total de jornadas de campo donde fuiste asignado como profesor responsable."
                            data-testid="teacher-stat-total"
                        />
                        <StatCard
                            title="Completadas"
                            value={ownSessions.completed}
                            icon={
                                <CheckCircle2 className="h-4 w-4 text-emerald-500 dark:text-emerald-400" />
                            }
                            description="Este período"
                            tooltip="Jornadas finalizadas exitosamente."
                            data-testid="teacher-stat-completed"
                        />
                        <StatCard
                            title="Canceladas"
                            value={ownSessions.cancelled}
                            icon={<Clock className="h-4 w-4 text-red-500 dark:text-red-400" />}
                            description="Este período"
                            tooltip="Jornadas que fueron canceladas."
                            data-testid="teacher-stat-cancelled"
                        />
                        <StatCard
                            title="Pendientes de Asistencia"
                            value={pendingAttendance}
                            icon={
                                <AlertTriangle className="h-4 w-4 text-amber-500 dark:text-amber-400" />
                            }
                            description="Sin registro"
                            tooltip="Jornadas donde aún no se ha registrado la asistencia de los estudiantes."
                            data-testid="teacher-stat-pending"
                        />
                    </StatGrid>

                    {/* ===== UPCOMING SESSIONS ===== */}
                    <UpcomingSessionsCard sessions={upcomingSessions} />

                    {/* ===== AT-RISK ALERT CARD (dedicated) ===== */}
                    {atRiskStudents.length > 0 && (
                        <Card
                            className="border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-950/30"
                            data-testid="teacher-at-risk-alert"
                        >
                            <CardHeader className="pb-2">
                                <CardTitle className="flex items-center gap-2 text-red-800 dark:text-red-200">
                                    <AlertTriangle className="h-5 w-5" />
                                    Estudiantes en Riesgo
                                </CardTitle>
                                <p className="text-sm text-red-700 dark:text-red-300">
                                    {atRiskStudents.length} estudiante(s) con
                                    menos del 40% de la cuota. Requieren
                                    atención inmediata.
                                </p>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-2">
                                    {atRiskStudents.map((student) => (
                                        <button
                                            key={student.id}
                                            onClick={() =>
                                                router.visit(
                                                    `/admin/users/${student.id}`,
                                                )
                                            }
                                            className="flex w-full items-center justify-between rounded-lg border bg-card p-3 text-left transition-colors hover:bg-accent"
                                        >
                                            <div>
                                                <p className="font-medium">
                                                    {student.name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {student.sectionName} (
                                                    {student.gradeName})
                                                </p>
                                            </div>
                                            <div className="text-right">
                                                <p className="text-sm font-medium text-red-600 dark:text-red-400">
                                                    {student.hours}h /{' '}
                                                    {student.quota}h
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {student.percentage.toFixed(
                                                        1,
                                                    )}
                                                    %
                                                </p>
                                            </div>
                                        </button>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* ===== HEALTH REMINDERS ===== */}
                    {healthReminders.length > 0 && (
                        <Card className="border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-950/30">
                            <CardHeader className="pb-2">
                                <CardTitle className="flex items-center gap-2 text-blue-800 dark:text-blue-200">
                                    <Info className="h-5 w-5" />
                                    Recordatorios de Salud
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-2">
                                    {healthReminders.map((reminder) => (
                                        <button
                                            key={`${reminder.studentId}-${reminder.conditionName}`}
                                            onClick={() =>
                                                router.visit(
                                                    `/admin/users/${reminder.studentId}`,
                                                )
                                            }
                                            className="flex w-full items-center justify-between rounded-lg border bg-card p-3 text-left transition-colors hover:bg-accent"
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
                                                {reminder.severity === 'high' && (
                                                    <span className="rounded-full bg-red-100 px-2 py-0.5 text-red-700 dark:bg-red-900/30 dark:text-red-300">
                                                        Alta
                                                    </span>
                                                )}
                                                {reminder.severity === 'medium' && (
                                                    <span className="rounded-full bg-amber-100 px-2 py-0.5 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                                                        Media
                                                    </span>
                                                )}
                                                {reminder.severity === 'low' && (
                                                    <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                                        Baja
                                                    </span>
                                                )}
                                                {reminder.daysSinceLastSession > 0 && (
                                                    <span className="ml-2">
                                                        Hace{' '}
                                                        {reminder.daysSinceLastSession}{' '}
                                                        días
                                                    </span>
                                                )}
                                            </div>
                                        </button>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* ===== STUDENT DISTRIBUTION ===== */}
                    <StudentDistributionCard
                        distribution={distribution}
                        totalStudents={totalStudents}
                        requiredHours={yearRequiredHours}
                        averageHours={averageHours}
                        onTrackStudents={onTrackStudents}
                        inProgressStudents={inProgressStudents}
                        atRiskStudents={atRiskStudents}
                        studentsWithNoHours={studentsWithNoHours}
                    />

                    {/* ===== RANKINGS ROW ===== */}
                    <div className="grid gap-4 xl:grid-cols-3 lg:grid-cols-2">
                        {/* Outstanding Students */}
                        <OutstandingCard
                            students={outstandingStudents}
                            requiredHours={yearRequiredHours}
                        />

                        {/* Top Hours */}
                        <TopHoursCard students={topStudents} />

                        {/* At Risk */}
                        <AtRiskCard students={atRiskStudents} />
                    </div>

                    {/* ===== SECTIONS ===== */}
                    <div id="teacher-sections" className="space-y-4">
                        <h2 className="text-lg font-semibold">
                            Mis Secciones
                        </h2>
                        {sections.length === 0 ? (
                            <Card>
                                <CardContent className="py-8 text-center text-muted-foreground">
                                    No tienes secciones asignadas
                                </CardContent>
                            </Card>
                        ) : (
                            <div className="space-y-6">
                                {sections.map((section) => (
                                    <TeacherSectionCard
                                        key={section.sectionId}
                                        section={section}
                                    />
                                ))}
                            </div>
                        )}
                    </div>

                    {/* ===== CATEGORY DISTRIBUTION ===== */}
                    <Card>
                        <CardHeader>
                            <div className="space-y-3">
                                {/* Filter row */}
                                <div className="flex flex-wrap items-center gap-3">
                                    <span className="text-sm font-medium text-muted-foreground">Filtrar por:</span>
                                    <Select
                                        value={selectedGradeId?.toString() ?? 'all'}
                                        onValueChange={handleGradeChange}
                                    >
                                        <SelectTrigger className="w-[180px]">
                                            <SelectValue placeholder="Grado" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Todos los grados</SelectItem>
                                            {grades.map((grade) => (
                                                <SelectItem key={grade.id} value={grade.id.toString()}>
                                                    {grade.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <Select
                                        value={selectedSectionId?.toString() ?? 'all'}
                                        onValueChange={handleSectionChange}
                                    >
                                        <SelectTrigger className="w-[180px]">
                                            <SelectValue placeholder="Sección" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Todas las secciones</SelectItem>
                                            {filteredSections.map((section) => (
                                                <SelectItem key={section.id} value={section.id.toString()}>
                                                    {section.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <CardTitle className="flex items-center gap-2">
                                    <BookOpen className="h-4 w-4" />
                                    Distribución por Categoría
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <Info className="h-3 w-3 cursor-help text-muted-foreground" />
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p className="text-xs">Horas totales acumuladas por <strong>todos</strong> tus estudiantes en cada tipo de actividad durante tus jornadas de campo. No es un promedio por estudiante, es la suma total.</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {categoryDistribution.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        Sin datos disponibles
                                    </p>
                                ) : (
                                    categoryDistribution.map((cat) => {
                                        const isExpanded = expandedCategories.has(cat.categoryName);
                                        const hasStudents = cat.students && cat.students.length > 0;
                                        return (
                                        <div
                                            key={cat.categoryName}
                                            className="space-y-1.5"
                                        >
                                            {/* Title row: name + badges */}
                                            <div className="flex items-center gap-2">
                                                <button
                                                    onClick={() =>
                                                        toggleCategory(cat.categoryName)
                                                    }
                                                    className="flex items-center gap-1 text-sm text-left font-medium transition-colors hover:text-foreground"
                                                >
                                                    {isExpanded ? (
                                                        <ChevronUp className="h-3.5 w-3.5 shrink-0" />
                                                    ) : (
                                                        <ChevronDown className="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                                                    )}
                                                    {cat.categoryName}
                                                </button>
                                                <div className="ml-auto flex shrink-0 gap-1.5">
                                                    {cat.sessionCount > 0 && (
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <span className="inline-flex items-center gap-1 rounded-md border bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-950/30 dark:border-amber-800 dark:text-amber-300 min-w-[110px] justify-center cursor-help">
                                                                    {cat.sessionCount} jornadas
                                                                    <Info className="h-3 w-3 text-amber-500 dark:text-amber-400" />
                                                                </span>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p className="text-xs max-w-56">Cantidad de jornadas distintas donde se realizó esta actividad.</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {cat.count > 0 && (
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <span className="inline-flex items-center gap-1 rounded-md border bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-950/30 dark:border-purple-800 dark:text-purple-300 min-w-[110px] justify-center cursor-help">
                                                                    {cat.count} participaciones
                                                                    <Info className="h-3 w-3 text-purple-500 dark:text-purple-400" />
                                                                </span>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p className="text-xs max-w-56">Cantidad de registros de asistencia donde se asignó esta actividad. Cada alumno en cada jornada cuenta como uno. Muchas participaciones con pocas horas = sesiones cortas. Pocas participaciones con muchas horas = jornadas intensivas. Muchas participaciones sobre pocos alumnos = unos pocos hacen casi todo. Muchas participaciones repartidas entre muchos = amplia participación. Si una categoría domina el total, hay dependencia. Si tiene muy pocas o cero, puede estar abandonada. Compara entre lapsos para ver estacionalidad y entre categorías para balancear la carga de los estudiantes.</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    <Tooltip>
                                                        <TooltipTrigger asChild>
                                                            <span className="inline-flex items-center gap-1 rounded-md border bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-950/30 dark:border-blue-800 dark:text-blue-300 min-w-[110px] justify-center cursor-help">
                                                                {cat.totalHours.toFixed(1)} horas totales
                                                                <Info className="h-3 w-3 text-blue-500 dark:text-blue-400" />
                                                            </span>
                                                        </TooltipTrigger>
                                                        <TooltipContent>
                                                                <p className="text-xs max-w-56">Suma de horas de todos los estudiantes en esta categoría. Muchas horas con pocas participaciones = jornadas largas e intensivas. Pocas horas con muchas participaciones = sesiones breves pero frecuentes. Compara contra la cuota requerida para saber si esta actividad está aportando lo suficiente al cumplimiento. Si el porcentaje del total es muy alto, hay concentración excesiva en esta categoría. Si es muy bajo, puede estar desatendida.</p>
                                                        </TooltipContent>
                                                    </Tooltip>
                                                    {totalCategoryHours > 0 && (
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <span className="inline-flex items-center gap-1 rounded-md border bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-950/30 dark:border-emerald-800 dark:text-emerald-300 min-w-[110px] justify-center cursor-help">
                                                                    {(cat.totalHours / totalCategoryHours * 100).toFixed(1)}% del total
                                                                    <Info className="h-3 w-3 text-emerald-500 dark:text-emerald-400" />
                                                                </span>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p className="text-xs">Porcentaje que representa esta categoría del total de horas en todas las categorías.</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                </div>
                                            </div>
                                            {/* Progress bar */}
                                            <div className="h-2 overflow-hidden rounded-full bg-muted">
                                                <div
                                                    className="h-full rounded-full bg-blue-500"
                                                    style={{
                                                        width: `${Math.min(
                                                            (cat.totalHours /
                                                                (categoryDistribution[0]
                                                                    ?.totalHours ||
                                                                     1)) *
                                                                 100,
                                                             100,
                                                        )}%`,
                                                    }}
                                                />
                                            </div>
                                            {/* Expanded student list */}
                                            {isExpanded && hasStudents && (
                                                <div className="space-y-1.5 pt-1">
                                                    {cat.students.map((student) => (
                                                        <button
                                                            key={student.studentId}
                                                            onClick={(e) => {
                                                                e.stopPropagation();
                                                                router.visit(
                                                                    `/admin/users/${student.studentId}`,
                                                                );
                                                            }}
                                                            className="flex w-full items-center justify-between rounded-lg border bg-card px-3 py-2 text-left text-sm transition-colors hover:bg-accent"
                                                        >
                                                            <div>
                                                                <span className="font-medium">{student.studentName}</span>
                                                                <span className="ml-1.5 text-xs text-muted-foreground">
                                                                    · {student.gradeName} {student.sectionName}
                                                                </span>
                                                            </div>
                                                            <span className="flex items-center gap-2 text-muted-foreground">
                                                                <span>{student.hours.toFixed(1)}h</span>
                                                                <span className="text-xs">
                                                                    ({student.percentage}%)
                                                                </span>
                                                            </span>
                                                        </button>
                                                    ))}
                                                </div>
                                            )}
                                            {isExpanded && !hasStudents && (
                                                <p className="py-1 text-xs text-muted-foreground">
                                                    Sin estudiantes registrados
                                                </p>
                                            )}
                                        </div>
                                    )})
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* ===== SESSIONS PER TERM ===== */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Calendar className="h-4 w-4" />
                                Sesiones por Período
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <Info className="h-3 w-3 cursor-help text-muted-foreground" />
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p className="text-xs">Cantidad de jornadas realizadas en cada lapso del año escolar.</p>
                                    </TooltipContent>
                                </Tooltip>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
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

                    {/* ===== LOW ATTENDANCE ALERT ===== */}
                    {lowAttendanceStudents.length > 0 && (
                        <Card className="border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30">
                            <CardHeader className="pb-2">
                                <div className="flex items-center justify-between">
                                    <CardTitle className="flex items-center gap-2 text-amber-800 dark:text-amber-200">
                                        <AlertTriangle className="h-5 w-5" />
                                        Estudiantes con Baja Asistencia
                                    </CardTitle>
                                    {lowAttendanceExpanded && (
                                        <button
                                            onClick={() =>
                                                setLowAttendanceExpanded(false)
                                            }
                                            className="rounded-md p-1 text-amber-700 transition-colors hover:bg-amber-100 dark:text-amber-300 dark:hover:bg-amber-900/50"
                                            aria-label="Colapsar lista de baja asistencia"
                                        >
                                            <ChevronUp className="h-5 w-5" />
                                        </button>
                                    )}
                                </div>
                                {!lowAttendanceExpanded && (
                                    <p className="text-sm text-amber-700 dark:text-amber-300">
                                        {lowAttendanceStudents.length}{' '}
                                        estudiante(s) con baja asistencia
                                    </p>
                                )}
                            </CardHeader>
                            <CardContent>
                                {lowAttendanceExpanded && (
                                    <div
                                        className="space-y-2"
                                        data-testid="teacher-low-attendance-list"
                                    >
                                        {lowAttendanceStudents.map((student) => (
                                            <button
                                                key={student.studentId}
                                                onClick={() => {
                                                    document
                                                        .getElementById(
                                                            `teacher-section-${student.sectionId}`,
                                                        )
                                                        ?.scrollIntoView({
                                                            behavior: 'smooth',
                                                        });
                                                }}
                                                className="flex w-full items-center justify-between rounded-lg border bg-card p-3 text-left transition-colors hover:bg-accent"
                                            >
                                                <div className="flex items-center gap-3">
                                                    <div>
                                                        <p className="font-medium">
                                                            Alumno:{' '}
                                                            {student.studentName}
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            Grado Académico:{' '}
                                                            {student.gradeName}
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            Sección:{' '}
                                                            {student.sectionName}
                                                        </p>
                                                    </div>
                                                    {student.attendanceCount === 0 && (
                                                        <span className="shrink-0 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-300">
                                                            Crítico
                                                        </span>
                                                    )}
                                                    {student.attendanceCount === 1 && (
                                                        <span className="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                                                            Bajo
                                                        </span>
                                                    )}
                                                </div>
                                                <div className="shrink-0 text-right">
                                                    <p className="text-sm font-medium text-red-600 dark:text-red-400">
                                                        {student.attendanceCount}{' '}
                                                        asistencia(s)
                                                    </p>
                                                    {student.totalHours > 0 && (
                                                        <p className="text-xs text-muted-foreground">
                                                            {student.totalHours.toFixed(
                                                                1,
                                                            )}
                                                            h acumuladas
                                                        </p>
                                                    )}
                                                </div>
                                            </button>
                                        ))}
                                    </div>
                                )}
                                <button
                                    onClick={() =>
                                        setLowAttendanceExpanded(
                                            !lowAttendanceExpanded,
                                        )
                                    }
                                    className="mt-3 flex w-full items-center justify-center gap-1.5 rounded-lg border border-dashed p-2 text-center text-sm text-muted-foreground transition-colors hover:bg-accent"
                                    data-testid="teacher-low-attendance-toggle"
                                >
                                    {lowAttendanceExpanded
                                        ? 'Ocultar'
                                        : `Ver ${lowAttendanceStudents.length} estudiantes`}
                                    {lowAttendanceExpanded ? (
                                        <ChevronUp className="h-4 w-4" />
                                    ) : (
                                        <ChevronDown className="h-4 w-4" />
                                    )}
                                </button>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </TooltipProvider>
        </AppLayout>
    );
}

// ======================================================================
// SUB-COMPONENTS
// ======================================================================

// --- Quick Actions ---
function QuickActions({
    onScrollToSections,
}: {
    onScrollToSections: () => void;
}) {
    const actions = [
        {
            key: 'registrar-asistencia',
            label: 'Registrar Asistencia',
            icon: <ClipboardCheck className="h-5 w-5" />,
            href: '/admin/field-sessions',
            description: 'Registrar asistencia de jornadas pendientes',
        },
        {
            key: 'nueva-jornada',
            label: 'Nueva Jornada',
            icon: <CalendarPlus className="h-5 w-5" />,
            href: '/admin/field-sessions/create',
            description: 'Programar una nueva jornada de campo',
        },
        {
            key: 'mis-secciones',
            label: 'Mis Secciones',
            icon: <Users className="h-5 w-5" />,
            onClick: onScrollToSections,
            description: 'Ver el detalle de tus secciones',
        },
    ];

    return (
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            {actions.map((action) => (
                <ActionButton key={action.key} action={action} />
            ))}
        </div>
    );
}

function ActionButton({
    action,
}: {
    action: {
        key: string;
        label: string;
        icon: React.ReactNode;
        href?: string;
        onClick?: () => void;
        description: string;
    };
}) {
    const className =
        'flex items-center gap-3 rounded-lg border bg-card p-4 text-left transition-colors hover:bg-accent';
    const content = (
        <>
            {action.icon}
            <div>
                <p className="text-sm font-medium">{action.label}</p>
                <p className="text-xs text-muted-foreground">
                    {action.description}
                </p>
            </div>
        </>
    );

    if (action.href) {
        return (
            <Link
                href={action.href}
                className={className}
                data-testid={`teacher-quick-action-${action.key}`}
            >
                {content}
            </Link>
        );
    }

    return (
        <button
            onClick={action.onClick}
            className={className}
            data-testid={`teacher-quick-action-${action.key}`}
        >
            {content}
        </button>
    );
}

// --- Student Distribution Card ---
function StudentDistributionCard({
    distribution,
    totalStudents,
    requiredHours,
    averageHours,
    onTrackStudents,
    inProgressStudents,
    atRiskStudents,
    studentsWithNoHours,
}: {
    distribution: TeacherDashboardData['distribution'];
    totalStudents: number;
    requiredHours: number;
    averageHours: number;
    onTrackStudents: TeacherScopedStudent[];
    inProgressStudents: TeacherScopedStudent[];
    atRiskStudents: TeacherScopedStudent[];
    studentsWithNoHours: TeacherScopedStudent[];
}) {
    const categories = [
        {
            key: 'on-track',
            label: 'En Meta',
            count: distribution.onTrack,
            students: onTrackStudents,
            icon: (
                <CheckCircle2 className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
            ),
            variant: 'success' as const,
            bgClass: 'bg-emerald-50 dark:bg-emerald-950/30',
            infoClass:
                'text-emerald-600 dark:text-emerald-400',
            tooltip: 'Estudiantes con ≥80% de la cuota completada. Van bien encaminados para cumplir.',
            threshold: '≥80% de la cuota',
        },
        {
            key: 'in-progress',
            label: 'En Progreso',
            count: distribution.inProgress,
            students: inProgressStudents,
            icon: (
                <TrendingUp className="h-5 w-5 text-amber-600 dark:text-amber-400" />
            ),
            variant: 'warning' as const,
            bgClass: 'bg-amber-50 dark:bg-amber-950/30',
            infoClass: 'text-amber-600 dark:text-amber-400',
            tooltip:
                'Estudiantes con 40-79% de la cuota. Avanzan pero necesitan mantener el ritmo.',
            threshold: '40-79% de la cuota',
        },
        {
            key: 'at-risk',
            label: 'En Riesgo',
            count: distribution.atRisk,
            students: atRiskStudents,
            icon: (
                <AlertTriangle className="h-5 w-5 text-red-600 dark:text-red-400" />
            ),
            variant: 'destructive' as const,
            bgClass: 'bg-red-50 dark:bg-red-950/30',
            infoClass: 'text-red-600 dark:text-red-400',
            tooltip:
                'Estudiantes con <40% de la cuota. Difícilmente cumplirán sin intervención.',
            threshold: '<40% de la cuota',
        },
        {
            key: 'zero-hours',
            label: 'Sin Horas',
            count: distribution.zeroHours,
            students: studentsWithNoHours,
            icon: (
                <XCircle className="h-5 w-5 text-gray-600 dark:text-gray-400" />
            ),
            variant: 'secondary' as const,
            bgClass: 'bg-gray-50 dark:bg-gray-900/30',
            infoClass: 'text-gray-600 dark:text-gray-400',
            tooltip:
                'Estudiantes sin horas registradas en el año actual.',
            threshold: '0 horas registradas',
        },
    ];

    return (
        <Card>
            <CardHeader>
                <div className="flex items-center justify-between">
                    <CardTitle className="flex items-center gap-2">
                        <Users className="h-5 w-5" />
                        Estudiantes Activos: {totalStudents}
                    </CardTitle>
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <button className="rounded-full p-1 hover:bg-accent">
                                <Info className="h-4 w-4 text-muted-foreground" />
                            </button>
                        </TooltipTrigger>
                        <TooltipContent className="max-w-xs">
                            <p className="text-sm">
                                Distribución de estudiantes según su progreso
                                hacia la cuota de {requiredHours}h requeridas
                                para el año escolar.
                            </p>
                        </TooltipContent>
                    </Tooltip>
                </div>
                <p className="text-sm text-muted-foreground">
                    Cuota requerida: {requiredHours}h | Promedio actual:{' '}
                    {averageHours}h
                </p>
            </CardHeader>
            <CardContent>
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {categories.map((cat) => (
                        <div
                            key={cat.key}
                            className={`rounded-lg border p-4 ${cat.bgClass}`}
                            data-testid={`teacher-dist-${cat.key}`}
                        >
                            <div className="mb-2 flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    {cat.icon}
                                    <span className="font-medium">
                                        {cat.label}
                                    </span>
                                </div>
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <button
                                            className={`rounded-full p-1 ${cat.infoClass}`}
                                        >
                                            <Info className="h-3 w-3" />
                                        </button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p className="text-sm">{cat.tooltip}</p>
                                    </TooltipContent>
                                </Tooltip>
                            </div>
                            <StudentListBadge
                                count={cat.count}
                                label="estudiantes"
                                students={cat.students.map(toBadgeStudent)}
                                variant={cat.variant}
                                className="text-lg"
                            />
                            <p className="mt-1 text-xs text-muted-foreground">
                                {cat.threshold}
                            </p>
                        </div>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}

// --- Outstanding Card ---
function OutstandingCard({
    students,
    requiredHours,
}: {
    students: TeacherScopedStudent[];
    requiredHours: number;
}) {
    return (
        <Card data-testid="teacher-outstanding-card">
            <CardHeader>
                <div className="flex items-center justify-between">
                    <CardTitle className="flex items-center gap-2 text-emerald-700 dark:text-emerald-400 dark:text-emerald-300">
                        <Star className="h-5 w-5" />
                        Estudiantes Sobresalientes
                    </CardTitle>
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <button className="rounded-full p-1 hover:bg-accent">
                                <Info className="h-4 w-4 text-muted-foreground" />
                            </button>
                        </TooltipTrigger>
                        <TooltipContent className="max-w-xs">
                            <p className="text-sm">
                                Estudiantes que han cumplido o superado la cuota
                                de {requiredHours}h.
                            </p>
                        </TooltipContent>
                    </Tooltip>
                </div>
                <p className="text-sm text-muted-foreground">
                    ≥100% de la cuota
                </p>
            </CardHeader>
            <CardContent>
                {students.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Aún no hay estudiantes sobresalientes
                    </p>
                ) : (
                    <div className="space-y-2">
                        {students.slice(0, 5).map((student) => (
                            <button
                                key={student.id}
                                onClick={() =>
                                    router.visit(
                                        `/admin/users/${student.id}`,
                                    )
                                }
                                className="flex w-full items-center justify-between rounded-lg border bg-card p-3 text-left transition-colors hover:bg-accent"
                            >
                                <div>
                                    <p className="font-medium">
                                        {student.name}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {student.sectionName} (
                                        {student.gradeName})
                                    </p>
                                </div>
                                <div className="text-right">
                                    <p className="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                        {student.hours}h
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {student.percentage.toFixed(1)}%
                                    </p>
                                </div>
                            </button>
                        ))}
                        {students.length > 5 && (
                            <StudentListBadge
                                count={students.length - 5}
                                label="más"
                                students={students
                                    .slice(5)
                                    .map(toBadgeStudent)}
                                variant="success"
                                className="mt-2"
                            />
                        )}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

// --- Top Hours Card ---
function TopHoursCard({
    students,
}: {
    students: TeacherScopedStudent[];
}) {
    return (
        <Card data-testid="teacher-top-hours-card">
            <CardHeader>
                <div className="flex items-center justify-between">
                    <CardTitle className="flex items-center gap-2 text-blue-700 dark:text-blue-400 dark:text-blue-300">
                        <Trophy className="h-5 w-5" />
                        Alumnos con más horas acumuladas
                    </CardTitle>
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <button className="rounded-full p-1 hover:bg-accent">
                                <Info className="h-4 w-4 text-muted-foreground" />
                            </button>
                        </TooltipTrigger>
                        <TooltipContent className="max-w-xs">
                            <p className="text-sm">
                                Listado de estudiantes con horas acumuladas en
                                el año escolar actual, ordenados de mayor a
                                menor.
                            </p>
                        </TooltipContent>
                    </Tooltip>
                </div>
                <p className="text-sm text-muted-foreground">
                    Ranking general por horas
                </p>
            </CardHeader>
            <CardContent>
                {students.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No hay datos de horas acumuladas
                    </p>
                ) : (
                    <div className="space-y-2">
                        {students.slice(0, 5).map((student) => (
                            <button
                                key={student.id}
                                onClick={() =>
                                    router.visit(
                                        `/admin/users/${student.id}`,
                                    )
                                }
                                className="flex w-full items-center justify-between rounded-lg border bg-card p-3 text-left transition-colors hover:bg-accent"
                            >
                                <div>
                                    <p className="font-medium">
                                        {student.name}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {student.sectionName} (
                                        {student.gradeName})
                                    </p>
                                </div>
                                <div className="text-right">
                                    <p className="text-sm font-medium text-blue-600 dark:text-blue-400">
                                        {student.hours}h
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {student.percentage.toFixed(1)}%
                                    </p>
                                </div>
                            </button>
                        ))}
                        {students.length > 5 && (
                            <StudentListBadge
                                count={students.length - 5}
                                label="más"
                                students={students
                                    .slice(5)
                                    .map(toBadgeStudent)}
                                variant="secondary"
                                className="mt-2"
                            />
                        )}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

// --- At Risk Card ---
function AtRiskCard({
    students,
}: {
    students: TeacherScopedStudent[];
}) {
    return (
        <Card data-testid="teacher-at-risk-card">
            <CardHeader>
                <div className="flex items-center justify-between">
                    <CardTitle className="flex items-center gap-2 text-red-700 dark:text-red-400 dark:text-red-300">
                        <AlertTriangle className="h-5 w-5" />
                        Estudiantes que Necesitan Apoyo
                    </CardTitle>
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <button className="rounded-full p-1 hover:bg-accent">
                                <Info className="h-4 w-4 text-muted-foreground" />
                            </button>
                        </TooltipTrigger>
                        <TooltipContent className="max-w-xs">
                            <p className="text-sm">
                                Estudiantes con menos del 40% de la cuota
                                completada. Requieren seguimiento prioritario.
                            </p>
                        </TooltipContent>
                    </Tooltip>
                </div>
                <p className="text-sm text-muted-foreground">
                    &lt;40% de la cuota
                </p>
            </CardHeader>
            <CardContent>
                {students.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No hay estudiantes en riesgo
                    </p>
                ) : (
                    <div className="space-y-2">
                        {students.slice(0, 5).map((student) => (
                            <button
                                key={student.id}
                                onClick={() =>
                                    router.visit(
                                        `/admin/users/${student.id}`,
                                    )
                                }
                                className="flex w-full items-center justify-between rounded-lg border bg-card p-3 text-left transition-colors hover:bg-accent"
                            >
                                <div>
                                    <p className="font-medium">
                                        {student.name}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {student.sectionName} (
                                        {student.gradeName})
                                    </p>
                                </div>
                                <div className="text-right">
                                    <p className="text-sm font-medium text-red-600 dark:text-red-400">
                                        {student.hours}h
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {student.percentage.toFixed(1)}%
                                    </p>
                                </div>
                            </button>
                        ))}
                        {students.length > 5 && (
                            <StudentListBadge
                                count={students.length - 5}
                                label="más"
                                students={students
                                    .slice(5)
                                    .map(toBadgeStudent)}
                                variant="destructive"
                                className="mt-2"
                            />
                        )}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

// --- Upcoming Sessions ---
function UpcomingSessionsCard({
    sessions,
}: {
    sessions: UpcomingSession[];
}) {
    const grouped = useMemo(() => {
        const now = new Date();
        const todayStart = new Date(
            now.getFullYear(),
            now.getMonth(),
            now.getDate(),
        );
        const tomorrowStart = new Date(todayStart);
        tomorrowStart.setDate(tomorrowStart.getDate() + 1);
        const dayAfterTomorrow = new Date(todayStart);
        dayAfterTomorrow.setDate(dayAfterTomorrow.getDate() + 2);

        // End of week = Sunday (weekend)
        const dayOfWeek = now.getDay();
        const daysUntilSunday = dayOfWeek === 0 ? 0 : 7 - dayOfWeek;
        const endOfWeek = new Date(todayStart);
        endOfWeek.setDate(endOfWeek.getDate() + daysUntilSunday);
        endOfWeek.setHours(23, 59, 59, 999);

        const today: UpcomingSession[] = [];
        const tomorrow: UpcomingSession[] = [];
        const thisWeek: UpcomingSession[] = [];

        sessions.forEach((s) => {
            const d = new Date(s.date);
            if (d >= todayStart && d < tomorrowStart) {
                today.push(s);
            } else if (d >= tomorrowStart && d < dayAfterTomorrow) {
                tomorrow.push(s);
            } else if (d >= dayAfterTomorrow && d <= endOfWeek) {
                thisWeek.push(s);
            }
        });

        return { today, tomorrow, thisWeek };
    }, [sessions]);

    const hasSessions = sessions.length > 0;

    return (
        <Card data-testid="teacher-upcoming-sessions">
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Calendar className="h-5 w-5" />
                    Jornadas Programadas
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Info className="h-3 w-3 cursor-help text-muted-foreground" />
                        </TooltipTrigger>
                        <TooltipContent>
                            <p className="text-xs">Jornadas de campo programadas para fechas futuras, agrupadas por día.</p>
                        </TooltipContent>
                    </Tooltip>
                </CardTitle>
            </CardHeader>
            <CardContent>
                {!hasSessions ? (
                    <p className="text-sm text-muted-foreground">
                        No tienes jornadas programadas próximamente
                    </p>
                ) : (
                    <div className="space-y-4">
                        {grouped.today.length > 0 && (
                            <SessionGroup
                                label="Hoy"
                                sessions={grouped.today}
                            />
                        )}
                        {grouped.tomorrow.length > 0 && (
                            <SessionGroup
                                label="Mañana"
                                sessions={grouped.tomorrow}
                            />
                        )}
                        {grouped.thisWeek.length > 0 && (
                            <SessionGroup
                                label="Esta Semana"
                                sessions={grouped.thisWeek}
                            />
                        )}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function SessionGroup({
    label,
    sessions,
}: {
    label: string;
    sessions: UpcomingSession[];
}) {
    return (
        <div>
            <h3 className="mb-2 text-sm font-semibold text-muted-foreground">
                {label}
            </h3>
            <div className="space-y-2">
                {sessions.map((session) => (
                    <button
                        key={session.id}
                        onClick={() =>
                            router.visit(
                                `/admin/field-sessions/${session.id}`,
                            )
                        }
                        className="flex w-full items-center justify-between rounded-lg border bg-card p-3 text-left transition-colors hover:bg-accent"
                    >
                        <div className="flex-1">
                            <p className="font-medium">{session.name}</p>
                            <div className="mt-1 flex flex-wrap gap-2 text-xs text-muted-foreground">
                                <span className="flex items-center gap-1">
                                    <Calendar className="h-3 w-3" />
                                    {new Date(session.date).toLocaleDateString(
                                        'es-VE',
                                        {
                                            year: 'numeric',
                                            month: 'short',
                                            day: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit',
                                        },
                                    )}
                                </span>
                                {session.location && (
                                    <span className="flex items-center gap-1">
                                        <MapPin className="h-3 w-3" />
                                        {session.location}
                                    </span>
                                )}
                                <span>{session.sectionName}</span>
                            </div>
                        </div>
                        <ExternalLink className="h-4 w-4 text-muted-foreground" />
                    </button>
                ))}
            </div>
        </div>
    );
}

// --- Teacher Section Card ---
function TeacherSectionCard({
    section,
}: {
    section: EnhancedSectionProgress;
}) {
    const [studentsExpanded, setStudentsExpanded] = useState(false);

    const avg = section.averageProgress ?? 0;
    const status: TrafficLightStatus =
        avg >= 80 ? 'green' : avg >= 40 ? 'yellow' : 'red';

    const allStudents = section.students;

    return (
        <Card
            id={`teacher-section-${section.sectionId}`}
            data-testid={`teacher-section-${section.sectionId}`}
        >
            <CardHeader>
                <div className="flex items-center justify-between">
                    <CardTitle className="flex items-center gap-2">
                        <Users className="h-4 w-4" />
                        Grado académico: {section.gradeName} | Sección:{' '}
                        {section.sectionName}
                    </CardTitle>
                    <div className="flex items-center gap-2">
                        <TrafficLightBadge status={status} />
                        {studentsExpanded && (
                            <button
                                onClick={() => setStudentsExpanded(false)}
                                className="rounded-md p-1 text-muted-foreground transition-colors hover:bg-accent"
                                aria-label={`Colapsar estudiantes de ${section.sectionName}`}
                            >
                                <ChevronUp className="h-5 w-5" />
                            </button>
                        )}
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                {/* Average progress bar */}
                <div className="mb-3 space-y-1">
                    <div className="flex items-center justify-between text-sm">
                        <span className="flex items-center gap-1 text-muted-foreground">
                            Progreso promedio
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Info className="h-3 w-3 cursor-help" />
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p className="text-xs">Porcentaje promedio de horas acumuladas respecto a la cuota requerida por los estudiantes de esta sección.</p>
                                </TooltipContent>
                            </Tooltip>
                        </span>
                        <span className="font-medium">
                            {avg.toFixed(1)}%
                        </span>
                    </div>
                    <div className="h-2 overflow-hidden rounded-full bg-muted">
                        <div
                            className={`h-full rounded-full ${
                                status === 'green'
                                    ? 'bg-emerald-500'
                                    : status === 'yellow'
                                      ? 'bg-amber-500'
                                      : 'bg-red-500'
                            }`}
                            style={{
                                width: `${Math.min(avg, 100)}%`,
                            }}
                        />
                    </div>
                </div>

                {/* Distribution badges */}
                {section.distribution && (
                    <div className="mb-4 flex flex-wrap gap-2 text-sm">
                        <span className="inline-flex items-center gap-1 rounded-md bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                            <CheckCircle2 className="h-3 w-3" />
                            {section.distribution.onTrack} en meta
                        </span>
                        <span className="inline-flex items-center gap-1 rounded-md bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                            <TrendingUp className="h-3 w-3" />
                            {section.distribution.inProgress} progreso
                        </span>
                        <span className="inline-flex items-center gap-1 rounded-md bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/30 dark:text-red-300">
                            <AlertTriangle className="h-3 w-3" />
                            {section.distribution.atRisk} riesgo
                        </span>
                        <span className="inline-flex items-center gap-1 rounded-md bg-secondary px-2.5 py-0.5 text-xs font-medium text-secondary-foreground">
                            <XCircle className="h-3 w-3" />
                            {section.distribution.zeroHours} sin horas
                        </span>
                    </div>
                )}

                {/* Students grid (visible only when expanded) */}
                {studentsExpanded && allStudents.length > 0 && (
                    <div
                        className="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
                        data-testid={`teacher-section-students-${section.sectionId}`}
                    >
                        {allStudents.map((student) => (
                            <button
                                key={student.studentId}
                                onClick={() =>
                                    router.visit(
                                        `/admin/users/${student.studentId}`,
                                    )
                                }
                                className="text-left transition-transform hover:scale-[1.02] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 rounded-xl"
                                title={`Ver detalle de ${student.studentName}`}
                            >
                                <ProgressCard
                                    title={student.studentName}
                                    currentHours={student.hours.totalHours}
                                    quota={student.hours.quota}
                                    status={student.hours.status}
                                    subtitle={`${student.hours.percentage.toFixed(1)}%`}
                                    showProgress={false}
                                    className="text-sm"
                                />
                            </button>
                        ))}
                    </div>
                )}

                {/* Toggle button */}
                {allStudents.length > 0 && (
                    <button
                        onClick={() => setStudentsExpanded(!studentsExpanded)}
                        className="flex w-full items-center justify-center gap-1.5 rounded-lg border border-dashed p-2 text-center text-sm text-muted-foreground transition-colors hover:bg-accent"
                        data-testid={`teacher-section-toggle-${section.sectionId}`}
                    >
                        {studentsExpanded
                            ? 'Ocultar estudiantes'
                            : `Ver ${allStudents.length} estudiantes`}
                        {studentsExpanded ? (
                            <ChevronUp className="h-4 w-4" />
                        ) : (
                            <ChevronDown className="h-4 w-4" />
                        )}
                    </button>
                )}

                {allStudents.length === 0 && (
                    <p className="text-center text-sm text-muted-foreground">
                        No hay estudiantes en esta sección.
                    </p>
                )}
            </CardContent>
        </Card>
    );
}
