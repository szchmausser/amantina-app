import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Layers, Save } from 'lucide-react';
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
import { index as sectionsIndex } from '@/routes/admin/sections';

interface Grade {
    id: number;
    name: string;
    academic_year_id: number;
}

interface AcademicYear {
    id: number;
    name: string;
}

interface Section {
    id: number;
    grade_id: number;
    academic_year_id: number;
    name: string;
}

interface Props {
    section?: Section;
    grades: Grade[];
    academicYears: AcademicYear[];
}

export default function SectionEdit({ section, grades, academicYears }: Props) {
    const isEditing = !!section;
    const { url } = usePage();
    const queryParams = new URLSearchParams(url.split('?')[1]);
    const defaultGradeId = queryParams.get('grade_id');
    const defaultYearId = queryParams.get('academic_year_id');

    const { data, setData, post, put, processing, errors } = useForm({
        academic_year_id:
            section?.academic_year_id ||
            (defaultYearId
                ? parseInt(defaultYearId)
                : grades[0]?.academic_year_id || academicYears[0]?.id),
        grade_id:
            section?.grade_id ||
            (defaultGradeId ? parseInt(defaultGradeId) : grades[0]?.id),
        name: section?.name || '',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Secciones', href: sectionsIndex().url },
        { title: isEditing ? 'Editar Sección' : 'Nueva Sección', href: '#' },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEditing) {
            put(`/admin/sections/${section.id}`);
        } else {
            post('/admin/sections');
        }
    };

    const filteredGrades = grades.filter(
        (g) => g.academic_year_id === data.academic_year_id,
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={
                    isEditing
                        ? `Editar Sección: ${section.name}`
                        : 'Nueva Sección'
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
                                    sectionsIndex({
                                        query: {
                                            academic_year_id:
                                                data.academic_year_id,
                                            grade_id: data.grade_id,
                                        },
                                    }).url
                                }
                            >
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver al listado
                            </Link>
                        </Button>
                        <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                            {isEditing ? 'Editar Sección' : 'Nueva Sección'}
                        </h1>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                            Asigna un identificador (ej: A) a un grado
                            específico.
                        </p>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Card */}
                        <div className="overflow-hidden rounded-xl border">
                            <div className="flex items-center gap-2 border-b bg-neutral-50 px-6 py-4 dark:bg-neutral-800/50">
                                <Layers className="h-4 w-4 text-neutral-500" />
                                <h2 className="text-sm font-semibold">
                                    Configuración de Sección
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
                                    <Label htmlFor="grade_id">
                                        Grado Académico
                                    </Label>
                                    <Select
                                        value={data.grade_id?.toString()}
                                        onValueChange={(val) =>
                                            setData('grade_id', parseInt(val))
                                        }
                                        data-test="grade-select"
                                    >
                                        <SelectTrigger data-test="grade-select-trigger">
                                            <SelectValue placeholder="Seleccionar grado" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {filteredGrades.map((grade) => (
                                                <SelectItem
                                                    key={grade.id}
                                                    value={grade.id.toString()}
                                                >
                                                    {grade.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.grade_id} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="name">
                                        Nombre de la Sección
                                    </Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) =>
                                            setData('name', e.target.value)
                                        }
                                        placeholder="Ej: A"
                                        required
                                        data-test="section-name-input"
                                    />
                                    <InputError message={errors.name} />
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
                                        sectionsIndex({
                                            query: {
                                                academic_year_id:
                                                    data.academic_year_id,
                                                grade_id: data.grade_id,
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
                                            ? 'Actualizar Sección'
                                            : 'Crear Sección'}
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
