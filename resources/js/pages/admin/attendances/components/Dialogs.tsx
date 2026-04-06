import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
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

interface AttendanceStudent {
    id: number;
    name: string;
    cedula: string;
    attended?: boolean;
}

interface ActivityDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    isEditing: boolean;
    activityCategoryId: string;
    onCategoryChange: (value: string) => void;
    activityHours: string;
    onHoursChange: (value: string) => void;
    activityNotes: string;
    onNotesChange: (value: string) => void;
    onSave: () => void;
    categories: ActivityCategory[];
}

export function ActivityDialog({
    open,
    onOpenChange,
    isEditing,
    activityCategoryId,
    onCategoryChange,
    activityHours,
    onHoursChange,
    activityNotes,
    onNotesChange,
    onSave,
    categories,
}: ActivityDialogProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {isEditing
                            ? 'Editar Subactividad'
                            : 'Agregar Subactividad'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEditing
                            ? 'Modifica los detalles de la subactividad.'
                            : 'Registra una actividad realizada por el estudiante durante la jornada.'}
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4 py-4">
                    <div>
                        <label className="mb-1 block text-sm font-medium">
                            Categoría de Actividad
                        </label>
                        <Select
                            value={activityCategoryId}
                            onValueChange={onCategoryChange}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar categoría" />
                            </SelectTrigger>
                            <SelectContent>
                                {categories.map((cat) => (
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

                    <div>
                        <label className="mb-1 block text-sm font-medium">
                            Horas
                        </label>
                        <Input
                            type="number"
                            step="0.25"
                            min="0"
                            max="24"
                            value={activityHours}
                            onChange={(e) => onHoursChange(e.target.value)}
                            placeholder="Ej: 2.5"
                        />
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-medium">
                            Observaciones
                        </label>
                        <textarea
                            className="flex min-h-[80px] w-full rounded-md border border-neutral-200 bg-transparent px-3 py-2 text-sm placeholder:text-neutral-400 focus:ring-2 focus:ring-neutral-900 focus:outline-none"
                            value={activityNotes}
                            onChange={(e) => onNotesChange(e.target.value)}
                            placeholder="Detalles de la actividad..."
                        />
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                    >
                        Cancelar
                    </Button>
                    <Button
                        onClick={onSave}
                        disabled={!activityCategoryId || !activityHours}
                    >
                        {isEditing ? 'Actualizar' : 'Agregar'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

interface QuickAssignDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    quickCategoryId: string;
    onCategoryChange: (value: string) => void;
    quickHours: string;
    onHoursChange: (value: string) => void;
    quickStudentIds: number[];
    onToggleStudent: (id: number) => void;
    onAssign: () => void;
    categories: ActivityCategory[];
    students: AttendanceStudent[];
}

export function QuickAssignDialog({
    open,
    onOpenChange,
    quickCategoryId,
    onCategoryChange,
    quickHours,
    onHoursChange,
    quickStudentIds,
    onToggleStudent,
    onAssign,
    categories,
    students,
}: QuickAssignDialogProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Asignación Rápida de Horas</DialogTitle>
                    <DialogDescription>
                        Asigna las mismas horas y categoría a múltiples
                        estudiantes de una vez.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4 py-4">
                    <div>
                        <label className="mb-1 block text-sm font-medium">
                            Categoría
                        </label>
                        <Select
                            value={quickCategoryId}
                            onValueChange={onCategoryChange}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar categoría" />
                            </SelectTrigger>
                            <SelectContent>
                                {categories.map((cat) => (
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

                    <div>
                        <label className="mb-1 block text-sm font-medium">
                            Horas por estudiante
                        </label>
                        <Input
                            type="number"
                            step="0.25"
                            min="0"
                            max="24"
                            value={quickHours}
                            onChange={(e) => onHoursChange(e.target.value)}
                            placeholder="Ej: 3"
                        />
                    </div>

                    <div>
                        <label className="mb-2 block text-sm font-medium">
                            Estudiantes ({quickStudentIds.length} seleccionados)
                        </label>
                        <div className="max-h-48 space-y-1 overflow-y-auto rounded-md border border-neutral-200 p-2">
                            {students
                                .filter((s) => s.attended)
                                .map((student) => (
                                    <label
                                        key={student.id}
                                        className="flex cursor-pointer items-center gap-2 rounded px-2 py-1 hover:bg-neutral-50"
                                    >
                                        <Checkbox
                                            checked={quickStudentIds.includes(
                                                student.id,
                                            )}
                                            onCheckedChange={() =>
                                                onToggleStudent(student.id)
                                            }
                                        />
                                        <span className="text-sm">
                                            {student.name}
                                        </span>
                                    </label>
                                ))}
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                    >
                        Cancelar
                    </Button>
                    <Button
                        onClick={onAssign}
                        disabled={
                            quickStudentIds.length === 0 ||
                            !quickCategoryId ||
                            !quickHours
                        }
                    >
                        Asignar Horas
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

interface DeleteConfirmDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onConfirm: () => void;
}

export function DeleteConfirmDialog({
    open,
    onOpenChange,
    onConfirm,
}: DeleteConfirmDialogProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Eliminar Asistencia</DialogTitle>
                    <DialogDescription>
                        ¿Estás seguro de que deseas eliminar este registro de
                        asistencia? Se eliminarán también todas las
                        subactividades asociadas. Esta acción no se puede
                        deshacer.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                    >
                        Cancelar
                    </Button>
                    <Button variant="destructive" onClick={onConfirm}>
                        Eliminar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
