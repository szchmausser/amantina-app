import { FormEvent, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, UserSearch } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
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

export default function EnrollmentsCreate({ activeYear, availableStudents, grades }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Inscripciones', href: '/admin/enrollments' },
        { title: 'Nuevo Ingreso', href: '#' },
    ];

    const { data, setData, post, processing, errors } = useForm({
        academic_year_id: activeYear.id,
        user_id: '',
        grade_id: '',
        section_id: '',
    });

    // Helper: Encontrar las secciones del grado seleccionado
    const availableSections = grades.find((g) => g.id.toString() === data.grade_id)?.sections || [];

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/admin/enrollments');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Nuevo Ingreso | ${activeYear.name}`} />

            <div className="mx-auto max-w-2xl p-4 lg:p-8 space-y-6">
                <div className="flex items-center gap-4">
                    <Button variant="outline" size="icon" asChild>
                        <Link href="/admin/enrollments">
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                            Nuevo Ingreso
                        </h1>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                            Inscribe un alumno de forma individual en el año escolar activo ({activeYear.name}).
                        </p>
                    </div>
                </div>

                <Card>
                    <form onSubmit={submit}>
                        <CardHeader>
                            <CardTitle>Datos de la Inscripción</CardTitle>
                            <CardDescription>
                                Solo se muestran los alumnos que no están inscritos en el año activo.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="space-y-2 relative">
                                <Label htmlFor="user_id">Alumno (Nombre - Cédula)</Label>
                                <Select
                                    value={data.user_id}
                                    onValueChange={(v) => setData('user_id', v)}
                                    disabled={processing}
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue placeholder="Seleccione un alumno" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {availableStudents.map((s) => (
                                            <SelectItem key={s.id} value={s.id.toString()}>
                                                {s.name} - {s.cedula}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.user_id} />
                            </div>

                            <div className="grid gap-6 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="grade_id">Grado</Label>
                                    <Select
                                        value={data.grade_id}
                                        onValueChange={(v) => {
                                            setData('grade_id', v);
                                            setData('section_id', ''); // Reset section
                                        }}
                                        disabled={processing}
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue placeholder="Seleccione un grado" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {grades.map((g) => (
                                                <SelectItem key={g.id} value={g.id.toString()}>
                                                    {g.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.grade_id} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="section_id">Sección</Label>
                                    <Select
                                        value={data.section_id}
                                        onValueChange={(v) => setData('section_id', v)}
                                        disabled={processing || !data.grade_id || availableSections.length === 0}
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue placeholder="Seleccione una sección" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {availableSections.map((s) => (
                                                <SelectItem key={s.id} value={s.id.toString()}>
                                                    {s.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.section_id} />
                                </div>
                            </div>

                            {availableStudents.length === 0 && (
                                <div className="rounded-md border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-900/50">
                                    <div className="flex gap-3">
                                        <UserSearch className="h-5 w-5 text-neutral-400" />
                                        <div className="text-sm text-neutral-600 dark:text-neutral-400">
                                            No hay alumnos disponibles para inscribir. Todos los alumnos registrados ya tienen una inscripción activa este año, o aún no se han registrado alumnos en el sistema.
                                        </div>
                                    </div>
                                </div>
                            )}
                        </CardContent>

                        <CardFooter className="flex justify-end gap-2 bg-neutral-50/50 py-4 dark:bg-neutral-900/50">
                            <Button variant="outline" asChild disabled={processing}>
                                <Link href="/admin/enrollments">Cancelar</Link>
                            </Button>
                            <Button type="submit" disabled={processing || availableStudents.length === 0}>
                                {processing ? (
                                    'Inscribiendo...'
                                ) : (
                                    <>
                                        <Save className="mr-2 h-4 w-4" />
                                        Inscribir Alumno
                                    </>
                                )}
                            </Button>
                        </CardFooter>
                    </form>
                </Card>
            </div>
        </AppLayout>
    );
}
