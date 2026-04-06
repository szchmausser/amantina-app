import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeft,
    ArrowRight,
    Clock,
    UserCheck,
    UserX,
} from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
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
import {
    ActivityDialog,
    DeleteConfirmDialog,
    QuickAssignDialog,
} from './components/Dialogs';
import {
    AvailableStudentList,
    RegisteredStudentCard,
} from './components/StudentCards';
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

interface Activity {
    id: number;
    activity_category: ActivityCategory | null;
    hours: number;
    notes?: string;
}

interface AttendanceStudent extends Student {
    attendance_id?: number;
    attended?: boolean;
    total_hours?: number;
    activities?: Activity[];
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
    isAdmin: boolean;
}

export default function AttendanceIndex({
    fieldSession,
    groupedStudents,
    attendances,
    activityCategories,
    baseHours,
    isAdmin,
}: Props) {
    const { flash } = usePage().props as {
        flash?: { warning?: string; success?: string };
    };

    const [registeredStudents, setRegisteredStudents] =
        useState<AttendanceStudent[]>(attendances);

    const [selectedGradeId, setSelectedGradeId] = useState<string>('all');
    const [selectedSectionId, setSelectedSectionId] = useState<string>('all');

    const [selectedAvailableIds, setSelectedAvailableIds] = useState<number[]>(
        [],
    );
    const [selectedRegisteredIds, setSelectedRegisteredIds] = useState<
        number[]
    >([]);
    const [isProcessing, setIsProcessing] = useState(false);

    const [expandedStudentId, setExpandedStudentId] = useState<number | null>(
        null,
    );

    const [showActivityDialog, setShowActivityDialog] = useState(false);
    const [editingActivity, setEditingActivity] = useState<Activity | null>(
        null,
    );
    const [activityStudentId, setActivityStudentId] = useState<number | null>(
        null,
    );
    const [activityCategoryId, setActivityCategoryId] = useState<string>('');
    const [activityHours, setActivityHours] = useState<string>('');
    const [activityNotes, setActivityNotes] = useState('');

    const [showQuickAssign, setShowQuickAssign] = useState(false);
    const [quickCategoryId, setQuickCategoryId] = useState<string>('');
    const [quickHours, setQuickHours] = useState<string>('');
    const [quickStudentIds, setQuickStudentIds] = useState<number[]>([]);

    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
    const [deleteStudentId, setDeleteStudentId] = useState<number | null>(null);

    const grades = [
        ...new Map(
            groupedStudents.map((s) => [
                s.grade_id,
                { id: s.grade_id, name: s.grade_name },
            ]),
        ).values(),
    ];

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
                },
            );
        } finally {
            setIsProcessing(false);
        }
    };

    const handleMoveToAvailable = async () => {
        if (selectedRegisteredIds.length === 0) return;

        setIsProcessing(true);
        try {
            await router.post(
                `/admin/field-sessions/${fieldSession.id}/attendance/bulk-absent`,
                { student_ids: selectedRegisteredIds },
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

    const handleDeleteAttendance = async () => {
        if (deleteStudentId === null) return;
        const student = registeredStudents.find(
            (s) => s.id === deleteStudentId,
        );
        if (!student?.attendance_id) return;

        router.delete(`/admin/attendance/${student.attendance_id}`, {
            onSuccess: () => {
                setRegisteredStudents(
                    registeredStudents.filter((s) => s.id !== deleteStudentId),
                );
                setShowDeleteConfirm(false);
                setDeleteStudentId(null);
            },
        });
    };

    const openActivityDialog = (studentId: number, activity?: Activity) => {
        setActivityStudentId(studentId);
        if (activity) {
            setEditingActivity(activity);
            setActivityCategoryId(
                activity.activity_category?.id.toString() ?? '',
            );
            setActivityHours(activity.hours.toString());
            setActivityNotes(activity.notes ?? '');
        } else {
            setEditingActivity(null);
            setActivityCategoryId('');
            setActivityHours('');
            setActivityNotes('');
        }
        setShowActivityDialog(true);
    };

    const handleSaveActivity = async () => {
        if (!activityStudentId || !activityCategoryId || !activityHours) return;

        const student = registeredStudents.find(
            (s) => s.id === activityStudentId,
        );
        if (!student?.attendance_id) return;

        const payload = {
            activity_category_id: parseInt(activityCategoryId),
            hours: parseFloat(activityHours),
            notes: activityNotes || null,
        };

        if (editingActivity) {
            router.put(
                `/admin/attendance-activities/${editingActivity.id}`,
                payload,
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        setRegisteredStudents((prev) =>
                            prev.map((s) => {
                                if (s.id !== activityStudentId) return s;
                                const updatedActivities =
                                    s.activities?.map((a) =>
                                        a.id === editingActivity.id
                                            ? {
                                                  ...a,
                                                  activity_category:
                                                      activityCategories.find(
                                                          (c) =>
                                                              c.id ===
                                                              parseInt(
                                                                  activityCategoryId,
                                                              ),
                                                      ) ?? a.activity_category,
                                                  hours: parseFloat(
                                                      activityHours,
                                                  ),
                                                  notes:
                                                      activityNotes ||
                                                      undefined,
                                              }
                                            : a,
                                    ) ?? [];
                                return {
                                    ...s,
                                    activities: updatedActivities,
                                    total_hours: updatedActivities.reduce(
                                        (sum, a) => sum + a.hours,
                                        0,
                                    ),
                                };
                            }),
                        );
                        setShowActivityDialog(false);
                    },
                },
            );
        } else {
            router.post(
                '/admin/attendance-activities',
                { attendance_id: student.attendance_id, ...payload },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        setRegisteredStudents((prev) =>
                            prev.map((s) => {
                                if (s.id !== activityStudentId) return s;
                                const newActivity: Activity = {
                                    id: Date.now(),
                                    activity_category:
                                        activityCategories.find(
                                            (c) =>
                                                c.id ===
                                                parseInt(activityCategoryId),
                                        ) ?? null,
                                    hours: parseFloat(activityHours),
                                    notes: activityNotes || undefined,
                                };
                                const updatedActivities = [
                                    ...(s.activities ?? []),
                                    newActivity,
                                ];
                                return {
                                    ...s,
                                    activities: updatedActivities,
                                    total_hours: updatedActivities.reduce(
                                        (sum, a) => sum + a.hours,
                                        0,
                                    ),
                                };
                            }),
                        );
                        setShowActivityDialog(false);
                    },
                },
            );
        }
    };

    const handleDeleteActivity = async (
        studentId: number,
        activityId: number,
    ) => {
        router.delete(`/admin/attendance-activities/${activityId}`, {
            preserveScroll: true,
            onSuccess: () => {
                setRegisteredStudents((prev) =>
                    prev.map((s) => {
                        if (s.id !== studentId) return s;
                        const updatedActivities =
                            s.activities?.filter((a) => a.id !== activityId) ??
                            [];
                        return {
                            ...s,
                            activities: updatedActivities,
                            total_hours: updatedActivities.reduce(
                                (sum, a) => sum + a.hours,
                                0,
                            ),
                        };
                    }),
                );
            },
        });
    };

    const handleQuickAssign = async () => {
        if (quickStudentIds.length === 0 || !quickCategoryId || !quickHours)
            return;

        const data = quickStudentIds.map((userId) => ({
            user_id: userId,
            activity_category_id: parseInt(quickCategoryId),
            hours: parseFloat(quickHours),
        }));

        router.post(
            `/admin/field-sessions/${fieldSession.id}/attendance/bulk-assign-hours`,
            { data },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setRegisteredStudents((prev) =>
                        prev.map((s) => {
                            if (!quickStudentIds.includes(s.id)) return s;
                            return {
                                ...s,
                                attended: true,
                                activities: [
                                    {
                                        id: Date.now() + s.id,
                                        activity_category:
                                            activityCategories.find(
                                                (c) =>
                                                    c.id ===
                                                    parseInt(quickCategoryId),
                                            ) ?? null,
                                        hours: parseFloat(quickHours),
                                    },
                                ],
                                total_hours: parseFloat(quickHours),
                            };
                        }),
                    );
                    setQuickStudentIds([]);
                    setQuickCategoryId('');
                    setQuickHours('');
                    setShowQuickAssign(false);
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

    const registeredCount = registeredStudents.filter((s) => s.attended).length;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Asistencia - ${fieldSession.name}`} />

            <div className="flex flex-col gap-6 p-4 lg:p-8">
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
                                {fieldSession.name} - {baseHours}h programadas
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-3">
                        <Badge variant="outline">
                            {registeredCount}/{registeredStudents.length}{' '}
                            registrados
                        </Badge>
                        <Badge variant="outline">
                            {fieldSession.status.name === 'planned'
                                ? 'Planificada'
                                : fieldSession.status.name === 'realized'
                                  ? 'Realizada'
                                  : 'Cancelada'}
                        </Badge>
                    </div>
                </div>

                {flash?.warning && (
                    <Alert variant="destructive">
                        <AlertTriangle className="h-4 w-4" />
                        <AlertDescription>{flash.warning}</AlertDescription>
                    </Alert>
                )}

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

                    <Button
                        variant="outline"
                        onClick={() => setShowQuickAssign(true)}
                        disabled={registeredStudents.length === 0}
                        className="ml-auto"
                    >
                        <Clock className="mr-2 h-4 w-4" />
                        Asignación Rápida
                    </Button>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
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
                                <AvailableStudentList
                                    students={filteredStudents}
                                    selectedIds={selectedAvailableIds}
                                    onToggle={(id) =>
                                        setSelectedAvailableIds((prev) =>
                                            prev.includes(id)
                                                ? prev.filter((i) => i !== id)
                                                : [...prev, id],
                                        )
                                    }
                                    onBulkRegister={handleMoveToRegistered}
                                    isProcessing={isProcessing}
                                />
                            )}
                        </CardContent>
                    </Card>

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
                                <div className="space-y-2">
                                    {registeredStudents.map((student) => (
                                        <RegisteredStudentCard
                                            key={student.id}
                                            student={student}
                                            isSelected={selectedRegisteredIds.includes(
                                                student.id,
                                            )}
                                            isExpanded={
                                                expandedStudentId === student.id
                                            }
                                            baseHours={baseHours}
                                            isAdmin={isAdmin}
                                            activityCategories={
                                                activityCategories
                                            }
                                            onToggle={(id) =>
                                                setSelectedRegisteredIds(
                                                    (prev) =>
                                                        prev.includes(id)
                                                            ? prev.filter(
                                                                  (i) =>
                                                                      i !== id,
                                                              )
                                                            : [...prev, id],
                                                )
                                            }
                                            onExpand={setExpandedStudentId}
                                            onDeleteAttendance={(id) => {
                                                setDeleteStudentId(id);
                                                setShowDeleteConfirm(true);
                                            }}
                                            onAddActivity={(id) =>
                                                openActivityDialog(id)
                                            }
                                            onEditActivity={(id, act) =>
                                                openActivityDialog(id, act)
                                            }
                                            onDeleteActivity={
                                                handleDeleteActivity
                                            }
                                        />
                                    ))}
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

            <ActivityDialog
                open={showActivityDialog}
                onOpenChange={setShowActivityDialog}
                isEditing={editingActivity !== null}
                activityCategoryId={activityCategoryId}
                onCategoryChange={setActivityCategoryId}
                activityHours={activityHours}
                onHoursChange={setActivityHours}
                activityNotes={activityNotes}
                onNotesChange={setActivityNotes}
                onSave={handleSaveActivity}
                categories={activityCategories}
            />

            <QuickAssignDialog
                open={showQuickAssign}
                onOpenChange={setShowQuickAssign}
                quickCategoryId={quickCategoryId}
                onCategoryChange={setQuickCategoryId}
                quickHours={quickHours}
                onHoursChange={setQuickHours}
                quickStudentIds={quickStudentIds}
                onToggleStudent={(id) =>
                    setQuickStudentIds((prev) =>
                        prev.includes(id)
                            ? prev.filter((i) => i !== id)
                            : [...prev, id],
                    )
                }
                onAssign={handleQuickAssign}
                categories={activityCategories}
                students={registeredStudents}
            />

            <DeleteConfirmDialog
                open={showDeleteConfirm}
                onOpenChange={(open) => {
                    setShowDeleteConfirm(open);
                    if (!open) setDeleteStudentId(null);
                }}
                onConfirm={handleDeleteAttendance}
            />
        </AppLayout>
    );
}
