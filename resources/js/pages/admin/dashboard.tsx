import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    BookOpen,
    Calendar,
    CheckCircle2,
    ChevronDown,
    ChevronUp,
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
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { StudentListBadge } from '@/components/ui/student-list-badge';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types/navigation';
import type { EnhancedCategoryDistribution } from '@/types/dashboard';

interface Student {
    id: number;
    name: string;
    hours: number;
    percentage: number;
    section: string;
    grade: string;
    status: string;
}

interface Section {
    id: number;
    name: string;
    grade: string;
    teachers: string[]; // Array of teacher names
    avgPercentage: number;
    studentCount: number;
    distribution: {
        onTrack: number;
        inProgress: number;
        atRisk: number;
    };
    onTrackStudents: Student[];
    inProgressStudents: Student[];
    atRiskStudents: Student[];
}

interface Props {
    activeYear: {
        id: number;
        name: string;
        requiredHours: number;
    } | null;
    totalStudents: number;
    requiredHours: number;
    averageHours: number;
    distribution: {
        onTrack: number;
        inProgress: number;
        atRisk: number;
        noHours: number;
    };
    onTrackStudents: Student[];
    inProgressStudents: Student[];
    atRiskStudents: Student[];
    outstandingStudents: Student[];
    topStudents: Student[];
    studentsWithNoHours: Student[];
    topSections: Section[];
    concerningSections: Section[];
    alerts: {
        zeroHourStudents: number;
        sessionsWithoutAttendance: number;
        sessionsWithoutAttendanceList: Array<{
            id: number;
            name: string;
            date: string;
            location: string;
            teacher: string | null;
        }>;
        sessionsWithAttendanceNoActivities: number;
        sessionsWithAttendanceNoActivitiesList: Array<{
            id: number;
            name: string;
            date: string;
            location: string;
            teacher: string | null;
            attendanceCount: number;
        }>;
        attendancesWithZeroHours: number;
        attendancesWithZeroHoursList: Array<{
            id: number;
            studentId: number;
            studentName: string;
            section: string;
            grade: string;
            sessionId: number;
            sessionName: string;
            sessionDate: string;
            teacher: string | null;
        }>;
    };
    categoryDistribution: EnhancedCategoryDistribution[];
    grades: { id: number; name: string }[];
    sections: { id: number; name: string; grade_id: number }[];
    selectedGradeId: number | null;
    selectedSectionId: number | null;
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
    totalStudents,
    requiredHours,
    averageHours,
    distribution,
    onTrackStudents,
    inProgressStudents,
    atRiskStudents,
    outstandingStudents,
    topStudents,
    studentsWithNoHours,
    topSections,
    concerningSections,
    alerts,
    categoryDistribution,
    grades,
    sections,
    selectedGradeId,
    selectedSectionId,
}: Props) {
    const [showSessionsModal, setShowSessionsModal] = useState(false);
    const [showSessionsNoActivitiesModal, setShowSessionsNoActivitiesModal] = useState(false);
    const [showZeroHoursModal, setShowZeroHoursModal] = useState(false);

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

    const totalCategoryHours = useMemo(
        () => categoryDistribution.reduce((sum, cat) => sum + cat.totalHours, 0),
        [categoryDistribution],
    );

    // Handle grade/section filter changes
    const handleGradeChange = (value: string) => {
        router.get(
            dashboard().url,
            {
                grade_id: value === 'all' ? null : value,
                section_id: null,
            },
            { preserveState: true },
        );
    };

    const handleSectionChange = (value: string) => {
        router.get(
            dashboard().url,
            {
                grade_id: selectedGradeId,
                section_id: value === 'all' ? null : value,
            },
            { preserveState: true },
        );
    };

    // Filter sections by selected grade
    const filteredSections = useMemo(() => {
        if (selectedGradeId) {
            return sections.filter(s => s.grade_id === selectedGradeId);
        }
        return sections;
    }, [sections, selectedGradeId]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Panel de Administración" />

            <TooltipProvider>
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
                        <Link
                            href={`/admin/academic-years/${activeYear.id}`}
                            className="flex items-center gap-2 rounded-lg border bg-card px-4 py-2 text-sm transition-colors hover:bg-accent"
                        >
                            <Calendar className="h-4 w-4 text-muted-foreground" />
                            <span className="font-medium">
                                {activeYear.name}
                            </span>
                            <span className="text-muted-foreground">
                                ({activeYear.requiredHours}h requeridas)
                            </span>
                        </Link>
                    )}
                </div>

                {/* Critical Alerts */}
                {(alerts.zeroHourStudents > 0 ||
                    alerts.sessionsWithoutAttendance > 0 ||
                    alerts.sessionsWithAttendanceNoActivities > 0 ||
                    alerts.attendancesWithZeroHours > 0) && (
                    <Card className="border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950/30">
                        <CardHeader className="pb-2">
                            <CardTitle className="flex items-center gap-2 text-red-800 dark:text-red-200">
                                <AlertTriangle className="h-5 w-5" />
                                Alertas Críticas
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {alerts.zeroHourStudents > 0 && (
                                <div className="flex items-center gap-2">
                                    <XCircle className="h-4 w-4 text-red-600" />
                                    <StudentListBadge
                                        count={alerts.zeroHourStudents}
                                        label="estudiantes sin horas registradas"
                                        students={studentsWithNoHours}
                                        variant="destructive"
                                    />
                                </div>
                            )}
                            {alerts.sessionsWithoutAttendance > 0 && (
                                <button
                                    onClick={() => setShowSessionsModal(true)}
                                    className="flex items-center gap-2 text-sm text-red-700 hover:text-red-800 hover:underline dark:text-red-300 dark:hover:text-red-200"
                                >
                                    ⚠️ {alerts.sessionsWithoutAttendance}{' '}
                                    jornada(s) realizadas sin registro de asistencia
                                </button>
                            )}
                            {alerts.sessionsWithAttendanceNoActivities > 0 && (
                                <button
                                    onClick={() => setShowSessionsNoActivitiesModal(true)}
                                    className="flex items-center gap-2 text-sm text-red-700 hover:text-red-800 hover:underline dark:text-red-300 dark:hover:text-red-200"
                                >
                                    ⚠️ {alerts.sessionsWithAttendanceNoActivities}{' '}
                                    jornada(s) con asistencia pero sin actividades cargadas
                                </button>
                            )}
                            {alerts.attendancesWithZeroHours > 0 && (
                                <button
                                    onClick={() => setShowZeroHoursModal(true)}
                                    className="flex items-center gap-2 text-sm text-red-700 hover:text-red-800 hover:underline dark:text-red-300 dark:hover:text-red-200"
                                >
                                    ⚠️ {alerts.attendancesWithZeroHours}{' '}
                                    asistencia(s) marcadas como presentes con 0 horas
                                </button>
                            )}
                        </CardContent>
                    </Card>
                )}

                {/* Student Distribution */}
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
                                        Distribución de estudiantes según su
                                        progreso hacia la cuota de{' '}
                                        {requiredHours}h requeridas para el año
                                        escolar.
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
                        <div className="grid gap-4 md:grid-cols-3">
                            <div className="rounded-lg border bg-emerald-50 p-4 dark:bg-emerald-950/30">
                                <div className="mb-2 flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <CheckCircle2 className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                                        <span className="font-medium">
                                            En Meta
                                        </span>
                                    </div>
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <button className="rounded-full p-1 hover:bg-emerald-100 dark:hover:bg-emerald-900/50">
                                                <Info className="h-3 w-3 text-emerald-600 dark:text-emerald-400" />
                                            </button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p className="text-sm">
                                                Estudiantes con ≥80% de la cuota
                                                completada. Van bien encaminados
                                                para cumplir.
                                            </p>
                                        </TooltipContent>
                                    </Tooltip>
                                </div>
                                <StudentListBadge
                                    count={distribution.onTrack}
                                    label="estudiantes"
                                    students={onTrackStudents}
                                    variant="success"
                                    className="text-lg"
                                />
                                <p className="mt-1 text-xs text-muted-foreground">
                                    ≥80% de la cuota
                                </p>
                            </div>

                            <div className="rounded-lg border bg-amber-50 p-4 dark:bg-amber-950/30">
                                <div className="mb-2 flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <TrendingUp className="h-5 w-5 text-amber-600 dark:text-amber-400" />
                                        <span className="font-medium">
                                            En Progreso
                                        </span>
                                    </div>
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <button className="rounded-full p-1 hover:bg-amber-100 dark:hover:bg-amber-900/50">
                                                <Info className="h-3 w-3 text-amber-600 dark:text-amber-400" />
                                            </button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p className="text-sm">
                                                Estudiantes con 40-79% de la
                                                cuota. Avanzan pero necesitan
                                                mantener el ritmo.
                                            </p>
                                        </TooltipContent>
                                    </Tooltip>
                                </div>
                                <StudentListBadge
                                    count={distribution.inProgress}
                                    label="estudiantes"
                                    students={inProgressStudents}
                                    variant="warning"
                                    className="text-lg"
                                />
                                <p className="mt-1 text-xs text-muted-foreground">
                                    40-79% de la cuota
                                </p>
                            </div>

                            <div className="rounded-lg border bg-red-50 p-4 dark:bg-red-950/30">
                                <div className="mb-2 flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <AlertTriangle className="h-5 w-5 text-red-600 dark:text-red-400" />
                                        <span className="font-medium">
                                            En Riesgo
                                        </span>
                                    </div>
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <button className="rounded-full p-1 hover:bg-red-100 dark:hover:bg-red-900/50">
                                                <Info className="h-3 w-3 text-red-600 dark:text-red-400" />
                                            </button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p className="text-sm">
                                                Estudiantes con &lt;40% de la
                                                cuota. Difícilmente cumplirán sin
                                                intervención.
                                            </p>
                                        </TooltipContent>
                                    </Tooltip>
                                </div>
                                <StudentListBadge
                                    count={distribution.atRisk}
                                    label="estudiantes"
                                    students={atRiskStudents}
                                    variant="destructive"
                                    className="text-lg"
                                />
                                <p className="mt-1 text-xs text-muted-foreground">
                                    &lt;40% de la cuota
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Rankings and Alerts */}
                <div className="grid gap-4 xl:grid-cols-3 lg:grid-cols-2">
                    {/* Outstanding Students */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle className="flex items-center gap-2 text-emerald-700 dark:text-emerald-400">
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
                                            Estudiantes que han cumplido o superado la cuota de {requiredHours}h. Estos estudiantes han demostrado un compromiso excepcional con la asignatura.
                                        </p>
                                    </TooltipContent>
                                </Tooltip>
                            </div>
                            <p className="text-sm text-muted-foreground">
                                ≥100% de la cuota
                            </p>
                        </CardHeader>
                        <CardContent>
                            {outstandingStudents.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Aún no hay estudiantes sobresalientes
                                </p>
                            ) : (
                                <div className="space-y-2">
                                    {outstandingStudents
                                        .slice(0, 5)
                                        .map((student) => (
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
                                                        {student.section} (
                                                        {student.grade})
                                                    </p>
                                                </div>
                                                <div className="text-right">
                                                    <p className="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                                        {student.hours}h
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
                                    {outstandingStudents.length > 5 && (
                                        <StudentListBadge
                                            count={
                                                outstandingStudents.length - 5
                                            }
                                            label="más"
                                            students={outstandingStudents.slice(
                                                5,
                                            )}
                                            variant="success"
                                            className="mt-2"
                                        />
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Top Accumulated Hours Students */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle className="flex items-center gap-2 text-blue-700 dark:text-blue-400">
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
                                            Listado general de estudiantes con horas acumuladas en el año escolar actual, ordenados de mayor a menor productividad.
                                        </p>
                                    </TooltipContent>
                                </Tooltip>
                            </div>
                            <p className="text-sm text-muted-foreground">
                                Ranking general por horas
                            </p>
                        </CardHeader>
                        <CardContent>
                            {topStudents.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No hay datos de horas acumuladas
                                </p>
                            ) : (
                                <div className="space-y-2">
                                    {topStudents.slice(0, 5).map((student) => (
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
                                                    {student.section} (
                                                    {student.grade})
                                                </p>
                                            </div>
                                            <div className="text-right">
                                                <p className="text-sm font-medium text-blue-600 dark:text-blue-400">
                                                    {student.hours}h
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
                                    {topStudents.length > 5 && (
                                        <StudentListBadge
                                            count={topStudents.length - 5}
                                            label="más"
                                            students={topStudents.slice(5)}
                                            variant="secondary"
                                            className="mt-2"
                                        />
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* At Risk Students */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle className="flex items-center gap-2 text-red-700 dark:text-red-400">
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
                                            Estudiantes con menos del 40% de la cuota completada. Difícilmente cumplirán sin intervención inmediata. Requieren seguimiento prioritario.
                                        </p>
                                    </TooltipContent>
                                </Tooltip>
                            </div>
                            <p className="text-sm text-muted-foreground">
                                &lt;40% de la cuota
                            </p>
                        </CardHeader>
                        <CardContent>
                            {atRiskStudents.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No hay estudiantes en riesgo
                                </p>
                            ) : (
                                <div className="space-y-2">
                                    {atRiskStudents
                                        .slice(0, 5)
                                        .map((student) => (
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
                                                        {student.section} (
                                                        {student.grade})
                                                    </p>
                                                </div>
                                                <div className="text-right">
                                                    <p className="text-sm font-medium text-red-600 dark:text-red-400">
                                                        {student.hours}h
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
                                    {atRiskStudents.length > 5 && (
                                        <StudentListBadge
                                            count={atRiskStudents.length - 5}
                                            label="más"
                                            students={atRiskStudents.slice(5)}
                                            variant="destructive"
                                            className="mt-2"
                                        />
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Category Distribution */}
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
                                        <p className="text-xs">Horas totales acumuladas por <strong>todos</strong> los estudiantes en cada tipo de actividad durante las jornadas de campo. No es un promedio por estudiante, es la suma total.</p>
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
                                                {cat.count > 0 && (
                                                    <span className="inline-flex items-center gap-1 rounded-md border bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-950/30 dark:border-purple-800 dark:text-purple-300 min-w-[110px] justify-center">
                                                        {cat.count} participaciones
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Info className="h-3 w-3 cursor-help text-purple-500 dark:text-purple-400" />
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p className="text-xs max-w-56">Cantidad de jornadas donde se registró esta actividad. Cuenta asistencias, no estudiantes. Un mismo alumno participando en 3 jornadas distintas cuenta como 3.</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </span>
                                                )}
                                                <span className="inline-flex items-center gap-1 rounded-md border bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-950/30 dark:border-blue-800 dark:text-blue-300 min-w-[110px] justify-center">
                                                    {cat.totalHours.toFixed(1)} horas totales
                                                    <Tooltip>
                                                        <TooltipTrigger asChild>
                                                            <Info className="h-3 w-3 cursor-help text-blue-500 dark:text-blue-400" />
                                                        </TooltipTrigger>
                                                        <TooltipContent>
                                                            <p className="text-xs max-w-56">Suma de horas de TODOS los estudiantes en esta categoría. Ejemplo: si 3 alumnos acumulan 3h cada uno en esta categoría, se muestran 9h.</p>
                                                        </TooltipContent>
                                                    </Tooltip>
                                                </span>
                                                {totalCategoryHours > 0 && (
                                                    <span className="inline-flex items-center gap-1 rounded-md border bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-950/30 dark:border-emerald-800 dark:text-emerald-300 min-w-[110px] justify-center">
                                                        {(cat.totalHours / totalCategoryHours * 100).toFixed(1)}% del total
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Info className="h-3 w-3 cursor-help text-emerald-500 dark:text-emerald-400" />
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p className="text-xs">Porcentaje que representa esta categoría del total de horas en todas las categorías.</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </span>
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

                {/* Sections Overview */}
                <Card>
                    <CardHeader>
                        <CardTitle>Panorama por Secciones</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        {/* Top Sections */}
                        {topSections.length > 0 && (
                            <div>
                                <h3 className="mb-3 flex items-center gap-2 text-sm font-semibold text-emerald-700 dark:text-emerald-400">
                                    <Star className="h-4 w-4" />
                                    Secciones con Mejor Rendimiento
                                </h3>
                                <p className="mb-3 text-xs text-muted-foreground">
                                    Top 3 secciones ordenadas por promedio de cumplimiento
                                </p>
                                <div className="space-y-3">
                                    {topSections.map((section) => (
                                        <SectionCard
                                            key={section.id}
                                            section={section}
                                            variant="success"
                                        />
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Concerning Sections */}
                        {concerningSections.length > 0 && (
                            <div>
                                <h3 className="mb-3 flex items-center gap-2 text-sm font-semibold text-red-700 dark:text-red-400">
                                    <AlertTriangle className="h-4 w-4" />
                                    Secciones que Requieren Más Atención
                                </h3>
                                <p className="mb-3 text-xs text-muted-foreground">
                                    Secciones con menor promedio de cumplimiento (ordenadas de menor a mayor)
                                </p>
                                <div className="space-y-3">
                                    {concerningSections.map((section) => (
                                        <SectionCard
                                            key={section.id}
                                            section={section}
                                            variant="warning"
                                        />
                                    ))}
                                </div>
                            </div>
                        )}

                        {topSections.length === 0 &&
                            concerningSections.length === 0 && (
                                <p className="text-sm text-muted-foreground">
                                    No hay secciones registradas
                                </p>
                            )}
                    </CardContent>
                </Card>
            </div>

            {/* Modal for sessions without attendance */}
            <Dialog open={showSessionsModal} onOpenChange={setShowSessionsModal}>
                <DialogContent className="max-h-[80vh] max-w-2xl overflow-hidden">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-red-700 dark:text-red-400">
                            <AlertTriangle className="h-5 w-5" />
                            Jornadas Realizadas sin Registro de Asistencia
                        </DialogTitle>
                        <p className="text-sm text-muted-foreground">
                            {alerts.sessionsWithoutAttendance} jornada(s) que necesitan registro de asistencia
                        </p>
                    </DialogHeader>

                    <div className="max-h-[60vh] overflow-y-auto">
                        <div className="space-y-2">
                            {alerts.sessionsWithoutAttendanceList.map((session) => (
                                <button
                                    key={session.id}
                                    onClick={() => {
                                        router.visit(`/admin/field-sessions/${session.id}`);
                                        setShowSessionsModal(false);
                                    }}
                                    className="flex w-full items-center justify-between rounded-lg border bg-card p-3 text-left transition-colors hover:bg-accent"
                                >
                                    <div className="flex-1">
                                        <p className="font-medium">{session.name}</p>
                                        <div className="mt-1 flex flex-wrap gap-2 text-xs text-muted-foreground">
                                            <span className="flex items-center gap-1">
                                                <Calendar className="h-3 w-3" />
                                                {new Date(session.date).toLocaleDateString('es-VE', {
                                                    year: 'numeric',
                                                    month: 'short',
                                                    day: 'numeric',
                                                })}
                                            </span>
                                            {session.location && (
                                                <span className="flex items-center gap-1">
                                                    <MapPin className="h-3 w-3" />
                                                    {session.location}
                                                </span>
                                            )}
                                            {session.teacher && (
                                                <span>👨‍🏫 {session.teacher}</span>
                                            )}
                                        </div>
                                    </div>
                                    <ExternalLink className="h-4 w-4 text-muted-foreground" />
                                </button>
                            ))}
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

            {/* Modal for sessions with attendance but no activities */}
            <Dialog open={showSessionsNoActivitiesModal} onOpenChange={setShowSessionsNoActivitiesModal}>
                <DialogContent className="max-h-[80vh] max-w-2xl overflow-hidden">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-red-700 dark:text-red-400">
                            <AlertTriangle className="h-5 w-5" />
                            Jornadas con Asistencia pero sin Actividades
                        </DialogTitle>
                        <p className="text-sm text-muted-foreground">
                            {alerts.sessionsWithAttendanceNoActivities} jornada(s) donde los estudiantes asistieron pero no se cargaron actividades
                        </p>
                    </DialogHeader>

                    <div className="max-h-[60vh] overflow-y-auto">
                        <div className="space-y-2">
                            {alerts.sessionsWithAttendanceNoActivitiesList.map((session) => (
                                <button
                                    key={session.id}
                                    onClick={() => {
                                        router.visit(`/admin/field-sessions/${session.id}/attendance`);
                                        setShowSessionsNoActivitiesModal(false);
                                    }}
                                    className="flex w-full items-center justify-between rounded-lg border bg-card p-3 text-left transition-colors hover:bg-accent"
                                >
                                    <div className="flex-1">
                                        <p className="font-medium">{session.name}</p>
                                        <div className="mt-1 flex flex-wrap gap-2 text-xs text-muted-foreground">
                                            <span className="flex items-center gap-1">
                                                <Calendar className="h-3 w-3" />
                                                {new Date(session.date).toLocaleDateString('es-VE', {
                                                    year: 'numeric',
                                                    month: 'short',
                                                    day: 'numeric',
                                                })}
                                            </span>
                                            {session.location && (
                                                <span className="flex items-center gap-1">
                                                    <MapPin className="h-3 w-3" />
                                                    {session.location}
                                                </span>
                                            )}
                                            {session.teacher && (
                                                <span>👨‍🏫 {session.teacher}</span>
                                            )}
                                            <span className="flex items-center gap-1 text-red-600 dark:text-red-400">
                                                <Users className="h-3 w-3" />
                                                {session.attendanceCount} asistencias sin actividades
                                            </span>
                                        </div>
                                    </div>
                                    <ExternalLink className="h-4 w-4 text-muted-foreground" />
                                </button>
                            ))}
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

            {/* Modal for attendances with zero hours */}
            <Dialog open={showZeroHoursModal} onOpenChange={setShowZeroHoursModal}>
                <DialogContent className="max-h-[80vh] max-w-2xl overflow-hidden">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-red-700 dark:text-red-400">
                            <AlertTriangle className="h-5 w-5" />
                            Asistencias Marcadas como Presentes con 0 Horas
                        </DialogTitle>
                        <p className="text-sm text-muted-foreground">
                            {alerts.attendancesWithZeroHours} estudiante(s) marcados como presentes pero sin horas acumuladas
                        </p>
                    </DialogHeader>

                    <div className="max-h-[60vh] overflow-y-auto">
                        <div className="space-y-2">
                            {alerts.attendancesWithZeroHoursList.map((attendance) => (
                                <button
                                    key={attendance.id}
                                    onClick={() => {
                                        router.visit(`/admin/field-sessions/${attendance.sessionId}/attendance`);
                                        setShowZeroHoursModal(false);
                                    }}
                                    className="flex w-full items-center justify-between rounded-lg border bg-card p-3 text-left transition-colors hover:bg-accent"
                                >
                                    <div className="flex-1">
                                        <p className="font-medium">{attendance.studentName}</p>
                                        <p className="text-xs text-muted-foreground">
                                            {attendance.section} ({attendance.grade})
                                        </p>
                                        <div className="mt-1 flex flex-wrap gap-2 text-xs text-muted-foreground">
                                            <span className="font-medium text-foreground">
                                                {attendance.sessionName}
                                            </span>
                                            <span className="flex items-center gap-1">
                                                <Calendar className="h-3 w-3" />
                                                {new Date(attendance.sessionDate).toLocaleDateString('es-VE', {
                                                    year: 'numeric',
                                                    month: 'short',
                                                    day: 'numeric',
                                                })}
                                            </span>
                                            {attendance.teacher && (
                                                <span>👨‍🏫 {attendance.teacher}</span>
                                            )}
                                        </div>
                                    </div>
                                    <ExternalLink className="h-4 w-4 text-muted-foreground" />
                                </button>
                            ))}
                        </div>
                    </div>
                </DialogContent>
            </Dialog>
            </TooltipProvider>
        </AppLayout>
    );
}

function SectionCard({
    section,
    variant,
}: {
    section: Section;
    variant: 'success' | 'warning';
}) {
    const [showAllStudents, setShowAllStudents] = useState(false);
    
    // Combine all students from the section
    const allStudents = [
        ...section.onTrackStudents,
        ...section.inProgressStudents,
        ...section.atRiskStudents,
    ];

    return (
        <>
            <div className="rounded-lg border bg-card p-4">
                <div className="mb-2 flex items-start justify-between">
                     <div>
                         <h4 className="font-medium">
                             <button
                                 onClick={() => {
                                     router.visit(`/admin/sections/${section.id}`);
                                 }}
                                 className="text-left hover:underline"
                             >
                                 {section.grade} {section.name}
                             </button>
                         </h4>
                         {section.teachers.length > 0 && (
                             <p className="text-xs text-muted-foreground flex flex-wrap gap-2">
                                 👨‍🏫 {section.teachers.map((teacher, idx) => (
                                     <>
                                         <button
                                             key={teacher.id}
                                             onClick={() => {
                                                 router.visit(`/admin/users/${teacher.id}`);
                                             }}
                                             className="hover:underline text-muted-foreground"
                                         >
                                             {teacher.name}
                                         </button>
                                         {idx < section.teachers.length - 1 && <span> | </span>}
                                     </>
                                 ))}
                             </p>
                         )}
                     </div>
                    <div className="text-right">
                        <div className="flex items-center gap-2">
                            <p
                                className={`text-lg font-bold ${
                                    variant === 'success'
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-red-600 dark:text-red-400'
                                }`}
                            >
                                {section.avgPercentage.toFixed(1)}%
                            </p>
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <button className="rounded-full p-1 hover:bg-accent">
                                        <Info className="h-3 w-3 text-muted-foreground" />
                                    </button>
                                </TooltipTrigger>
                                <TooltipContent className="max-w-xs">
                                    <p className="text-sm">
                                        Promedio del porcentaje de cumplimiento de todos los estudiantes de esta sección. Se calcula sumando el porcentaje individual de cada estudiante y dividiendo entre el total de estudiantes.
                                    </p>
                                </TooltipContent>
                            </Tooltip>
                        </div>
                        <button
                            onClick={() => setShowAllStudents(true)}
                            className="text-xs text-muted-foreground hover:text-foreground hover:underline"
                        >
                            {section.studentCount} estudiantes
                        </button>
                    </div>
                </div>

            {/* Progress bar */}
            <div className="mb-3 h-2 overflow-hidden rounded-full bg-muted">
                <div
                    className={`h-full ${
                        variant === 'success' ? 'bg-emerald-500' : 'bg-red-500'
                    }`}
                    style={{ width: `${section.avgPercentage}%` }}
                />
            </div>

            {/* Distribution badges */}
            <div className="flex flex-wrap gap-2 text-sm">
                <StudentListBadge
                    count={section.distribution.onTrack}
                    label="en meta"
                    students={section.onTrackStudents}
                    variant="success"
                    icon={<CheckCircle2 className="h-3 w-3" />}
                />
                <StudentListBadge
                    count={section.distribution.inProgress}
                    label="progreso"
                    students={section.inProgressStudents}
                    variant="warning"
                    icon={<TrendingUp className="h-3 w-3" />}
                />
                <StudentListBadge
                    count={section.distribution.atRisk}
                    label="riesgo"
                    students={section.atRiskStudents}
                    variant="destructive"
                    icon={<AlertTriangle className="h-3 w-3" />}
                />
            </div>
        </div>

        {/* Modal for all students in section */}
        <Dialog open={showAllStudents} onOpenChange={setShowAllStudents}>
            <DialogContent className="max-h-[80vh] max-w-2xl overflow-hidden">
                <DialogHeader>
                    <DialogTitle>
                        {section.grade} {section.name} - Todos los Estudiantes
                    </DialogTitle>
                    <p className="text-sm text-muted-foreground">
                        {section.teachers.length > 0 && `👨‍🏫 ${section.teachers.join(', ')} • `}
                        Promedio: {section.avgPercentage.toFixed(1)}%
                    </p>
                </DialogHeader>

                <div className="max-h-[60vh] overflow-y-auto">
                    <div className="space-y-2">
                        {allStudents.map((student) => (
                            <button
                                key={student.id}
                                onClick={() => {
                                    router.visit(`/admin/users/${student.id}`);
                                    setShowAllStudents(false);
                                }}
                                className="flex w-full items-center justify-between rounded-lg border bg-card p-3 text-left transition-colors hover:bg-accent"
                            >
                                <div className="flex-1">
                                    <p className="font-medium">{student.name}</p>
                                </div>
                                <div className="flex items-center gap-3">
                                    <div className="text-right">
                                        <p className="text-sm font-medium">
                                            {student.hours}h
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {student.percentage.toFixed(1)}%
                                        </p>
                                    </div>
                                    <ExternalLink className="h-4 w-4 text-muted-foreground" />
                                </div>
                            </button>
                        ))}
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </>
    );
}
