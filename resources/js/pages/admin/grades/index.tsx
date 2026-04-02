import { Head, Link, router } from '@inertiajs/react';
import { Edit, GraduationCap, Plus, Search } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
}

interface Grade {
    id: number;
    name: string;
    order: number;
    academic_year_id: number;
    sections: Section[];
}

interface AcademicYear {
    id: number;
    name: string;
}

interface Props {
    grades: Grade[];
    academicYears: AcademicYear[];
    selectedYearId: number;
}

export default function GradesIndex({ grades, academicYears, selectedYearId }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Grados y Secciones', href: '/admin/grades' },
    ];

    const handleYearChange = (yearId: string) => {
        router.get('/admin/grades', { academic_year_id: yearId }, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gestión de Grados" />

            <SettingsLayout>
                <div className="flex flex-col gap-6">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                Grados Académicos
                            </h1>
                            <p className="text-sm text-neutral-500">
                                Configura los niveles y secciones del plantel por año escolar.
                            </p>
                        </div>
                        <Button asChild>
                            <Link href={`/admin/grades/create?academic_year_id=${selectedYearId}`}>
                                <Plus className="mr-2 h-4 w-4" />
                                Nuevo Grado
                            </Link>
                        </Button>
                    </div>

                    <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                        <CardHeader className="pb-3 border-b bg-neutral-50/50 dark:bg-neutral-800/30">
                            <div className="flex items-center gap-2">
                                <Search className="h-4 w-4 text-neutral-400" />
                                <CardTitle className="text-sm font-medium">Búsqueda y Filtros</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent className="pt-6">
                            <div className="flex flex-wrap items-center gap-4">
                                <div className="w-full max-w-xs space-y-1.5">
                                    <label className="text-[10px] font-bold uppercase tracking-wider text-neutral-400">
                                        Filtrar por Año Escolar
                                    </label>
                                    <Select value={selectedYearId.toString()} onValueChange={handleYearChange}>
                                        <SelectTrigger className="h-10">
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
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="grid gap-4 md:grid-cols-2">
                        {grades.length > 0 ? (
                            grades.map((grade) => (
                                <Card key={grade.id} className="overflow-hidden border-sidebar-border/70 group dark:border-sidebar-border">
                                    <CardHeader className="bg-neutral-50/50 pb-3 transition-colors group-hover:bg-neutral-100/50 dark:bg-neutral-800/30 dark:group-hover:bg-neutral-800/50">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-3">
                                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-white shadow-sm border dark:bg-neutral-800">
                                                    <GraduationCap className="h-4 w-4 text-blue-500" />
                                                </div>
                                                <div>
                                                    <CardTitle className="text-base font-bold">{grade.name}</CardTitle>
                                                    <p className="text-[10px] text-neutral-500 uppercase">Orden: {grade.order}</p>
                                                </div>
                                            </div>
                                            <Button variant="ghost" size="icon" className="h-8 w-8 text-neutral-400 hover:text-neutral-900" asChild>
                                                <Link href={`/admin/grades/${grade.id}/edit`}>
                                                    <Edit className="h-3.5 w-3.5" />
                                                </Link>
                                            </Button>
                                        </div>
                                    </CardHeader>
                                    <CardContent className="pt-4">
                                        <div className="space-y-4">
                                            <div className="flex items-center justify-between">
                                                <span className="text-[10px] font-bold uppercase tracking-wider text-neutral-400">Secciones Registradas</span>
                                                <Badge variant="outline" className="h-5 px-2 text-[10px] font-bold bg-neutral-50">
                                                    {grade.sections?.length || 0}
                                                </Badge>
                                            </div>
                                            <div className="flex flex-wrap gap-2">
                                                {grade.sections?.map((section) => (
                                                    <Link key={section.id} href={`/admin/sections/${section.id}`}>
                                                        <Badge
                                                            variant="secondary"
                                                            className="px-2.5 py-1 text-xs transition-colors hover:bg-neutral-200 dark:hover:bg-neutral-700 cursor-pointer"
                                                        >
                                                            {section.name}
                                                        </Badge>
                                                    </Link>
                                                ))}
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="h-7 px-2 border-dashed text-[10px] transition-all hover:border-solid"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/admin/sections/create?grade_id=${grade.id}&academic_year_id=${grade.academic_year_id}`}
                                                    >
                                                        <Plus className="mr-1 h-3 w-3" />
                                                        Añadir Sección
                                                    </Link>
                                                </Button>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))
                        ) : (
                            <div className="col-span-full flex flex-col items-center justify-center rounded-xl border border-dashed border-neutral-200 py-20 dark:border-neutral-800">
                                <GraduationCap className="h-10 w-10 text-neutral-200 mb-4" />
                                <p className="text-sm font-medium text-neutral-500">No se encontraron grados para este periodo.</p>
                                <Button variant="link" asChild className="mt-2">
                                    <Link href={`/admin/grades/create?academic_year_id=${selectedYearId}`}>
                                        Registra el primer grado aquí
                                    </Link>
                                </Button>
                            </div>
                        )}
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
