import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Clock, Save } from 'lucide-react';
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
import { dashboard } from '@/routes';
import { index as schoolTermsIndex } from '@/routes/admin/school-terms';
import {
    store as schoolTermsStore,
    update as schoolTermsUpdate,
} from '@/routes/admin/school-terms';

interface AcademicYear {
    id: number;
    name: string;
}

interface TermType {
    id: number;
    name: string;
    order: number;
}

interface SchoolTerm {
    id: number;
    academic_year_id: number;
    term_type_id: number;
    term_type?: TermType;
    start_date: string;
    end_date: string;
}

interface Props {
    schoolTerm?: SchoolTerm;
    academicYears: AcademicYear[];
    termTypes: TermType[];
}

export default function SchoolTermEdit({
    schoolTerm,
    academicYears,
    termTypes,
}: Props) {
    const isEditing = !!schoolTerm;
    const { url } = usePage();
    const queryParams = new URLSearchParams(url.split('?')[1]);
    const defaultYearId = queryParams.get('academic_year_id');

    const { data, setData, post, put, processing, errors } = useForm({
        academic_year_id:
            schoolTerm?.academic_year_id ||
            (defaultYearId ? parseInt(defaultYearId) : academicYears[0]?.id),
        term_type_id: schoolTerm?.term_type_id || termTypes[0]?.id || null,
        start_date: schoolTerm?.start_date
            ? schoolTerm.start_date.substring(0, 10)
            : '',
        end_date: schoolTerm?.end_date
            ? schoolTerm.end_date.substring(0, 10)
            : '',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Lapsos Académicos', href: schoolTermsIndex().url },
        { title: isEditing ? 'Editar Lapso' : 'Nuevo Lapso', href: '#' },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEditing) {
            put(schoolTermsUpdate(schoolTerm.id).url);
        } else {
            post(schoolTermsStore().url);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={
                    isEditing
                        ? `Editar ${schoolTerm.term_type?.name || 'Lapso'}`
                        : 'Nuevo Lapso Académico'
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
                                    schoolTermsIndex({
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
                                ? 'Editar Lapso Académico'
                                : 'Nuevo Lapso Académico'}
                        </h1>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                            Define el periodo de tiempo para este lapso o
                            periodo escolar.
                        </p>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Card */}
                        <div className="overflow-hidden rounded-xl border">
                            <div className="flex items-center gap-2 border-b bg-neutral-50 px-6 py-4 dark:bg-neutral-800/50">
                                <Clock className="h-4 w-4 text-neutral-500" />
                                <h2 className="text-sm font-semibold">
                                    Configuración del Lapso
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
                                    >
                                        <SelectTrigger>
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
                                    <Label htmlFor="term_type_id">
                                        Tipo de Lapso
                                    </Label>
                                    <Select
                                        value={
                                            data.term_type_id?.toString() || ''
                                        }
                                        onValueChange={(val) =>
                                            setData(
                                                'term_type_id',
                                                parseInt(val),
                                            )
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar tipo de lapso" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {termTypes.map((type) => (
                                                <SelectItem
                                                    key={type.id}
                                                    value={type.id.toString()}
                                                >
                                                    {type.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.term_type_id} />
                                </div>

                                <div className="grid gap-6 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="start_date">
                                            Fecha de Inicio
                                        </Label>
                                        <Input
                                            id="start_date"
                                            type="date"
                                            value={data.start_date}
                                            onChange={(e) =>
                                                setData(
                                                    'start_date',
                                                    e.target.value,
                                                )
                                            }
                                            required
                                        />
                                        <InputError
                                            message={errors.start_date}
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="end_date">
                                            Fecha de Finalización
                                        </Label>
                                        <Input
                                            id="end_date"
                                            type="date"
                                            value={data.end_date}
                                            onChange={(e) =>
                                                setData(
                                                    'end_date',
                                                    e.target.value,
                                                )
                                            }
                                            required
                                        />
                                        <InputError message={errors.end_date} />
                                    </div>
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
                                        schoolTermsIndex({
                                            query: {
                                                academic_year_id:
                                                    data.academic_year_id || 0,
                                            },
                                        }).url
                                    }
                                >
                                    Cancelar
                                </Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? (
                                    'Guardando...'
                                ) : (
                                    <>
                                        <Save className="mr-2 h-4 w-4" />
                                        {isEditing
                                            ? 'Actualizar Lapso'
                                            : 'Crear Lapso'}
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
