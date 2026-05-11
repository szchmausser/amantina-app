import { useState, useEffect, useRef, useMemo } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft, ArrowRight } from 'lucide-react';
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
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { useDebounce } from '@/hooks/use-debounce';
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
import { show as userShow } from '@/routes/admin/users';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface StudentRow {
    id: number;
    name: string;
    cedula: string;
    grade_id: number;
    grade_name: string;
    section_id: number;
    section_name: string;
    is_registered: boolean;
    has_activities: boolean;
}

interface PaginatedStudents {
    data: StudentRow[];
    links: PaginationLink[];
    total: number;
    current_page: number;
    last_page: number;
}

interface GradeOption {
    id: number;
    name: string;
}

interface SectionOption {
    id: number;
    name: string;
    grade_id: number;
}

interface FieldSession {
    id: number;
    name: string;
    base_hours: number;
    start_datetime: string;
    activity_name: string | null;
    location_name: string | null;
    teacher: { id: number; name: string };
    status: { id: number; name: string };
}

interface Props {
    fieldSession: FieldSession;
    students: PaginatedStudents;
    filters: {
        search?: string;
        grade_id?: string;
        section_id?: string;
        status?: string;
        per_page?: number;
    };
    availableGrades: GradeOption[];
    availableSections: SectionOption[];
    baseHours: number;
    isAdmin: boolean;
}

export default function AttendanceIndex({
    fieldSession,
    students,
    filters,
    availableGrades,
    availableSections,
    baseHours,
    isAdmin,
}: Props) {
    const { flash } = usePage().props as {
        flash?: { warning?: string; success?: string };
    };

    const [search, setSearch] = useState(filters.search || '');
    const [gradeId, setGradeId] = useState(filters.grade_id || 'all');
    const [sectionId, setSectionId] = useState(filters.section_id || 'all');
    const [status, setStatus] = useState(filters.status || 'all');
    const [perPage, setPerPage] = useState(filters.per_page || 10);
    const [isSearching, setIsSearching] = useState(false);
    const [selectedIds, setSelectedIds] = useState<number[]>([]);
    const [isProcessing, setIsProcessing] = useState(false);
    const [studentToUnregister, setStudentToUnregister] = useState<StudentRow | null>(null);

    const isFirstRender = useRef(true);
    const debouncedSearch = useDebounce(search, 300);

    // Filtered sections based on selected grade
    const filteredSections = useMemo(
        () =>
            gradeId === 'all'
                ? availableSections
                : availableSections.filter(
                      (s) => s.grade_id === parseInt(gradeId),
                  ),
        [gradeId, availableSections],
    );

    // Reset section when grade changes
    useEffect(() => {
        if (gradeId !== 'all' && sectionId !== 'all') {
            const sectionStillValid = filteredSections.some(
                (s) => s.id.toString() === sectionId,
            );
            if (!sectionStillValid) {
                setSectionId('all');
            }
        }
    }, [gradeId, filteredSections, sectionId]);

    // Server-side search with debounce
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }

        fetchStudents();
    }, [debouncedSearch, gradeId, sectionId, status, perPage]);

    const fetchStudents = () => {
        router.get(
            `/admin/field-sessions/${fieldSession.id}/attendance`,
            {
                search: debouncedSearch || undefined,
                grade_id: gradeId === 'all' ? undefined : gradeId,
                section_id: sectionId === 'all' ? undefined : sectionId,
                status: status === 'all' ? undefined : status,
                per_page: perPage,
            },
            {
                preserveState: true,
                replace: true,
                onFinish: () => {
                    setIsSearching(false);
                    setSelectedIds([]);
                },
            },
        );
        setIsSearching(true);
    };

    const handleGradeChange = (value: string) => {
        setGradeId(value);
        if (value === 'all') {
            setSectionId('all');
        }
    };

    const handleSectionChange = (value: string) => {
        setSectionId(value);
    };

    const handleStatusChange = (value: string) => {
        setStatus(value);
    };

    const handleClearFilters = () => {
        setSearch('');
        setGradeId('all');
        setSectionId('all');
        setStatus('all');
        setPerPage(10);
    };

    const hasFilters = Boolean(
        search || gradeId !== 'all' || sectionId !== 'all' || status !== 'all' || perPage !== 10,
    );

    const handleToggleSelect = (id: number) => {
        setSelectedIds((prev) =>
            prev.includes(id)
                ? prev.filter((i) => i !== id)
                : [...prev, id],
        );
    };

    const handleToggleAll = () => {
        const unregisteredIds = students.data
            .filter((s) => !s.is_registered)
            .map((s) => s.id);
        const allSelected = unregisteredIds.every((id) =>
            selectedIds.includes(id),
        );

        if (allSelected) {
            setSelectedIds([]);
        } else {
            setSelectedIds(unregisteredIds);
        }
    };

    const handleBulkRegister = async () => {
        if (selectedIds.length === 0) return;

        setIsProcessing(true);
        try {
            await router.post(
                `/admin/field-sessions/${fieldSession.id}/attendance`,
                {
                    field_session_id: fieldSession.id,
                    student_ids: selectedIds,
                    attended: true,
                },
                {
                    onSuccess: () => {
                        setSelectedIds([]);
                    },
                },
            );
        } finally {
            setIsProcessing(false);
        }
    };

    const handleUnregister = (student: StudentRow) => {
        setStudentToUnregister(student);
    };

    const confirmUnregister = () => {
        if (!studentToUnregister) return;

        router.delete(
            `/admin/field-sessions/${fieldSession.id}/attendance/${studentToUnregister.id}`,
            {
                onSuccess: () => {
                    setStudentToUnregister(null);
                },
            },
        );
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Jornadas de Campo', href: '/admin/field-sessions' },
        {
            title: fieldSession.name,
            href: `/admin/field-sessions/${fieldSession.id}`,
        },
        { title: 'Asistencia', href: '#' },
    ];

    // Pagination info
    const pagination: PaginationInfo | undefined =
        students.last_page > 1
            ? {
                  links: students.links,
                  total: students.total,
                  current_page: students.current_page,
                  last_page: students.last_page,
              }
            : undefined;

    const unregisteredOnPage = students.data.filter(
        (s) => !s.is_registered,
    );
    const allOnPageSelected =
        unregisteredOnPage.length > 0 &&
        unregisteredOnPage.every((s) => selectedIds.includes(s.id));

    // Table columns
    const tableColumns = (
        <>
            <DataTableHead>
                <DataTableTH className="w-10">
                    <Checkbox
                        checked={allOnPageSelected}
                        onCheckedChange={handleToggleAll}
                        aria-label="Seleccionar todos"
                        data-testid="select-all-checkbox"
                    />
                </DataTableTH>
                <DataTableTH className="w-12">#</DataTableTH>
                <DataTableTH>Estudiante</DataTableTH>
                <DataTableTH className="w-32">Cedula</DataTableTH>
                <DataTableTH className="w-28">Grado</DataTableTH>
                <DataTableTH className="w-24">Seccion</DataTableTH>
                <DataTableTH className="w-32">Estado</DataTableTH>
                <DataTableTH className="w-28 text-right">Acciones</DataTableTH>
            </DataTableHead>
            <DataTableBody>
                {students.data.map((student, index) => (
                    <DataTableTR key={student.id}>
                        <DataTableTD>
                            {!student.is_registered && (
                                <Checkbox
                                    checked={selectedIds.includes(student.id)}
                                    onCheckedChange={() =>
                                        handleToggleSelect(student.id)
                                    }
                                    aria-label={`Seleccionar ${student.name}`}
                                    data-testid={`student-checkbox-${student.id}`}
                                />
                            )}
                        </DataTableTD>
                        <DataTableTD className="font-mono text-xs text-neutral-400">
                            {(students.current_page - 1) * perPage + index + 1}
                        </DataTableTD>
                        <DataTableTD>
                            <Link
                                href={userShow(student.id).url}
                                className="font-medium text-neutral-900 hover:text-blue-600 dark:text-neutral-100"
                                data-testid={`student-link-${student.id}`}
                            >
                                {student.name}
                            </Link>
                        </DataTableTD>
                        <DataTableTD className="font-mono text-neutral-500">
                            {student.cedula}
                        </DataTableTD>
                        <DataTableTD className="text-neutral-600 dark:text-neutral-400">
                            {student.grade_name}
                        </DataTableTD>
                        <DataTableTD className="text-neutral-600 dark:text-neutral-400">
                            {student.section_name}
                        </DataTableTD>
                        <DataTableTD>
                            {student.is_registered ? (
                                <Badge
                                    variant="default"
                                    className="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400"
                                    data-testid={`status-registered-${student.id}`}
                                >
                                    Registrado
                                </Badge>
                            ) : (
                                <Badge
                                    variant="secondary"
                                    data-testid={`status-unregistered-${student.id}`}
                                >
                                    Sin registrar
                                </Badge>
                            )}
                        </DataTableTD>
                        <DataTableTD className="text-right">
                            {!student.is_registered && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="text-blue-600 border-blue-200 hover:bg-blue-50"
                                    onClick={() =>
                                        router.post(
                                            `/admin/field-sessions/${fieldSession.id}/attendance`,
                                            {
                                                field_session_id:
                                                    fieldSession.id,
                                                student_ids: [student.id],
                                                attended: true,
                                            },
                                        )
                                    }
                                    data-testid={`register-button-${student.id}`}
                                >
                                    Registrar
                                </Button>
                            )}
                            {student.is_registered && !student.has_activities && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="text-red-600"
                                    onClick={() => handleUnregister(student)}
                                    data-testid={`unregister-button-${student.id}`}
                                >
                                    Desregistrar
                                </Button>
                            )}
                            {student.is_registered && student.has_activities && (
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <span className="inline-block">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="text-red-400 border-red-200 pointer-events-none"
                                                disabled
                                                data-testid={`unregister-button-${student.id}`}
                                            >
                                                Desregistrar
                                            </Button>
                                        </span>
                                    </TooltipTrigger>
                                    <TooltipContent side="left" className="max-w-[220px] text-xs">
                                        <p>No se puede desregistrar: el alumno tiene actividades registradas. Elimínelas desde la jornada.</p>
                                    </TooltipContent>
                                </Tooltip>
                            )}
                        </DataTableTD>
                    </DataTableTR>
                ))}
            </DataTableBody>
        </>
    );

    // Grade filter select
    const gradeFilterSelect = (
        <Select value={gradeId} onValueChange={handleGradeChange}>
            <SelectTrigger
                className="h-10 w-full sm:w-44"
                data-testid="grade-filter"
            >
                <SelectValue placeholder="Todos los grados" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="all">Todos los grados</SelectItem>
                {availableGrades.map((grade) => (
                    <SelectItem
                        key={grade.id}
                        value={grade.id.toString()}
                    >
                        {grade.name}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );

    // Section filter select — disabled until a grade is selected
    const sectionFilterSelect = (
        <Select
            value={sectionId}
            onValueChange={handleSectionChange}
            disabled={gradeId === 'all'}
        >
            <SelectTrigger
                className="h-10 w-full sm:w-44"
                data-testid="section-filter"
            >
                <SelectValue placeholder={
                    gradeId === 'all'
                        ? 'Selecciona un grado...'
                        : 'Todas las secciones'
                } />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="all">Todas las secciones</SelectItem>
                {filteredSections.map((section) => (
                    <SelectItem
                        key={section.id}
                        value={section.id.toString()}
                    >
                        {section.name}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );

    // Status filter select
    const statusFilterSelect = (
        <Select value={status} onValueChange={handleStatusChange}>
            <SelectTrigger
                className="h-10 w-full sm:w-44"
                data-testid="status-filter"
            >
                <SelectValue placeholder="Todos" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="all">Todos</SelectItem>
                <SelectItem value="registered">Registrado</SelectItem>
                <SelectItem value="unregistered">Sin registrar</SelectItem>
            </SelectContent>
        </Select>
    );

    // Bulk register button
    const bulkButton =
        selectedIds.length > 0 ? (
            <Button
                onClick={handleBulkRegister}
                disabled={isProcessing}
                className="sm:ml-auto"
                data-testid="bulk-register-button"
            >
                <ArrowRight className="mr-2 h-4 w-4" />
                Registrar seleccionados ({selectedIds.length})
            </Button>
        ) : undefined;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Asistencia - ${fieldSession.name}`} />

            <TooltipProvider delayDuration={200}>
                <div className="flex flex-col gap-6 p-4 lg:p-8">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                Registro de Asistencia
                            </h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                {fieldSession.name} - {baseHours}h programadas
                            </p>
                        </div>
                        <Badge
                            variant="outline"
                            className={
                                fieldSession.status.name === 'planned'
                                    ? 'bg-blue-100 text-blue-800'
                                    : fieldSession.status.name === 'realized'
                                      ? 'bg-green-100 text-green-800'
                                      : 'bg-red-100 text-red-800'
                            }
                        >
                            {fieldSession.status.name === 'planned'
                                ? 'Planificada'
                                : fieldSession.status.name === 'realized'
                                  ? 'Realizada'
                                  : 'Cancelada'}
                        </Badge>
                    </div>
                    <Button variant="outline" size="sm" onClick={() => window.history.back()}>
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Volver
                    </Button>
                </div>

                {/* Flash warnings */}
                {flash?.warning && (
                    <Alert variant="destructive">
                        <AlertTriangle className="h-4 w-4" />
                        <AlertDescription>{flash.warning}</AlertDescription>
                    </Alert>
                )}

                {/* Filters */}
                <TableFilters
                    searchValue={search}
                    onSearchChange={setSearch}
                    searchPlaceholder="Buscar por nombre o cedula..."
                    searchLoading={isSearching}
                    filterSelect={
                        <div className="flex gap-2">
                            {gradeFilterSelect}
                            {sectionFilterSelect}
                            {statusFilterSelect}
                        </div>
                    }
                    hasFilters={hasFilters}
                    onClearFilters={handleClearFilters}
                    createButton={bulkButton}
                />

                {/* DataTable */}
                <DataTable
                    data={students.data}
                    columns={tableColumns}
                    pagination={pagination}
                    onPageChange={(page, url) => {
                        router.get(
                            url,
                            {
                                search: search || undefined,
                                grade_id:
                                    gradeId === 'all' ? undefined : gradeId,
                                section_id:
                                    sectionId === 'all'
                                        ? undefined
                                        : sectionId,
                                status: status === 'all' ? undefined : status,
                                per_page: perPage,
                            },
                            {
                                preserveState: true,
                                replace: true,
                            },
                        );
                    }}
                    perPage={perPage}
                    onPerPageChange={setPerPage}
                    perPageOptions={[5, 10, 15, 25, 50, 100]}
                    emptyMessage="No se encontraron estudiantes que coincidan con los criterios de busqueda."
                />

                {/* Unregister Confirmation Dialog */}
                <AlertDialog open={studentToUnregister !== null} onOpenChange={(open) => !open && setStudentToUnregister(null)}>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>¿Quitar estudiante de la jornada?</AlertDialogTitle>
                            <AlertDialogDescription>
                                ¿Quitar a {studentToUnregister?.name} de la jornada? Su registro de asistencia será eliminado.
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Cancelar</AlertDialogCancel>
                            <AlertDialogAction
                                variant="destructive"
                                onClick={confirmUnregister}
                                data-testid="confirm-unregister-button"
                            >
                                Desregistrar
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </div>
            </TooltipProvider>
        </AppLayout>
    );
}
