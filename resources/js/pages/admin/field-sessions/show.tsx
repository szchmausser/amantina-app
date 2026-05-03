import { Head, Link, usePage, router } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import {
    ArrowLeft,
    ArrowRight,
    Clock,
    MapPin,
    Tag,
    Trash2,
    UserCheck,
    UserX,
    Plus,
    ListChecks,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import {
    DataTable,
    DataTableHead,
    DataTableTH,
    DataTableBody,
    DataTableTR,
    DataTableTD,
    type PaginationInfo,
} from '@/components/ui/data-table';
import { TableFilters } from '@/components/ui/table-filters';
import type { BreadcrumbItem, SharedData } from '@/types';
import { useDebounce } from '@/hooks/use-debounce';

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

interface AttendanceRow {
    id: number;
    user_id: number;
    student_name: string;
    student_cedula: string;
    grade_name: string;
    grade_id: number | null;
    section_name: string;
    attended: boolean;
    total_hours: number;
    activities: {
        id: number;
        activity_category: string | null;
        hours: number;
    }[];
    notes: string | null;
    created_at: string;
}

interface Grade {
    id: number;
    name: string;
    sections: { id: number; name: string }[];
}

interface Props {
    fieldSession: FieldSession;
    attendances: {
        data: AttendanceRow[];
        links: { url: string | null; label: string; active: boolean }[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    grades: Grade[];
    filters: {
        search?: string;
        grade?: string;
        section?: string;
    };
    activityCategories: { id: number; name: string }[];
}

// Activity item for the modal
interface ActivityItem {
    id: string;
    activity_category_id: number;
    activity_category_name: string;
    hours: number;
    notes: string;
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

export default function FieldSessionShow({
    fieldSession,
    attendances,
    grades,
    filters,
    activityCategories,
}: Props) {
    const { auth } = usePage<SharedData>().props;

    // Filter state
    const [search, setSearch] = useState(filters.search || '');
    const [selectedGradeId, setSelectedGradeId] = useState(
        filters.grade || 'all',
    );
    const [selectedSectionId, setSelectedSectionId] = useState(
        filters.section || 'all',
    );
    const [perPage, setPerPage] = useState(attendances.per_page || 10);

    // Modal state for global hours assignment
    const [globalHoursModal, setGlobalHoursModal] = useState<{
        open: boolean;
        student: AttendanceRow | null;
    }>({ open: false, student: null });
    const [globalHours, setGlobalHours] = useState<{
        hours: number;
        activity_category_id: number;
    }>({ hours: 0, activity_category_id: 0 });
    const [isSubmittingGlobal, setIsSubmittingGlobal] = useState(false);

    // Modal state for activities assignment
    const [activitiesModal, setActivitiesModal] = useState<{
        open: boolean;
        student: AttendanceRow | null;
    }>({ open: false, student: null });
    const [activities, setActivities] = useState<ActivityItem[]>([]);
    const [isSubmittingActivities, setIsSubmittingActivities] = useState(false);

    // AlertDialog state for delete confirmation
    const [confirmDialogOpen, setConfirmDialogOpen] = useState(false);

    // Ref para evitar ejecución en primera renderización
    const isFirstRender = useRef(true);

    // Debounce del search
    const debouncedSearch = useDebounce(search, 300);

    // Get sections for selected grade
    const selectedGrade = grades.find(
        (g) => g.id.toString() === selectedGradeId,
    );
    const sections = selectedGrade?.sections || [];

    // URL base para las peticiones
    const baseUrl = `/admin/field-sessions/${fieldSession.id}`;

    // Efecto para aplicar filtros
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }

        router.get(
            baseUrl,
            {
                search: debouncedSearch || undefined,
                grade: selectedGradeId === 'all' ? undefined : selectedGradeId,
                section:
                    selectedSectionId === 'all' ? undefined : selectedSectionId,
                per_page: perPage,
            },
            { preserveState: true, preserveScroll: true },
        );
    }, [debouncedSearch, selectedGradeId, selectedSectionId, perPage]);

    const handleGradeChange = (value: string) => {
        setSelectedGradeId(value);
        setSelectedSectionId('all'); // Reset section when grade changes
        router.get(
            baseUrl,
            {
                search: search || undefined,
                grade: value === 'all' ? undefined : value,
                per_page: perPage,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleSectionChange = (value: string) => {
        setSelectedSectionId(value);
        router.get(
            baseUrl,
            {
                search: search || undefined,
                grade: selectedGradeId === 'all' ? undefined : selectedGradeId,
                section: value === 'all' ? undefined : value,
                per_page: perPage,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleClearFilters = () => {
        setSearch('');
        setSelectedGradeId('all');
        setSelectedSectionId('all');
        setPerPage(10);
        router.get(
            baseUrl,
            { per_page: 10 },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleDelete = () => {
        setConfirmDialogOpen(true);
    };

    const confirmDelete = () => {
        router.delete(`/admin/field-sessions/${fieldSession.id}`);
        setConfirmDialogOpen(false);
    };

    // Open modal for global hours assignment
    const openGlobalHoursModal = (student: AttendanceRow) => {
        setGlobalHoursModal({ open: true, student });
        setGlobalHours({
            hours:
                student.total_hours > 0
                    ? student.total_hours
                    : parseFloat(fieldSession.base_hours),
            activity_category_id: activityCategories[0]?.id || 0,
        });
    };

    // Submit global hours
    const submitGlobalHours = () => {
        if (!globalHoursModal.student || globalHours.hours <= 0) return;

        setIsSubmittingGlobal(true);
        router.post(
            `/admin/field-sessions/${fieldSession.id}/attendance/quick-assign-hours`,
            {
                user_id: globalHoursModal.student.user_id,
                hours: globalHours.hours,
                activity_category_id: globalHours.activity_category_id,
            },
            {
                onSuccess: () => {
                    setGlobalHoursModal({ open: false, student: null });
                    // Reload the page to show updated data
                    router.get(
                        baseUrl,
                        {
                            search: search || undefined,
                            grade:
                                selectedGradeId === 'all'
                                    ? undefined
                                    : selectedGradeId,
                            section:
                                selectedSectionId === 'all'
                                    ? undefined
                                    : selectedSectionId,
                            per_page: perPage,
                        },
                        { preserveState: true, preserveScroll: true },
                    );
                },
                onFinish: () => setIsSubmittingGlobal(false),
            },
        );
    };

    // Open modal for activities assignment
    const openActivitiesModal = (student: AttendanceRow) => {
        setActivitiesModal({ open: true, student });
        // Initialize with existing activities
        const existingActivities: ActivityItem[] = student.activities.map(
            (act, index) => ({
                id: `existing-${index}`,
                activity_category_id: 0,
                activity_category_name: act.activity_category || '',
                hours: act.hours,
                notes: '',
            }),
        );
        setActivities(existingActivities.length > 0 ? existingActivities : []);
    };

    // Add new activity row
    const addActivityRow = () => {
        setActivities([
            ...activities,
            {
                id: `new-${Date.now()}`,
                activity_category_id: activityCategories[0]?.id || 0,
                activity_category_name: activityCategories[0]?.name || '',
                hours: 0,
                notes: '',
            },
        ]);
    };

    // Update activity row
    const updateActivity = (
        id: string,
        field: keyof ActivityItem,
        value: string | number,
    ) => {
        setActivities(
            activities.map((act) => {
                if (act.id !== id) return act;
                if (field === 'activity_category_id') {
                    const category = activityCategories.find(
                        (c) => c.id === value,
                    );
                    return {
                        ...act,
                        activity_category_id: value as number,
                        activity_category_name: category?.name || '',
                    };
                }
                return { ...act, [field]: value };
            }),
        );
    };

    // Remove activity row
    const removeActivity = (id: string) => {
        setActivities(activities.filter((act) => act.id !== id));
    };

    // Submit activities
    const submitActivities = () => {
        if (!activitiesModal.student || activities.length === 0) return;

        setIsSubmittingActivities(true);
        const data = activities.map((act) => ({
            user_id: activitiesModal.student!.user_id,
            activity_category_id: act.activity_category_id,
            hours: act.hours,
            notes: act.notes,
        }));

        router.post(
            `/admin/field-sessions/${fieldSession.id}/attendance/bulk-assign-hours`,
            { data },
            {
                onSuccess: () => {
                    setActivitiesModal({ open: false, student: null });
                    setActivities([]);
                    // Reload the page to show updated data
                    router.get(
                        baseUrl,
                        {
                            search: search || undefined,
                            grade:
                                selectedGradeId === 'all'
                                    ? undefined
                                    : selectedGradeId,
                            section:
                                selectedSectionId === 'all'
                                    ? undefined
                                    : selectedSectionId,
                            per_page: perPage,
                        },
                        { preserveState: true, preserveScroll: true },
                    );
                },
                onFinish: () => setIsSubmittingActivities(false),
            },
        );
    };

    const hasFilters = Boolean(
        search || selectedGradeId !== 'all' || selectedSectionId !== 'all',
    );

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Jornadas de Campo', href: '/admin/field-sessions' },
        { title: fieldSession.name, href: '#' },
    ];

    // Preparar paginación
    const pagination: PaginationInfo | undefined =
        attendances.last_page > 1
            ? {
                  links: attendances.links,
                  total: attendances.total,
                  current_page: attendances.current_page,
                  last_page: attendances.last_page,
              }
            : undefined;

    // Columnas de la tabla
    const tableColumns = (
        <>
            <DataTableHead>
                <DataTableTH className="w-16">#</DataTableTH>
                <DataTableTH className="w-[300px]">Estudiante</DataTableTH>
                <DataTableTH>Grado / Sección</DataTableTH>
                <DataTableTH>Estado</DataTableTH>
                <DataTableTH className="text-center">Horas</DataTableTH>
                <DataTableTH className="text-center">Actividades</DataTableTH>
                <DataTableTH>Registrado</DataTableTH>
                <DataTableTH className="w-40 text-right">Acciones</DataTableTH>
            </DataTableHead>
            <DataTableBody>
                {attendances.data.map((row, index) => (
                    <DataTableTR key={row.id}>
                        <DataTableTD className="font-mono text-xs text-neutral-400">
                            {(attendances.current_page - 1) * perPage +
                                index +
                                1}
                        </DataTableTD>
                        <DataTableTD>
                            <div>
                                <p className="font-medium">
                                    {row.student_name}
                                </p>
                                <p className="text-xs text-neutral-500">
                                    {row.student_cedula}
                                </p>
                            </div>
                        </DataTableTD>
                        <DataTableTD>
                            <div className="text-sm">
                                <span>{row.grade_name} / </span>
                                <span className="font-medium">
                                    Sección {row.section_name}
                                </span>
                            </div>
                        </DataTableTD>
                        <DataTableTD>
                            {row.attended ? (
                                <Badge className="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                    <UserCheck className="mr-1 h-3 w-3" />
                                    Asistió
                                </Badge>
                            ) : (
                                <Badge className="bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                    <UserX className="mr-1 h-3 w-3" />
                                    Ausente
                                </Badge>
                            )}
                        </DataTableTD>
                        <DataTableTD className="text-center">
                            <span
                                className={
                                    row.total_hours > 0
                                        ? 'font-mono font-medium text-green-600'
                                        : 'font-mono text-neutral-400'
                                }
                            >
                                {row.total_hours > 0
                                    ? `${row.total_hours}h`
                                    : '-'}
                            </span>
                        </DataTableTD>
                        <DataTableTD className="text-center">
                            <div className="flex flex-wrap justify-center gap-1">
                                {row.activities.length > 0 ? (
                                    row.activities.map((act) => (
                                        <Badge
                                            key={act.id}
                                            variant="outline"
                                            className="text-xs"
                                        >
                                            {act.hours}h{' '}
                                            {act.activity_category || ''}
                                        </Badge>
                                    ))
                                ) : (
                                    <span className="text-xs text-neutral-400">
                                        Sin detalle
                                    </span>
                                )}
                            </div>
                        </DataTableTD>
                        <DataTableTD className="text-xs text-neutral-500">
                            {row.created_at}
                        </DataTableTD>
                        <DataTableTD className="text-right">
                            <div className="flex items-center justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-8 w-8 text-green-600 hover:bg-green-50 hover:text-green-700"
                                    title="Registrar horas globales"
                                    onClick={() => openGlobalHoursModal(row)}
                                >
                                    <Clock className="h-4 w-4" />
                                    <span className="sr-only">
                                        Horas globales
                                    </span>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-8 w-8 text-blue-600 hover:bg-blue-50 hover:text-blue-700"
                                    title="Detalle de actividades"
                                    onClick={() => openActivitiesModal(row)}
                                >
                                    <ListChecks className="h-4 w-4" />
                                    <span className="sr-only">Actividades</span>
                                </Button>
                            </div>
                        </DataTableTD>
                    </DataTableTR>
                ))}
            </DataTableBody>
        </>
    );

    // Selectores de filtro
    const gradeFilterSelect = (
        <Select value={selectedGradeId} onValueChange={handleGradeChange}>
            <SelectTrigger className="h-10 w-full sm:w-44">
                <SelectValue placeholder="Filtrar por grado" />
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
    );

    const sectionFilterSelect = (
        <Select
            value={selectedSectionId}
            onValueChange={handleSectionChange}
            disabled={selectedGradeId === 'all'}
        >
            <SelectTrigger className="h-10 w-full sm:w-36">
                <SelectValue placeholder="Sección" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="all">Todas</SelectItem>
                {sections.map((section) => (
                    <SelectItem key={section.id} value={section.id.toString()}>
                        Sección {section.name}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );

    // Botón registrar asistencia
    const registerButton = (
        <Button asChild>
            <Link href={`${baseUrl}/attendance`}>
                <ArrowRight className="mr-2 h-4 w-4" />
                Registrar Asistencia
            </Link>
        </Button>
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={fieldSession.name} />

            <div className="flex flex-col gap-6 p-4 lg:p-8">
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
                            <Link href={`${baseUrl}/attendance`}>
                                Registrar Asistencia
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={`${baseUrl}/edit`}>Editar</Link>
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
                        className={statusColors[fieldSession.status.name] || ''}
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
                                <span className="text-neutral-500">Inicio</span>
                                <span className="font-medium text-neutral-900 dark:text-neutral-100">
                                    {new Date(
                                        fieldSession.start_datetime,
                                    ).toLocaleString('es-ES')}
                                </span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-neutral-500">Fin</span>
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

                {/* Attendance Section */}
                <div>
                    {/* Título de la sección */}
                    <div className="mb-4">
                        <h2 className="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                            Asistencia Registrada
                        </h2>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                            Lista de estudiantes registrados en esta jornada
                        </p>
                    </div>

                    {/* Filtros usando componente reutilizable */}
                    <TableFilters
                        searchValue={search}
                        onSearchChange={setSearch}
                        searchPlaceholder="Buscar por nombre o cédula..."
                        filterSelect={
                            <div className="flex gap-2">
                                {gradeFilterSelect}
                                {sectionFilterSelect}
                            </div>
                        }
                        hasFilters={hasFilters}
                        onClearFilters={handleClearFilters}
                        createButton={registerButton}
                    />

                    {/* Tabla */}
                    <DataTable
                        className="mt-4"
                        data={attendances.data}
                        columns={tableColumns}
                        pagination={pagination}
                        onPageChange={(page, url) => {
                            router.get(
                                url,
                                {
                                    search: search || undefined,
                                    grade:
                                        selectedGradeId === 'all'
                                            ? undefined
                                            : selectedGradeId,
                                    section:
                                        selectedSectionId === 'all'
                                            ? undefined
                                            : selectedSectionId,
                                    per_page: perPage,
                                },
                                {
                                    preserveState: true,
                                    preserveScroll: true,
                                },
                            );
                        }}
                        perPage={perPage}
                        onPerPageChange={setPerPage}
                        perPageOptions={[5, 10, 25, 50]}
                        emptyMessage="No hay estudiantes registrados en esta jornada."
                    />
                </div>

                {/* Modal: Registrar horas globales */}
                <Dialog
                    open={globalHoursModal.open}
                    onOpenChange={(open) =>
                        setGlobalHoursModal({ ...globalHoursModal, open })
                    }
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>
                                Registrar Horas -{' '}
                                {globalHoursModal.student?.student_name}
                            </DialogTitle>
                            <DialogDescription>
                                Asigna horas de forma global al estudiante para
                                esta jornada.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="global-hours">Horas</Label>
                                <Input
                                    id="global-hours"
                                    type="number"
                                    min="0"
                                    step="0.5"
                                    value={globalHours.hours}
                                    onChange={(e) =>
                                        setGlobalHours({
                                            ...globalHours,
                                            hours:
                                                parseFloat(e.target.value) || 0,
                                        })
                                    }
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="global-category">
                                    Categoría de Actividad
                                </Label>
                                <Select
                                    value={globalHours.activity_category_id.toString()}
                                    onValueChange={(value) =>
                                        setGlobalHours({
                                            ...globalHours,
                                            activity_category_id:
                                                parseInt(value),
                                        })
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar categoría" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {activityCategories.map((cat) => (
                                            <SelectItem
                                                key={cat.id}
                                                value={cat.id.toString()}
                                            >
                                                {cat.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                onClick={() =>
                                    setGlobalHoursModal({
                                        open: false,
                                        student: null,
                                    })
                                }
                            >
                                Cancelar
                            </Button>
                            <Button
                                onClick={submitGlobalHours}
                                disabled={
                                    isSubmittingGlobal || globalHours.hours <= 0
                                }
                            >
                                {isSubmittingGlobal
                                    ? 'Guardando...'
                                    : 'Guardar'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Modal: Registrar actividades detalladas */}
                <Dialog
                    open={activitiesModal.open}
                    onOpenChange={(open) =>
                        setActivitiesModal({ ...activitiesModal, open })
                    }
                >
                    <DialogContent className="max-w-2xl">
                        <DialogHeader>
                            <DialogTitle>
                                Detalle de Actividades -{' '}
                                {activitiesModal.student?.student_name}
                            </DialogTitle>
                            <DialogDescription>
                                Registra las actividades realizadas por el
                                estudiante en esta jornada con el tiempo
                                invertido en cada una.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="max-h-[60vh] space-y-3 overflow-y-auto py-4">
                            {activities.length === 0 ? (
                                <p className="py-4 text-center text-sm text-neutral-500">
                                    No hay actividades registradas. Agrega una
                                    nueva.
                                </p>
                            ) : (
                                activities.map((activity, index) => (
                                    <div
                                        key={activity.id}
                                        className="flex items-start gap-3 rounded-lg border p-3"
                                    >
                                        <div className="flex-1 space-y-2">
                                            <div className="grid grid-cols-2 gap-2">
                                                <div>
                                                    <Label className="text-xs">
                                                        Categoría
                                                    </Label>
                                                    <Select
                                                        value={activity.activity_category_id.toString()}
                                                        onValueChange={(
                                                            value,
                                                        ) =>
                                                            updateActivity(
                                                                activity.id,
                                                                'activity_category_id',
                                                                parseInt(value),
                                                            )
                                                        }
                                                    >
                                                        <SelectTrigger className="h-9">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {activityCategories.map(
                                                                (cat) => (
                                                                    <SelectItem
                                                                        key={
                                                                            cat.id
                                                                        }
                                                                        value={cat.id.toString()}
                                                                    >
                                                                        {
                                                                            cat.name
                                                                        }
                                                                    </SelectItem>
                                                                ),
                                                            )}
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                                <div>
                                                    <Label className="text-xs">
                                                        Horas
                                                    </Label>
                                                    <Input
                                                        type="number"
                                                        min="0"
                                                        step="0.5"
                                                        className="h-9"
                                                        value={activity.hours}
                                                        onChange={(e) =>
                                                            updateActivity(
                                                                activity.id,
                                                                'hours',
                                                                parseFloat(
                                                                    e.target
                                                                        .value,
                                                                ) || 0,
                                                            )
                                                        }
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="h-8 w-8 shrink-0 text-red-500 hover:bg-red-50"
                                            onClick={() =>
                                                removeActivity(activity.id)
                                            }
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </div>
                                ))
                            )}
                            <Button
                                variant="outline"
                                size="sm"
                                className="w-full"
                                onClick={addActivityRow}
                            >
                                <Plus className="mr-2 h-4 w-4" />
                                Agregar Actividad
                            </Button>
                        </div>
                        <DialogFooter>
                            <div className="flex w-full items-center justify-between">
                                <span className="text-sm text-neutral-500">
                                    Total:{' '}
                                    <span className="font-medium text-green-600">
                                        {activities.reduce(
                                            (sum, a) => sum + a.hours,
                                            0,
                                        )}
                                        h
                                    </span>
                                </span>
                                <div className="flex gap-2">
                                    <Button
                                        variant="outline"
                                        onClick={() => {
                                            setActivitiesModal({
                                                open: false,
                                                student: null,
                                            });
                                            setActivities([]);
                                        }}
                                    >
                                        Cancelar
                                    </Button>
                                    <Button
                                        onClick={submitActivities}
                                        disabled={
                                            isSubmittingActivities ||
                                            activities.length === 0 ||
                                            activities.every(
                                                (a) => a.hours <= 0,
                                            )
                                        }
                                    >
                                        {isSubmittingActivities
                                            ? 'Guardando...'
                                            : 'Guardar'}
                                    </Button>
                                </div>
                            </div>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <AlertDialog open={confirmDialogOpen} onOpenChange={setConfirmDialogOpen}>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>¿Eliminar jornada de campo?</AlertDialogTitle>
                            <AlertDialogDescription>
                                Esta acción no se puede deshacer. La jornada de campo <strong>{fieldSession.name}</strong> será eliminada permanentemente junto con todos sus registros de asistencia.
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Cancelar</AlertDialogCancel>
                            <AlertDialogAction
                                onClick={confirmDelete}
                                className="bg-red-600 hover:bg-red-700"
                                data-test="confirm-delete-button"
                            >
                                Eliminar
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </div>
        </AppLayout>
    );
}
