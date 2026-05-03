import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, GraduationCap, Save } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem } from '@/types';
import { index as gradesIndex } from '@/routes/admin/grades';

interface AcademicYear {
    id: number;
    name: string;
}

interface Grade {
    id: number;
    academic_year_id: number;
    name: string;
    order: number;
}

interface Props {
    grade?: Grade;
    academicYears: AcademicYear[];
}

export default function GradeEdit({ grade, academicYears }: Props) {
    const isEditing = !!grade;
    const { url } = usePage();
    const queryParams = new URLSearchParams(url.split('?')[1]);
    const defaultYearId = queryParams.get('academic_year_id');

    const { data, setData, post, put, processing, errors } = useForm({
        academic_year_id:
            grade?.academic_year_id ||
            (defaultYearId ? parseInt(defaultYearId) : academicYears[0]?.id),
        name: grade?.name || '',
        order: grade?.order || 1,
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Grados', href: gradesIndex().url },
        { title: isEditing ? 'Editar Grado' : 'Nuevo Grado', href: '#' },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEditing) {
            put(`/admin/grades/${grade.id}`);
        } else {
            post('/admin/grades');
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={
                    isEditing
                        ? `Editar Grado: ${grade.name}`
                        : 'Nuevo Grado Académico'
                }
            />

            <SettingsLayout>
                <div className="px-4 py-4">
                    {/* Header */}
                    <div className="mb-6">
                        <Button
                            variant="ghost"
                            size="sm"
                            asChild
                            className="mb-2 -ml-2 h-8"
                        >
                            <Link
                                href={
                                    gradesIndex({
                                        query: {
                                            academic_year_id:
                                                data.academic_year_id,
                                        },
                                    }).url
                                }
                            >
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver al listado
                            </Link>
                        </Button>
                        <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                            {isEditing
                                ? 'Editar Grado Académico'
                                : 'Nuevo Grado Académico'}
                        </h1>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                            Configura un nivel educativo (ej: 1er Año) para el
                            ciclo seleccionado.
                        </p>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Card */}
                        <div className="overflow-hidden rounded-xl border">
                            <div className="flex items-center gap-2 border-b bg-neutral-50 px-6 py-4 dark:bg-neutral-800/50">
                                <GraduationCap className="h-4 w-4 text-neutral-500" />
                                <h2 className="text-sm font-semibold">
                                    Datos del Grado
                                </h2>
                            </div>
                            <div className="grid gap-6 p-6">
                                <div className="space-y-2">
                                    <Label htmlFor="academic_year_id">
                                        Año Académico
                                    </Label>
                                    <Select
                                        value={data.academic_year_id?.toString()}
                                        onValueChange={(val) =>
                                            setData(
                                                'academic_year_id',
                                                parseInt(val),
                                            )
                                        }
                                        data-test="academic-year-select"
                                    >
                                        <SelectTrigger data-test="academic-year-select-trigger">
                                            <SelectValue placeholder="Seleccionar año" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {academicYears.map((year) => (
                                                <SelectItem
                                                    key={year.id}
                                                    value={year.id.toString()}
                                                >
                                                    {year.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={errors.academic_year_id}
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="name">
                                        Nombre del Grado
                                    </Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) =>
                                            setData('name', e.target.value)
                                        }
                                        placeholder="Ej: 1er Año"
                                        required
                                        data-test="grade-name-input"
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="order">
                                        Orden de Visualización
                                    </Label>
                                    <Input
                                        id="order"
                                        type="number"
                                        value={data.order}
                                        onChange={(e) =>
                                            setData(
                                                'order',
                                                parseInt(
                                                    e.target.value.toString(),
                                                ),
                                            )
                                        }
                                        placeholder="Ej: 1"
                                        required
                                        data-test="grade-order-input"
                                    />
                                    <p className="text-xs text-neutral-500">
                                        Define la secuencia en la que aparecerán
                                        los grados.
                                    </p>
                                    <InputError message={errors.order} />
                                </div>
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="flex items-center justify-end gap-3">
                            <Button
                                variant="outline"
                                asChild
                                disabled={processing}
                            >
                                <Link
                                    href={
                                        gradesIndex({
                                            query: {
                                                academic_year_id:
                                                    data.academic_year_id,
                                            },
                                        }).url
                                    }
                                >
                                    Cancelar
                                </Link>
                            </Button>
                            <Button type="submit" disabled={processing} data-test="submit-button">
                                {processing ? (
                                    'Guardando...'
                                ) : (
                                    <>
                                        <Save className="mr-2 h-4 w-4" />
                                        {isEditing
                                            ? 'Actualizar Grado'
                                            : 'Crear Grado'}
                                    </>
                                )}
                            </Button>
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
