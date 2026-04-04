import { FormEvent, useMemo, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Search, UserSearch, Check } from 'lucide-react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
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

interface AvailableStudent {
    id: number;
    name: string;
    cedula: string;
}

interface Section {
    id: number;
    name: string;
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
    availableStudents: AvailableStudent[];
    grades: Grade[];
}

export default function EnrollmentsCreate({
    activeYear,
    availableStudents,
    grades,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Inscripciones', href: '/admin/enrollments' },
        { title: 'Nuevo Ingreso', href: '#' },
    ];

    const { errors } = usePage().props;

    const [searchQuery, setSearchQuery] = useState('');
    const [selectedStudents, setSelectedStudents] = useState<number[]>([]);
    const [isProcessing, setIsProcessing] = useState(false);

    const [destGradeId, setDestGradeId] = useState<number | null>(null);

    const filteredStudents = useMemo(() => {
        if (!searchQuery) return availableStudents;
        const q = searchQuery.toLowerCase();
        return availableStudents.filter(
            (s) =>
                s.name.toLowerCase().includes(q) ||
                s.cedula.toLowerCase().includes(q),
        );
    }, [availableStudents, searchQuery]);

    const areAllFilteredSelected =
        filteredStudents.length > 0 &&
        filteredStudents.every((s) => selectedStudents.includes(s.id));

    const toggleAll = () => {
        if (areAllFilteredSelected) {
            const filteredIds = filteredStudents.map((s) => s.id);
            setSelectedStudents(
                selectedStudents.filter((id) => !filteredIds.includes(id)),
            );
        } else {
            const newIds = [...selectedStudents];
            filteredStudents.forEach((s) => {
                if (!newIds.includes(s.id)) newIds.push(s.id);
            });
            setSelectedStudents(newIds);
        }
    };

    const toggleStudent = (userId: number) => {
        if (selectedStudents.includes(userId)) {
            setSelectedStudents(selectedStudents.filter((id) => id !== userId));
        } else {
            setSelectedStudents([...selectedStudents, userId]);
        }
    };

    const enrollTo = (destSection: Section) => {
        if (selectedStudents.length === 0) return;

        if (
            confirm(
                `¿Inscribir ${selectedStudents.length} alumnos de nuevo ingreso en ${destSection.name}?`,
            )
        ) {
            router.post(
                '/admin/enrollments',
                {
                    academic_year_id: activeYear.id,
                    user_ids: selectedStudents,
                    grade_id: destGradeId?.toString() || '',
                    section_id: destSection.id.toString(),
                },
                {
                    preserveScroll: true,
                    onStart: () => setIsProcessing(true),
                    onFinish: () => setIsProcessing(false),
                    onSuccess: () => {
                        setSelectedStudents([]);
                        setSearchQuery('');
                    },
                },
            );
        }
    };

    const destSections =
        grades.find((g) => g.id === destGradeId)?.sections || [];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Nuevo Ingreso | ${activeYear.name}`} />

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
                                Nuevo Ingreso
                            </h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Inscribir alumnos de nuevo ingreso al año
                                escolar activo ({activeYear.name}).
                            </p>
                        </div>
                    </div>

                    <div className="grid h-full min-h-0 grid-cols-1 gap-6 md:grid-cols-2">
                        {/* Panel Izquierdo: Alumnos Sin Inscripción */}
                        <Card className="flex min-h-0 flex-col overflow-hidden">
                            <CardHeader className="shrink-0 border-b pb-3">
                                <CardTitle className="text-base font-semibold">
                                    Alumnos Disponibles
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

                            <CardContent className="flex-1 overflow-auto p-0">
                                {availableStudents.length > 0 ? (
                                    <div className="divide-y">
                                        <div className="sticky top-0 z-10 flex items-center justify-between bg-neutral-50 p-3 dark:bg-neutral-800/30">
                                            <div className="flex items-center gap-2">
                                                <Checkbox
                                                    checked={
                                                        areAllFilteredSelected
                                                    }
                                                    onCheckedChange={toggleAll}
                                                    disabled={
                                                        filteredStudents.length ===
                                                        0
                                                    }
                                                />
                                                <span className="text-sm font-medium">
                                                    Seleccionar de la lista
                                                </span>
                                            </div>
                                            {selectedStudents.length > 0 && (
                                                <Badge
                                                    variant="secondary"
                                                    className="text-xs"
                                                >
                                                    {selectedStudents.length}{' '}
                                                    seleccionados
                                                </Badge>
                                            )}
                                        </div>
                                        <div className="p-2">
                                            {filteredStudents.length > 0 ? (
                                                filteredStudents.map(
                                                    (s, index) => {
                                                        const isSelected =
                                                            selectedStudents.includes(
                                                                s.id,
                                                            );
                                                        return (
                                                            <label
                                                                key={s.id}
                                                                className={`flex items-center gap-3 rounded-lg p-3 transition-colors ${
                                                                    isSelected
                                                                        ? 'bg-neutral-100 dark:bg-neutral-800'
                                                                        : 'cursor-pointer hover:bg-neutral-50 dark:hover:bg-neutral-800/50'
                                                                }`}
                                                            >
                                                                <span className="w-5 shrink-0 text-right font-mono text-[10px] text-neutral-400">
                                                                    {index + 1}
                                                                </span>
                                                                <div
                                                                    className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold ${
                                                                        isSelected
                                                                            ? 'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900'
                                                                            : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400'
                                                                    }`}
                                                                >
                                                                    {s.name.charAt(
                                                                        0,
                                                                    )}
                                                                </div>
                                                                <div className="min-w-0 flex-1">
                                                                    <div className="truncate text-sm font-medium">
                                                                        {s.name}
                                                                    </div>
                                                                    <div className="font-mono text-xs text-neutral-500">
                                                                        {
                                                                            s.cedula
                                                                        }
                                                                    </div>
                                                                </div>
                                                                <Checkbox
                                                                    checked={
                                                                        isSelected
                                                                    }
                                                                    onCheckedChange={() =>
                                                                        toggleStudent(
                                                                            s.id,
                                                                        )
                                                                    }
                                                                />
                                                            </label>
                                                        );
                                                    },
                                                )
                                            ) : (
                                                <div className="py-8 text-center text-sm text-neutral-500">
                                                    No hay resultados para "
                                                    {searchQuery}"
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                ) : (
                                    <div className="flex h-full flex-col items-center justify-center p-8 text-center text-sm text-neutral-500">
                                        <UserSearch className="mb-2 h-10 w-10 opacity-20" />
                                        No hay alumnos pendientes por
                                        inscripción.
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Panel Derecho: Destino */}
                        <Card className="flex min-h-0 flex-col overflow-hidden">
                            <CardHeader className="shrink-0 border-b pb-3">
                                <CardTitle className="text-base font-semibold">
                                    Secciones Destino
                                </CardTitle>
                                <div className="mt-2">
                                    <Select
                                        value={destGradeId?.toString() || ''}
                                        onValueChange={(v) =>
                                            setDestGradeId(Number(v))
                                        }
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue placeholder="Seleccione grado destino..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {grades.map((g) => (
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
                                ) : !destGradeId ? (
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
                                                Inscribir{' '}
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
                                                            enrollTo(section)
                                                        }
                                                        disabled={isProcessing}
                                                    >
                                                        <span className="text-lg font-bold">
                                                            {section.name}
                                                        </span>
                                                        <span className="text-xs font-normal opacity-70">
                                                            Clic para inscribir
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
                                            message={
                                                errors?.user_ids ||
                                                errors?.grade_id ||
                                                errors?.section_id
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
