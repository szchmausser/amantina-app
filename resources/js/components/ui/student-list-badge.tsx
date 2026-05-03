import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { ExternalLink } from 'lucide-react';
import { cn } from '@/lib/utils';

interface Student {
    id: number;
    name: string;
    hours: number;
    percentage: number;
    section?: string;
    grade?: string;
    status?: string;
}

interface StudentListBadgeProps {
    count: number;
    label: string;
    students: Student[];
    variant?: 'success' | 'warning' | 'destructive' | 'secondary';
    icon?: React.ReactNode;
    className?: string;
}

export function StudentListBadge({
    count,
    label,
    students,
    variant = 'secondary',
    icon,
    className,
}: StudentListBadgeProps) {
    const [open, setOpen] = useState(false);

    const handleStudentClick = (studentId: number) => {
        router.visit(`/admin/users/${studentId}`);
        setOpen(false);
    };

    if (count === 0) {
        return (
            <span className={cn('text-sm text-muted-foreground', className)}>
                {icon && <span className="mr-1">{icon}</span>}
                {count} {label}
            </span>
        );
    }

    return (
        <>
            <button
                onClick={() => setOpen(true)}
                className={cn(
                    'inline-flex items-center gap-1 rounded-md px-2.5 py-0.5 text-sm font-medium transition-colors hover:opacity-80',
                    variant === 'success' &&
                        'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                    variant === 'warning' &&
                        'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                    variant === 'destructive' &&
                        'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                    variant === 'secondary' &&
                        'bg-secondary text-secondary-foreground',
                    className,
                )}
            >
                {icon && <span>{icon}</span>}
                {count} {label}
            </button>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-h-[80vh] max-w-2xl overflow-hidden">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            {icon && <span>{icon}</span>}
                            {label} ({count})
                        </DialogTitle>
                    </DialogHeader>

                    <div className="max-h-[60vh] overflow-y-auto">
                        <div className="space-y-2">
                            {students.map((student) => (
                                <button
                                    key={student.id}
                                    onClick={() =>
                                        handleStudentClick(student.id)
                                    }
                                    className="flex w-full items-center justify-between rounded-lg border bg-card p-3 text-left transition-colors hover:bg-accent"
                                >
                                    <div className="flex-1">
                                        <p className="font-medium">
                                            {student.name}
                                        </p>
                                        {(student.section || student.grade) && (
                                            <p className="text-sm text-muted-foreground">
                                                {student.section}{' '}
                                                {student.grade &&
                                                    `(${student.grade})`}
                                            </p>
                                        )}
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
