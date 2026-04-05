import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    CheckCircle2,
    Clock,
    UserCheck,
    UserX,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface ActivityCategory {
    id: number;
    name: string;
}

interface Student {
    id: number;
    name: string;
    cedula: string;
}

interface GroupedStudents {
    grade_id: number;
    grade_name: string;
    section_id: number;
    section_name: string;
    students: Student[];
}

interface AttendanceStudent extends Student {
    attendance_id?: number;
    attended?: boolean;
    total_hours?: number;
    activities?: {
        id: number;
        activity_category: ActivityCategory;
        hours: number;
        notes?: string;
    }[];
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
    groupedStudents: GroupedStudents[];
    attendances: AttendanceStudent[];
    activityCategories: ActivityCategory[];
    baseHours: number;
}

export default function AttendanceIndex({
    fieldSession,
    groupedStudents,
    attendances,
    activityCategories,
    baseHours,
}: Props) {
    const { errors } = usePage().props;

    // State for the two-column layout
    const [availableStudents, setAvailableStudents] = useState<Student[]>([]);
    const [registeredStudents, setRegisteredStudents] =
        useState<AttendanceStudent[]>(attendances);

    // Filter state
    const [selectedGradeId, setSelectedGradeId] = useState<string>('all');
    const [selectedSectionId, setSelectedSectionId] = useState<string>('all');

    // Bulk actions state
    const [selectedAvailableIds, setSelectedAvailableIds] = useState<number[]>(
        [],
    );
    const [selectedRegisteredIds, setSelectedRegisteredIds] = useState<
        number[]
    >([]);
    const [isProcessing, setIsProcessing] = useState(false);

    // Get unique grades from groupedStudents
    const grades = [
        ...new Map(
            groupedStudents.map((s) => [
                s.grade_id,
                { id: s.grade_id, name: s.grade_name },
            ]),
        ).values(),
    ];

    // Filter sections based on selected grade
    const sections =
        selectedGradeId === 'all'
            ? [
                  ...new Map(
                      groupedStudents.map((s) => [
                          s.section_id,
                          { id: s.section_id, name: s.section_name },
                      ]),
                  ).values(),
              ]
            : groupedStudents
                  .filter(
                      (s) =>
                          selectedGradeId === 'all' ||
                          s.grade_id === parseInt(selectedGradeId),
                  )
                  .map((s) => ({ id: s.section_id, name: s.section_name }));

    // Get filtered students (excluding already registered)
    const filteredStudents = groupedStudents
        .filter(
            (s) =>
                (selectedGradeId === 'all' ||
                    s.grade_id === parseInt(selectedGradeId)) &&
                (selectedSectionId === 'all' ||
                    s.section_id === parseInt(selectedSectionId)),
        )
        .flatMap((s) => s.students)
        .filter(
            (student) => !registeredStudents.some((r) => r.id === student.id),
        );

    // Move students from available to registered
    const handleMoveToRegistered = async () => {
        if (selectedAvailableIds.length === 0) return;

        setIsProcessing(true);
        try {
            await router.post(
                `/admin/field-sessions/${fieldSession.id}/attendance`,
                {
                    field_session_id: fieldSession.id,
                    student_ids: selectedAvailableIds,
                    attended: true,
                },
                {
                    onSuccess: () => {
                        // Update local state
                        const newRegistered = filteredStudents
                            .filter((s) => selectedAvailableIds.includes(s.id))
                            .map((s) => ({
                                ...s,
                                attended: true,
                                total_hours: 0,
                                activities: [],
                            }));
                        setRegisteredStudents([
                            ...registeredStudents,
                            ...newRegistered,
                        ]);
                        setSelectedAvailableIds([]);
                    },
                    onError: (errors) => {
                        console.error('Error registering attendance:', errors);
                        alert(
                            'Error al registrar asistencia: ' +
                                JSON.stringify(errors),
                        );
                    },
                },
            );
        } finally {
            setIsProcessing(false);
        }
    };

    // Move students from registered to available (mark as absent)
    const handleMoveToAvailable = async () => {
        if (selectedRegisteredIds.length === 0) return;

        setIsProcessing(true);
        try {
            await router.post(
                `/admin/field-sessions/${fieldSession.id}/attendance/bulk-absent`,
                {
                    student_ids: selectedRegisteredIds,
                },
                {
                    onSuccess: () => {
                        setRegisteredStudents(
                            registeredStudents.filter(
                                (s) => !selectedRegisteredIds.includes(s.id),
                            ),
                        );
                        setSelectedRegisteredIds([]);
                    },
                },
            );
        } finally {
            setIsProcessing(false);
        }
    };

    // Toggle student selection in available column
    const toggleAvailableStudent = (id: number) => {
        setSelectedAvailableIds((prev) =>
            prev.includes(id) ? prev.filter((i) => i !== id) : [...prev, id],
        );
    };

    // Toggle student selection in registered column
    const toggleRegisteredStudent = (id: number) => {
        setSelectedRegisteredIds((prev) =>
            prev.includes(id) ? prev.filter((i) => i !== id) : [...prev, id],
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

    const getAttendanceStatus = (student: AttendanceStudent) => {
        if (!student.attended) {
            return {
                color: 'bg-red-100 text-red-800',
                label: 'Ausente',
                icon: UserX,
            };
        }
        if (student.total_hours && student.total_hours > 0) {
            return {
                color: 'bg-green-100 text-green-800',
                label: `${student.total_hours}h`,
                icon: Clock,
            };
        }
        return {
            color: 'bg-yellow-100 text-yellow-800',
            label: 'Sin horas',
            icon: UserCheck,
        };
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Asistencia - ${fieldSession.name}`} />

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
                            <a
                                href={`/admin/field-sessions/${fieldSession.id}`}
                            >
                                <ArrowLeft className="h-4 w-4" />
                            </a>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                Registro de Asistencia
                            </h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                {fieldSession.name} - {fieldSession.base_hours}h
                                programadas
                            </p>
                        </div>
                    </div>
                    <Badge variant="outline">
                        {fieldSession.status.name === 'planned'
                            ? 'Planificada'
                            : fieldSession.status.name === 'realized'
                              ? 'Realizada'
                              : 'Cancelada'}
                    </Badge>
                </div>

                {/* Filters */}
                <div className="flex gap-4">
                    <Select
                        value={selectedGradeId}
                        onValueChange={setSelectedGradeId}
                    >
                        <SelectTrigger className="w-[180px]">
                            <SelectValue placeholder="Todos los grados" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                Todos los grados
                            </SelectItem>
                            {grades.map((grade) => (
                                <SelectItem
                                    key={grade.id}
                                    value={grade.id.toString()}
                                >
                                    {grade.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Select
                        value={selectedSectionId}
                        onValueChange={setSelectedSectionId}
                    >
                        <SelectTrigger className="w-[180px]">
                            <SelectValue placeholder="Todas las secciones" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                Todas las secciones
                            </SelectItem>
                            {sections.map((section) => (
                                <SelectItem
                                    key={section.id}
                                    value={section.id.toString()}
                                >
                                    {section.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* Two Column Layout */}
                <div className="grid gap-6 md:grid-cols-2">
                    {/* Available Students Column */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-2 text-base">
                                <UserX className="h-4 w-4" />
                                Estudiantes sin Registrar
                                <Badge variant="secondary" className="ml-auto">
                                    {filteredStudents.length}
                                </Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {filteredStudents.length === 0 ? (
                                <div className="flex h-32 items-center justify-center text-sm text-neutral-500">
                                    No hay estudiantes disponibles
                                </div>
                            ) : (
                                <div className="space-y-1">
                                    {filteredStudents.map((student) => {
                                        const isSelected =
                                            selectedAvailableIds.includes(
                                                student.id,
                                            );
                                        return (
                                            <div
                                                key={student.id}
                                                className={`flex items-center gap-3 rounded-lg p-2 transition-colors ${
                                                    isSelected
                                                        ? 'bg-primary/10'
                                                        : 'hover:bg-neutral-50'
                                                }`}
                                            >
                                                <Checkbox
                                                    checked={isSelected}
                                                    onCheckedChange={() =>
                                                        toggleAvailableStudent(
                                                            student.id,
                                                        )
                                                    }
                                                />
                                                <div className="flex-1">
                                                    <p className="text-sm font-medium">
                                                        {student.name}
                                                    </p>
                                                    <p className="text-xs text-neutral-500">
                                                        {student.cedula}
                                                    </p>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}

                            <div className="mt-4 flex justify-end">
                                <Button
                                    onClick={handleMoveToRegistered}
                                    disabled={
                                        selectedAvailableIds.length === 0 ||
                                        isProcessing
                                    }
                                    className="gap-2"
                                >
                                    <ArrowRight className="h-4 w-4" />
                                    Registrar ({selectedAvailableIds.length})
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Registered Students Column */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-2 text-base">
                                <UserCheck className="h-4 w-4" />
                                Asistencia Registrada
                                <Badge variant="secondary" className="ml-auto">
                                    {registeredStudents.length}
                                </Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {registeredStudents.length === 0 ? (
                                <div className="flex h-32 items-center justify-center text-sm text-neutral-500">
                                    No hay estudiantes registrados
                                </div>
                            ) : (
                                <div className="space-y-1">
                                    {registeredStudents.map((student) => {
                                        const isSelected =
                                            selectedRegisteredIds.includes(
                                                student.id,
                                            );
                                        const status =
                                            getAttendanceStatus(student);
                                        const StatusIcon = status.icon;

                                        return (
                                            <div
                                                key={student.id}
                                                className={`flex items-center gap-3 rounded-lg p-2 transition-colors ${
                                                    isSelected
                                                        ? 'bg-primary/10'
                                                        : 'hover:bg-neutral-50'
                                                }`}
                                            >
                                                <Checkbox
                                                    checked={isSelected}
                                                    onCheckedChange={() =>
                                                        toggleRegisteredStudent(
                                                            student.id,
                                                        )
                                                    }
                                                />
                                                <div className="flex-1">
                                                    <p className="text-sm font-medium">
                                                        {student.name}
                                                    </p>
                                                    <p className="text-xs text-neutral-500">
                                                        {student.cedula}
                                                    </p>
                                                </div>
                                                <Badge className={status.color}>
                                                    <StatusIcon className="mr-1 h-3 w-3" />
                                                    {status.label}
                                                </Badge>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}

                            <div className="mt-4 flex justify-start">
                                <Button
                                    variant="outline"
                                    onClick={handleMoveToAvailable}
                                    disabled={
                                        selectedRegisteredIds.length === 0 ||
                                        isProcessing
                                    }
                                    className="gap-2 text-red-600 hover:bg-red-50 hover:text-red-700"
                                >
                                    <ArrowLeft className="h-4 w-4" />
                                    Marcar Ausentes (
                                    {selectedRegisteredIds.length})
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
