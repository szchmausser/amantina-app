import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    ArrowLeft,
    ArrowRight,
    CheckCircle2,
    ChevronDown,
    ChevronUp,
    Clock,
    Plus,
    Trash2,
    UserCheck,
    UserX,
    AlertTriangle,
} from 'lucide-react';

interface ActivityCategory {
    id: number;
    name: string;
}

interface Activity {
    id: number;
    activity_category: ActivityCategory | null;
    hours: number;
    notes?: string;
}

interface Student {
    id: number;
    name: string;
    cedula: string;
}

interface AttendanceStudent extends Student {
    attendance_id?: number;
    attended?: boolean;
    total_hours?: number;
    activities?: Activity[];
}

interface RegisteredStudentCardProps {
    student: AttendanceStudent;
    isSelected: boolean;
    isExpanded: boolean;
    baseHours: number;
    isAdmin: boolean;
    activityCategories: ActivityCategory[];
    onToggle: (id: number) => void;
    onExpand: (id: number | null) => void;
    onDeleteAttendance: (studentId: number) => void;
    onAddActivity: (studentId: number) => void;
    onEditActivity: (studentId: number, activity: Activity) => void;
    onDeleteActivity: (studentId: number, activityId: number) => void;
}

export function RegisteredStudentCard({
    student,
    isSelected,
    isExpanded,
    baseHours,
    isAdmin,
    activityCategories,
    onToggle,
    onExpand,
    onDeleteAttendance,
    onAddActivity,
    onEditActivity,
    onDeleteActivity,
}: RegisteredStudentCardProps) {
    const status = getAttendanceStatus(student, baseHours);
    const StatusIcon = status.icon;
    const { pct, exceeds } = getHoursProgress(
        student.total_hours ?? 0,
        baseHours,
    );

    return (
        <div
            className={`rounded-lg border transition-colors ${
                isSelected
                    ? 'border-primary bg-primary/5'
                    : 'border-neutral-200'
            }`}
        >
            <div className="flex items-center gap-3 p-2">
                <Checkbox
                    checked={isSelected}
                    onCheckedChange={() => onToggle(student.id)}
                />
                <button
                    type="button"
                    className="flex-1 text-left"
                    onClick={() => onExpand(isExpanded ? null : student.id)}
                >
                    <div className="flex items-center gap-2">
                        <p className="text-sm font-medium">{student.name}</p>
                        {isExpanded ? (
                            <ChevronUp className="h-3 w-3 text-neutral-400" />
                        ) : (
                            <ChevronDown className="h-3 w-3 text-neutral-400" />
                        )}
                    </div>
                    <p className="text-xs text-neutral-500">{student.cedula}</p>
                </button>
                <Badge className={status.color}>
                    <StatusIcon className="mr-1 h-3 w-3" />
                    {status.label}
                </Badge>
                {isAdmin && student.attendance_id && (
                    <Button
                        variant="ghost"
                        size="icon"
                        className="h-6 w-6 text-red-500 hover:bg-red-50 hover:text-red-700"
                        onClick={() => onDeleteAttendance(student.id)}
                    >
                        <Trash2 className="h-3 w-3" />
                    </Button>
                )}
            </div>

            {isExpanded && student.attended && (
                <div className="border-t border-neutral-200 px-3 py-3">
                    <div className="mb-2">
                        <div className="flex items-center justify-between text-xs text-neutral-500">
                            <span>Progreso de horas</span>
                            <span
                                className={
                                    exceeds ? 'font-medium text-orange-600' : ''
                                }
                            >
                                {student.total_hours ?? 0}h / {baseHours}h
                            </span>
                        </div>
                        <div className="mt-1 h-2 w-full overflow-hidden rounded-full bg-neutral-200">
                            <div
                                className={`h-full transition-all ${exceeds ? 'bg-orange-500' : 'bg-green-500'}`}
                                style={{ width: `${pct}%` }}
                            />
                        </div>
                        {exceeds && (
                            <p className="mt-1 text-xs text-orange-600">
                                ⚠ Horas exceden la jornada
                            </p>
                        )}
                    </div>

                    <div className="space-y-1">
                        {student.activities?.map((act) => (
                            <div
                                key={act.id}
                                className="flex items-center gap-2 rounded bg-neutral-50 px-2 py-1.5"
                            >
                                <div className="flex-1">
                                    <p className="text-xs font-medium">
                                        {act.activity_category?.name ??
                                            'Sin categoría'}
                                    </p>
                                    {act.notes && (
                                        <p className="text-xs text-neutral-500">
                                            {act.notes}
                                        </p>
                                    )}
                                </div>
                                <span className="text-xs font-medium text-neutral-700">
                                    {act.hours}h
                                </span>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-5 w-5"
                                    onClick={() =>
                                        onEditActivity(student.id, act)
                                    }
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="12"
                                        height="12"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="2"
                                    >
                                        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
                                    </svg>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-5 w-5 text-red-500 hover:text-red-700"
                                    onClick={() =>
                                        onDeleteActivity(student.id, act.id)
                                    }
                                >
                                    <Trash2 className="h-3 w-3" />
                                </Button>
                            </div>
                        ))}
                    </div>

                    <Button
                        variant="outline"
                        size="sm"
                        className="mt-2 w-full gap-1 text-xs"
                        onClick={() => onAddActivity(student.id)}
                    >
                        <Plus className="h-3 w-3" />
                        Agregar subactividad
                    </Button>
                </div>
            )}
        </div>
    );
}

interface AvailableStudentListProps {
    students: Student[];
    selectedIds: number[];
    onToggle: (id: number) => void;
    onBulkRegister: () => void;
    isProcessing: boolean;
}

export function AvailableStudentList({
    students,
    selectedIds,
    onToggle,
    onBulkRegister,
    isProcessing,
}: AvailableStudentListProps) {
    return (
        <div className="space-y-1">
            {students.map((student) => {
                const isSelected = selectedIds.includes(student.id);
                return (
                    <div
                        key={student.id}
                        className={`flex items-center gap-3 rounded-lg p-2 transition-colors ${
                            isSelected ? 'bg-primary/10' : 'hover:bg-neutral-50'
                        }`}
                    >
                        <Checkbox
                            checked={isSelected}
                            onCheckedChange={() => onToggle(student.id)}
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

            <div className="mt-4 flex justify-end">
                <Button
                    onClick={onBulkRegister}
                    disabled={selectedIds.length === 0 || isProcessing}
                    className="gap-2"
                >
                    <ArrowRight className="h-4 w-4" />
                    Registrar ({selectedIds.length})
                </Button>
            </div>
        </div>
    );
}

function getAttendanceStatus(student: AttendanceStudent, baseHours: number) {
    if (!student.attended) {
        return {
            color: 'bg-red-100 text-red-800',
            label: 'Ausente',
            icon: UserX,
        };
    }
    if (student.total_hours && student.total_hours > 0) {
        const exceeds = student.total_hours > baseHours;
        return {
            color: exceeds
                ? 'bg-orange-100 text-orange-800'
                : 'bg-green-100 text-green-800',
            label: `${student.total_hours}h`,
            icon: exceeds ? AlertTriangle : Clock,
        };
    }
    return {
        color: 'bg-yellow-100 text-yellow-800',
        label: 'Sin horas',
        icon: UserCheck,
    };
}

function getHoursProgress(totalHours: number, baseHours: number) {
    const pct = Math.min((totalHours / baseHours) * 100, 100);
    const exceeds = totalHours > baseHours;
    return { pct, exceeds };
}
