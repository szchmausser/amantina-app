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

interface AcademicYear {
    id: number;
    name: string;
}

interface SchoolTerm {
    id: number;
    academic_year_id: number;
    term_number: number;
    start_date: string;
    end_date: string;
}

interface Props {
    schoolTerm?: SchoolTerm;
    academicYears: AcademicYear[];
}

export default function SchoolTermEdit({ schoolTerm, academicYears }: Props) {
    const isEditing = !!schoolTerm;
    const { url } = usePage();
    const queryParams = new URLSearchParams(url.split('?')[1]);
    const defaultYearId = queryParams.get('academic_year_id');

    const { data, setData, post, put, processing, errors } = useForm({
        academic_year_id: schoolTerm?.academic_year_id || (defaultYearId ? parseInt(defaultYearId) : academicYears[0]?.id),
        term_number: schoolTerm?.term_number || 1,
        start_date: schoolTerm?.start_date || '',
        end_date: schoolTerm?.end_date || '',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Años Escolares', href: '/admin/academic-years' },
        { title: isEditing ? 'Editar Lapso' : 'Nuevo Lapso', href: '#' },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEditing) {
            put(`/admin/school-terms/${schoolTerm.id}`);
        } else {
            post('/admin/school-terms');
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isEditing ? `Editar Lapso ${schoolTerm.term_number}` : 'Nuevo Lapso Académico'} />

            <SettingsLayout>
                <div className="mx-auto max-w-2xl px-4 py-4">
                    <div className="mb-6">
                        <Button variant="ghost" size="sm" asChild className="-ml-2 mb-2 h-8">
                            <Link href={`/admin/academic-years/${data.academic_year_id}`}>
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver al año académico
                            </Link>
                        </Button>
                        <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                            {isEditing ? 'Editar Lapso Académico' : 'Nuevo Lapso Académico'}
                        </h1>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                            Define el periodo de tiempo para este lapso o periodo escolar.
                        </p>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                            <div className="flex items-center gap-2 bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                <Clock className="h-4 w-4 text-neutral-500" />
                                <h2 className="text-sm font-semibold uppercase tracking-wide text-neutral-600 dark:text-neutral-300">
                                    Configuración del Lapso
                                </h2>
                            </div>
                            <div className="grid gap-6 p-6">
                                <div className="space-y-2">
                                    <Label htmlFor="academic_year_id" className="text-[10px] font-bold uppercase tracking-wider text-neutral-400">
                                        Año Académico
                                    </Label>
                                    <Select
                                        value={data.academic_year_id?.toString()}
                                        onValueChange={(val) => setData('academic_year_id', parseInt(val))}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar año" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {academicYears.map((year) => (
                                                <SelectItem key={year.id} value={year.id.toString()}>
                                                    {year.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.academic_year_id} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="term_number" className="text-[10px] font-bold uppercase tracking-wider text-neutral-400">
                                        Número de Lapso
                                    </Label>
                                    <Select
                                        value={data.term_number.toString()}
                                        onValueChange={(val) => setData('term_number', parseInt(val))}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar número" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="1">Lapso 1</SelectItem>
                                            <SelectItem value="2">Lapso 2</SelectItem>
                                            <SelectItem value="3">Lapso 3</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.term_number} />
                                </div>

                                <div className="grid gap-6 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="start_date" className="text-[10px] font-bold uppercase tracking-wider text-neutral-400">
                                            Fecha de Inicio
                                        </Label>
                                        <Input
                                            id="start_date"
                                            type="date"
                                            value={data.start_date}
                                            onChange={(e) => setData('start_date', e.target.value)}
                                            required
                                        />
                                        <InputError message={errors.start_date} />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="end_date" className="text-[10px] font-bold uppercase tracking-wider text-neutral-400">
                                            Fecha de Finalización
                                        </Label>
                                        <Input
                                            id="end_date"
                                            type="date"
                                            value={data.end_date}
                                            onChange={(e) => setData('end_date', e.target.value)}
                                            required
                                        />
                                        <InputError message={errors.end_date} />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="flex items-center justify-end gap-3 border-t pt-6">
                            <Button variant="outline" asChild disabled={processing} className="h-10">
                                <Link href={`/admin/academic-years/${data.academic_year_id}`}>Cancelar</Link>
                            </Button>
                            <Button type="submit" disabled={processing} className="h-10 px-8">
                                {processing ? (
                                    'Guardando...'
                                ) : (
                                    <>
                                        <Save className="mr-2 h-4 w-4" />
                                        {isEditing ? 'Actualizar Lapso' : 'Crear Lapso'}
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

