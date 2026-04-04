import { useEffect, useMemo, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, BookUser, Check, Search, Users } from 'lucide-react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem } from '@/types';

interface Teacher {
    id: number;
    name: string;
    cedula: string;
}

interface TeacherAssignment {
    id: number;
    teacher: {
        id: number;
        name: string;
    };
}

interface Section {
    id: number;
    name: string;
    enrollments_count: number;
    teacher_assignments: TeacherAssignment[];
}

interface Grade {
    id: number;
    name: string;
    sections: Section[];
}

interface Props {
    activeYear: {
        id: number;
        name: string;
    };
    availableTeachers: Teacher[];
    grades: Grade[];
}

export default function TeacherAssignmentsCreate({
    activeYear,
    availableTeachers,
    grades,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Asignaciones Docentes', href: '/admin/teacher-assignments' },
        { title: 'Consola de Asignación', href: '#' },
    ];

    const { errors } = usePage().props;

    const [searchQuery, setSearchQuery] = useState('');
    const [selectedTeacherId, setSelectedTeacherId] = useState<number | null>(
        null,
    );
    const [selectedSections, setSelectedSections] = useState<number[]>([]);
    const [isProcessing, setIsProcessing] = useState(false);

    const filteredTeachers = useMemo(() => {
        if (!searchQuery) return availableTeachers;
        const q = searchQuery.toLowerCase();
        return availableTeachers.filter(
            (t) =>
                t.name.toLowerCase().includes(q) ||
                t.cedula.toLowerCase().includes(q),
        );
    }, [availableTeachers, searchQuery]);

    useEffect(() => {
        if (!selectedTeacherId) {
            setSelectedSections([]);
            return;
        }

        const currentIds: number[] = [];
        grades.forEach((g) => {
            g.sections.forEach((s) => {
                if (
                    s.teacher_assignments?.some(
                        (ta) => ta.teacher.id === selectedTeacherId,
                    )
                ) {
                    currentIds.push(s.id);
                }
            });
        });
        setSelectedSections(currentIds);
    }, [selectedTeacherId, grades]);

    const toggleSection = (sectionId: number) => {
        if (selectedSections.includes(sectionId)) {
            setSelectedSections(
                selectedSections.filter((id) => id !== sectionId),
            );
        } else {
            setSelectedSections([...selectedSections, sectionId]);
        }
    };

    const isDirty = useMemo(() => {
        if (!selectedTeacherId) return false;

        const originalIds: number[] = [];
        grades.forEach((g) => {
            g.sections.forEach((s) => {
                if (
                    s.teacher_assignments?.some(
                        (ta) => ta.teacher.id === selectedTeacherId,
                    )
                ) {
                    originalIds.push(s.id);
                }
            });
        });

        if (originalIds.length !== selectedSections.length) return true;

        const sortedOriginal = [...originalIds].sort();
        const sortedSelected = [...selectedSections].sort();

        return sortedOriginal.some((id, index) => id !== sortedSelected[index]);
    }, [selectedTeacherId, selectedSections, grades]);

    const saveAssignments = () => {
        if (!selectedTeacherId) return;

        const teacher = availableTeachers.find(
            (t) => t.id === selectedTeacherId,
        );

        if (
            confirm(
                `¿Guardar la asignación de ${selectedSections.length} sección(es) para el Prof. ${teacher?.name}?`,
            )
        ) {
            router.post(
                '/admin/teacher-assignments',
                {
                    academic_year_id: activeYear.id,
                    user_id: selectedTeacherId.toString(),
                    section_ids: selectedSections,
                },
                {
                    preserveScroll: true,
                    onStart: () => setIsProcessing(true),
                    onFinish: () => setIsProcessing(false),
                },
            );
        }
    };

    const getAssignedTeacherNames = (section: Section): string => {
        if (
            !section.teacher_assignments ||
            section.teacher_assignments.length === 0
        )
            return 'Ninguno';
        return section.teacher_assignments
            .map((ta) => ta.teacher.name)
            .join(', ');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Asignar Profesores | ${activeYear.name}`} />

            <SettingsLayout>
                <div className="flex h-[calc(100vh-10rem)] flex-col gap-6 overflow-hidden">
                    {/* Header */}
                    <div className="flex shrink-0 items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href="/admin/teacher-assignments">
                                <ArrowLeft className="h-5 w-5" />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                Asignación Docente
                            </h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Selecciona un profesor y asigna las secciones
                                que impartirá en {activeYear.name}.
                            </p>
                        </div>
                    </div>

                    <div className="grid h-full min-h-0 grid-cols-1 gap-6 md:grid-cols-[1fr_2fr]">
                        {/* Panel Izquierdo: Lista de Profesores */}
                        <Card className="flex min-h-0 flex-col overflow-hidden">
                            <CardHeader className="shrink-0 border-b pb-3">
                                <CardTitle className="text-base font-semibold">
                                    Profesores
                                </CardTitle>
                                <div className="relative mt-2">
                                    <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-neutral-400" />
                                    <Input
                                        className="pl-9"
                                        placeholder="Buscar por cédula o nombre..."
                                        value={searchQuery}
                                        onChange={(e) =>
                                            setSearchQuery(e.target.value)
                                        }
                                    />
                                </div>
                            </CardHeader>

                            <CardContent className="flex-1 overflow-auto p-2">
                                {availableTeachers.length > 0 ? (
                                    <div className="space-y-1">
                                        {filteredTeachers.length > 0 ? (
                                            filteredTeachers.map((t) => {
                                                const isSelected =
                                                    selectedTeacherId === t.id;
                                                return (
                                                    <button
                                                        key={t.id}
                                                        type="button"
                                                        onClick={() =>
                                                            setSelectedTeacherId(
                                                                t.id,
                                                            )
                                                        }
                                                        className={`flex w-full items-center gap-3 rounded-lg p-3 text-left transition-all ${
                                                            isSelected
                                                                ? 'bg-neutral-100 ring-1 ring-neutral-300 dark:bg-neutral-800 dark:ring-neutral-600'
                                                                : 'hover:bg-neutral-50 dark:hover:bg-neutral-800/50'
                                                        }`}
                                                    >
                                                        <div
                                                            className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold ${
                                                                isSelected
                                                                    ? 'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900'
                                                                    : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400'
                                                            }`}
                                                        >
                                                            {t.name.charAt(0)}
                                                        </div>
                                                        <div className="min-w-0 flex-1">
                                                            <div
                                                                className={`truncate text-sm font-medium ${isSelected ? 'text-neutral-900 dark:text-neutral-100' : ''}`}
                                                            >
                                                                {t.name}
                                                            </div>
                                                            <div className="font-mono text-xs text-neutral-500">
                                                                {t.cedula}
                                                            </div>
                                                        </div>
                                                        {isSelected && (
                                                            <Check className="h-4 w-4 shrink-0 text-neutral-500" />
                                                        )}
                                                    </button>
                                                );
                                            })
                                        ) : (
                                            <div className="py-8 text-center text-sm text-neutral-500">
                                                No hay resultados para "
                                                {searchQuery}"
                                            </div>
                                        )}
                                    </div>
                                ) : (
                                    <div className="flex h-full flex-col items-center justify-center p-8 text-center text-sm text-neutral-500">
                                        <BookUser className="mb-2 h-10 w-10 opacity-20" />
                                        No hay profesores registrados en el
                                        sistema.
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Panel Derecho: Grilla de Secciones */}
                        <Card className="flex min-h-0 flex-col overflow-hidden">
                            <CardHeader className="flex shrink-0 flex-row items-center justify-between border-b pb-3">
                                <CardTitle className="text-base font-semibold">
                                    Secciones
                                </CardTitle>
                                <Button
                                    onClick={saveAssignments}
                                    disabled={
                                        !selectedTeacherId ||
                                        !isDirty ||
                                        isProcessing
                                    }
                                    size="sm"
                                >
                                    {isProcessing
                                        ? 'Guardando...'
                                        : 'Guardar Cambios'}
                                </Button>
                            </CardHeader>

                            <CardContent className="flex-1 overflow-auto p-4 lg:p-6">
                                {!selectedTeacherId ? (
                                    <div className="flex h-full flex-col items-center justify-center space-y-3">
                                        <BookUser
                                            className="h-16 w-16 text-neutral-200 dark:text-neutral-700"
                                            strokeWidth={1}
                                        />
                                        <p className="font-medium text-neutral-500">
                                            Selecciona un profesor para
                                            comenzar.
                                        </p>
                                    </div>
                                ) : grades.length === 0 ? (
                                    <div className="flex h-full items-center justify-center text-neutral-500">
                                        No hay grados ni secciones configuradas
                                        en el año escolar activo.
                                    </div>
                                ) : (
                                    <div className="space-y-6">
                                        {grades.map((grade) => (
                                            <div
                                                key={grade.id}
                                                className="space-y-3"
                                            >
                                                <h3 className="text-sm font-semibold tracking-wider text-neutral-500 uppercase">
                                                    {grade.name}
                                                </h3>

                                                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                                    {grade.sections.length >
                                                    0 ? (
                                                        grade.sections.map(
                                                            (section) => {
                                                                const isChecked =
                                                                    selectedSections.includes(
                                                                        section.id,
                                                                    );

                                                                return (
                                                                    <button
                                                                        key={
                                                                            section.id
                                                                        }
                                                                        type="button"
                                                                        onClick={() =>
                                                                            toggleSection(
                                                                                section.id,
                                                                            )
                                                                        }
                                                                        className={`relative flex w-full flex-col rounded-lg border p-4 text-left transition-all ${
                                                                            isChecked
                                                                                ? 'border-neutral-900 bg-neutral-50 shadow-sm dark:border-white dark:bg-neutral-800/50'
                                                                                : 'border-neutral-200 hover:border-neutral-300 dark:border-neutral-800 dark:hover:border-neutral-700'
                                                                        }`}
                                                                    >
                                                                        <div className="mb-3 flex items-start justify-between">
                                                                            <div className="text-base font-semibold">
                                                                                {
                                                                                    section.name
                                                                                }
                                                                            </div>
                                                                            <Checkbox
                                                                                checked={
                                                                                    isChecked
                                                                                }
                                                                                onCheckedChange={() =>
                                                                                    toggleSection(
                                                                                        section.id,
                                                                                    )
                                                                                }
                                                                                className="pointer-events-none"
                                                                            />
                                                                        </div>

                                                                        <div className="mt-auto space-y-1.5">
                                                                            <div className="flex items-center gap-1.5 text-xs text-neutral-500">
                                                                                <Users className="h-3.5 w-3.5" />
                                                                                {section.enrollments_count ??
                                                                                    0}{' '}
                                                                                alumno(s)
                                                                            </div>

                                                                            <div className="mt-2 border-t border-neutral-100 pt-2 text-xs text-neutral-400 dark:border-neutral-800">
                                                                                <span className="font-medium text-neutral-500 dark:text-neutral-400">
                                                                                    Profesores:{' '}
                                                                                </span>
                                                                                {getAssignedTeacherNames(
                                                                                    section,
                                                                                )}
                                                                            </div>
                                                                        </div>
                                                                    </button>
                                                                );
                                                            },
                                                        )
                                                    ) : (
                                                        <div className="py-2 text-sm text-neutral-400">
                                                            Sin secciones para
                                                            este grado.
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        ))}

                                        <InputError
                                            message={
                                                errors?.user_id ||
                                                errors?.section_ids
                                            }
                                            className="mt-4 text-center"
                                        />
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
