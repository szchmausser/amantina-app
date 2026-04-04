import { FormEvent, useEffect, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    CheckCircle2,
    GraduationCap,
    Users,
} from 'lucide-react';
import InputError from '@/components/input-error';
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
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem } from '@/types';

interface Section {
    id: number;
    name: string;
    grade_id: number;
}
interface Grade {
    id: number;
    name: string;
    order: number;
    sections: Section[];
}
interface Enrollment {
    id: number;
    user_id: number;
    already_enrolled: boolean;
    student: { name: string; cedula: string };
}
interface AcademicYear {
    id: number;
    name: string;
}

interface Props {
    activeYear: AcademicYear;
    previousYears: AcademicYear[];
    sourceGrades: Grade[];
    sourceEnrollments: Enrollment[];
    suggestedGrade: Grade | null;
    allActiveGrades: Grade[];
    sourceYearId: number | null;
    sourceGradeId: number | null;
    sourceSectionId: number | null;
}

export default function PromoteEnrollments({
    activeYear,
    previousYears,
    sourceGrades,
    sourceEnrollments,
    suggestedGrade,
    allActiveGrades,
    sourceYearId,
    sourceGradeId,
    sourceSectionId,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Inscripciones', href: '/admin/enrollments' },
        { title: 'Panel de Promoción', href: '#' },
    ];

    const { errors } = usePage().props;
    const [selectedStudents, setSelectedStudents] = useState<number[]>([]);
    const [isProcessing, setIsProcessing] = useState(false);

    const [manualDestGradeId, setManualDestGradeId] = useState<number | null>(
        suggestedGrade?.id ?? null,
    );

    useEffect(() => {
        setManualDestGradeId(suggestedGrade?.id ?? null);
    }, [suggestedGrade]);

    const handleSourceChange = (
        key: 'source_year_id' | 'source_grade_id' | 'source_section_id',
        value: string,
    ) => {
        const query: any = {
            source_year_id: sourceYearId,
            source_grade_id: sourceGradeId,
            source_section_id: sourceSectionId,
        };

        query[key] = value === 'all' ? null : value;

        if (key === 'source_year_id') {
            query.source_grade_id = null;
            query.source_section_id = null;
        }
        if (key === 'source_grade_id') {
            query.source_section_id = null;
        }

        setSelectedStudents([]);
        router.get('/admin/enrollments/promote', query, {
            preserveState: true,
        });
    };

    const eligibleStudents = sourceEnrollments.filter(
        (e) => !e.already_enrolled,
    );
    const areAllEligibleSelected =
        eligibleStudents.length > 0 &&
        selectedStudents.length === eligibleStudents.length;

    const toggleAll = () => {
        if (areAllEligibleSelected) {
            setSelectedStudents([]);
        } else {
            setSelectedStudents(eligibleStudents.map((e) => e.user_id));
        }
    };

    const toggleStudent = (userId: number) => {
        if (selectedStudents.includes(userId)) {
            setSelectedStudents(selectedStudents.filter((id) => id !== userId));
        } else {
            setSelectedStudents([...selectedStudents, userId]);
        }
    };

    const promoteTo = (destSection: Section) => {
        if (selectedStudents.length === 0) return;

        if (
            confirm(
                `¿Promover ${selectedStudents.length} alumnos a ${destSection.name}?`,
            )
        ) {
            router.post(
                '/admin/enrollments/promote',
                {
                    academic_year_id: activeYear.id,
                    user_ids: selectedStudents,
                    grade_id: destSection.grade_id.toString(),
                    section_id: destSection.id.toString(),
                },
                {
                    preserveScroll: true,
                    onStart: () => setIsProcessing(true),
                    onFinish: () => setIsProcessing(false),
                    onSuccess: () => setSelectedStudents([]),
                },
            );
        }
    };

    const sourceSections =
        sourceGrades.find((g) => g.id === sourceGradeId)?.sections || [];
    const destSections =
        allActiveGrades.find((g) => g.id === manualDestGradeId)?.sections || [];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Promoción | ${activeYear.name}`} />

            <SettingsLayout>
                <div className="flex h-[calc(100vh-10rem)] flex-col gap-6 overflow-hidden">
                    {/* Header */}
                    <div className="flex shrink-0 items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href="/admin/enrollments">
                                <ArrowLeft className="h-5 w-5" />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                Promoción Masiva
                            </h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Traslada alumnos del año anterior al año escolar
                                activo ({activeYear.name}).
                            </p>
                        </div>
                    </div>

                    <div className="grid h-full min-h-0 grid-cols-1 gap-6 md:grid-cols-2">
                        {/* Panel Izquierdo: Origen */}
                        <Card className="flex min-h-0 flex-col overflow-hidden">
                            <CardHeader className="shrink-0 border-b pb-3">
                                <CardTitle className="text-base font-semibold">
                                    Alumnos de Origen
                                </CardTitle>
                                <div className="mt-2 grid grid-cols-3 gap-2">
                                    <div>
                                        <Select
                                            value={
                                                sourceYearId?.toString() ||
                                                'all'
                                            }
                                            onValueChange={(v) =>
                                                handleSourceChange(
                                                    'source_year_id',
                                                    v,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="h-8 text-xs">
                                                <SelectValue placeholder="Año" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    Año
                                                </SelectItem>
                                                {previousYears.map((y) => (
                                                    <SelectItem
                                                        key={y.id}
                                                        value={y.id.toString()}
                                                    >
                                                        {y.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <Select
                                            value={
                                                sourceGradeId?.toString() ||
                                                'all'
                                            }
                                            onValueChange={(v) =>
                                                handleSourceChange(
                                                    'source_grade_id',
                                                    v,
                                                )
                                            }
                                            disabled={!sourceYearId}
                                        >
                                            <SelectTrigger className="h-8 text-xs">
                                                <SelectValue placeholder="Grado" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    Grado
                                                </SelectItem>
                                                {sourceGrades.map((g) => (
                                                    <SelectItem
                                                        key={g.id}
                                                        value={g.id.toString()}
                                                    >
                                                        {g.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <Select
                                            value={
                                                sourceSectionId?.toString() ||
                                                'all'
                                            }
                                            onValueChange={(v) =>
                                                handleSourceChange(
                                                    'source_section_id',
                                                    v,
                                                )
                                            }
                                            disabled={!sourceGradeId}
                                        >
                                            <SelectTrigger className="h-8 text-xs">
                                                <SelectValue placeholder="Sección" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    Sección
                                                </SelectItem>
                                                {sourceSections.map((s) => (
                                                    <SelectItem
                                                        key={s.id}
                                                        value={s.id.toString()}
                                                    >
                                                        {s.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                            </CardHeader>

                            <CardContent className="flex-1 overflow-auto p-0">
                                {sourceEnrollments.length > 0 ? (
                                    <div className="divide-y">
                                        <div className="sticky top-0 z-10 flex items-center justify-between bg-neutral-50 p-3 dark:bg-neutral-800/30">
                                            <div className="flex items-center gap-2">
                                                <Checkbox
                                                    checked={
                                                        areAllEligibleSelected
                                                    }
                                                    onCheckedChange={toggleAll}
                                                    disabled={
                                                        eligibleStudents.length ===
                                                        0
                                                    }
                                                />
                                                <span className="text-sm font-medium">
                                                    Seleccionar Todos
                                                </span>
                                            </div>
                                            <Badge
                                                variant="secondary"
                                                className="text-xs"
                                            >
                                                {selectedStudents.length} /{' '}
                                                {eligibleStudents.length}
                                            </Badge>
                                        </div>
                                        <div className="p-2">
                                            {sourceEnrollments.map(
                                                (enr, index) => (
                                                    <label
                                                        key={enr.id}
                                                        className={`flex items-center gap-3 rounded-lg p-3 transition-colors ${
                                                            enr.already_enrolled
                                                                ? 'pointer-events-none bg-neutral-50 opacity-40 dark:bg-neutral-900/30'
                                                                : 'cursor-pointer hover:bg-neutral-50 dark:hover:bg-neutral-800/50'
                                                        }`}
                                                    >
                                                        <span className="w-5 shrink-0 text-right font-mono text-[10px] text-neutral-400">
                                                            {index + 1}
                                                        </span>
                                                        <Checkbox
                                                            checked={selectedStudents.includes(
                                                                enr.user_id,
                                                            )}
                                                            onCheckedChange={() =>
                                                                toggleStudent(
                                                                    enr.user_id,
                                                                )
                                                            }
                                                            disabled={
                                                                enr.already_enrolled
                                                            }
                                                        />
                                                        <div className="min-w-0 flex-1">
                                                            <div
                                                                className={`truncate text-sm font-medium ${enr.already_enrolled ? 'text-neutral-400 line-through' : ''}`}
                                                            >
                                                                {
                                                                    enr.student
                                                                        .name
                                                                }
                                                            </div>
                                                            <div
                                                                className={`font-mono text-xs ${enr.already_enrolled ? 'text-neutral-400' : 'text-neutral-500'}`}
                                                            >
                                                                {
                                                                    enr.student
                                                                        .cedula
                                                                }
                                                            </div>
                                                        </div>
                                                        {enr.already_enrolled && (
                                                            <Badge
                                                                variant="outline"
                                                                className="border-green-200 bg-green-50 text-xs text-green-600 dark:border-green-900 dark:bg-green-900/20"
                                                            >
                                                                <CheckCircle2 className="mr-1 h-3 w-3" />{' '}
                                                                Inscrito
                                                            </Badge>
                                                        )}
                                                    </label>
                                                ),
                                            )}
                                        </div>
                                    </div>
                                ) : (
                                    <div className="flex h-full flex-col items-center justify-center p-8 text-center text-sm text-neutral-500">
                                        <GraduationCap className="mb-2 h-10 w-10 opacity-20" />
                                        Selecciona un año, grado y sección de
                                        origen.
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Panel Derecho: Destino */}
                        <Card className="flex min-h-0 flex-col overflow-hidden">
                            <CardHeader className="shrink-0 border-b pb-3">
                                <div className="flex items-center justify-between">
                                    <CardTitle className="text-base font-semibold">
                                        Secciones Destino
                                    </CardTitle>
                                    {suggestedGrade &&
                                        manualDestGradeId ===
                                            suggestedGrade.id && (
                                            <Badge className="bg-amber-500 text-xs hover:bg-amber-600">
                                                Sugerido
                                            </Badge>
                                        )}
                                </div>
                                <div className="mt-2">
                                    <Select
                                        value={
                                            manualDestGradeId?.toString() || ''
                                        }
                                        onValueChange={(v) =>
                                            setManualDestGradeId(Number(v))
                                        }
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue placeholder="Seleccione grado destino..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {allActiveGrades.map((g) => (
                                                <SelectItem
                                                    key={g.id}
                                                    value={g.id.toString()}
                                                >
                                                    {g.name}{' '}
                                                    {suggestedGrade &&
                                                    g.id === suggestedGrade.id
                                                        ? '(Sugerido)'
                                                        : ''}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </CardHeader>

                            <CardContent className="flex-1 overflow-auto p-4 lg:p-6">
                                {selectedStudents.length === 0 ? (
                                    <div className="flex h-48 flex-col items-center justify-center space-y-3">
                                        <ArrowRight
                                            className="h-12 w-12 text-neutral-200 dark:text-neutral-700"
                                            strokeWidth={1}
                                        />
                                        <p className="text-sm text-neutral-500">
                                            Selecciona alumnos en el panel
                                            izquierdo.
                                        </p>
                                    </div>
                                ) : !manualDestGradeId ? (
                                    <div className="flex h-48 items-center justify-center text-sm text-amber-600 dark:text-amber-500">
                                        Selecciona un grado destino arriba.
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        <div className="mb-4 flex items-center justify-center">
                                            <Badge
                                                variant="outline"
                                                className="px-3 py-1 text-sm"
                                            >
                                                Promover{' '}
                                                {selectedStudents.length}{' '}
                                                alumno(s)
                                            </Badge>
                                        </div>

                                        <div className="grid gap-3 sm:grid-cols-2">
                                            {destSections.length > 0 ? (
                                                destSections.map((section) => (
                                                    <Button
                                                        key={section.id}
                                                        variant="default"
                                                        className="flex h-auto flex-col items-center gap-1 py-5 shadow-sm transition-shadow hover:shadow-md"
                                                        onClick={() =>
                                                            promoteTo(section)
                                                        }
                                                        disabled={isProcessing}
                                                    >
                                                        <div className="mb-1 flex items-center gap-2">
                                                            <Users className="h-4 w-4" />
                                                            <span className="text-lg font-bold">
                                                                {section.name}
                                                            </span>
                                                        </div>
                                                        <span className="text-xs font-normal opacity-70">
                                                            Clic para promover
                                                            aquí
                                                        </span>
                                                    </Button>
                                                ))
                                            ) : (
                                                <div className="col-span-full rounded-lg border border-dashed border-red-200 bg-red-50 p-6 text-center text-red-600 dark:border-red-900 dark:bg-red-950/30">
                                                    No hay secciones
                                                    configuradas para este
                                                    grado.
                                                </div>
                                            )}
                                        </div>
                                        <InputError
                                            message={errors.user_ids}
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
