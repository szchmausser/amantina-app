import { Head, Link, router } from '@inertiajs/react';
import { Edit, Layers, Plus, Search } from 'lucide-react';
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
import type { BreadcrumbItem } from '@/types';

interface Grade {
    id: number;
    name: string;
}

interface AcademicYear {
    id: number;
    name: string;
}

interface Section {
    id: number;
    name: string;
    grade_id: number;
    academic_year_id: number;
    grade?: Grade;
}

interface Props {
    sections: Section[];
    grades: Grade[];
    academicYears: AcademicYear[];
    selectedYearId: number;
    selectedGradeId: number | null;
}

export default function SectionsIndex({
    sections,
    grades,
    academicYears,
    selectedYearId,
    selectedGradeId,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Grados y Secciones', href: '/admin/grades' },
        { title: 'Secciones', href: '/admin/sections' },
    ];

    const handleFilterChange = (key: string, value: string) => {
        const params: any = { academic_year_id: selectedYearId };
        if (selectedGradeId) params.grade_id = selectedGradeId;
        
        params[key] = value;
        
        router.get('/admin/sections', params, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gestión de Secciones" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                            Secciones del Plantel
                        </h1>
                        <p className="text-sm text-neutral-500">
                            Administra la división de grupos para cada grado y periodo.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={`/admin/sections/create?academic_year_id=${selectedYearId}${selectedGradeId ? `&grade_id=${selectedGradeId}` : ''}`}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nueva Sección
                        </Link>
                    </Button>
                </div>

                <Card className="border-sidebar-border/70 dark:border-sidebar-border shadow-sm">
                    <CardHeader className="pb-3 border-b bg-neutral-50/50 dark:bg-neutral-800/30">
                        <div className="flex items-center gap-2">
                            <Search className="h-4 w-4 text-neutral-400" />
                            <CardTitle className="text-sm font-medium uppercase tracking-tight">Panel de Filtrado</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            <div className="space-y-1.5">
                                <label className="text-[10px] font-bold uppercase tracking-wider text-neutral-400">
                                    Filtrar por Ciclo Lectivo
                                </label>
                                <Select value={selectedYearId.toString()} onValueChange={(v) => handleFilterChange('academic_year_id', v)}>
                                    <SelectTrigger className="h-10">
                                        <SelectValue placeholder="Seleccionar ciclo" />
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
                            <div className="space-y-1.5">
                                <label className="text-[10px] font-bold uppercase tracking-wider text-neutral-400">
                                    Nivel / Grado Académico
                                </label>
                                <Select value={selectedGradeId?.toString() || 'all'} onValueChange={(v) => handleFilterChange('grade_id', v === 'all' ? '' : v)}>
                                    <SelectTrigger className="h-10">
                                        <SelectValue placeholder="Todos los grados" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Ver todos los niveles</SelectItem>
                                        {grades.map((grade) => (
                                            <SelectItem key={grade.id} value={grade.id.toString()}>
                                                {grade.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border shadow-md">
                    <table className="w-full text-left text-sm">
                        <thead className="bg-neutral-50 dark:bg-neutral-800/50 border-b">
                            <tr>
                                <th className="px-6 py-4 font-bold text-neutral-600 dark:text-neutral-300 uppercase tracking-widest text-[10px]">Identificador</th>
                                <th className="px-6 py-4 font-bold text-neutral-600 dark:text-neutral-300 uppercase tracking-widest text-[10px]">Grado Perteneciente</th>
                                <th className="px-6 py-4 font-bold text-neutral-600 dark:text-neutral-300 uppercase tracking-widest text-[10px]">Ciclo Lectivo</th>
                                <th className="px-6 py-4 text-right font-bold text-neutral-600 dark:text-neutral-300 uppercase tracking-widest text-[10px]">Acciones</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-sidebar-border/70">
                            {sections.length > 0 ? (
                                sections.map((section) => (
                                    <tr key={section.id} className="hover:bg-neutral-50/30 transition-all dark:hover:bg-neutral-800/30 group">
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-4">
                                                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50/50 dark:bg-blue-900/10 border border-blue-100/50 dark:border-blue-900/30">
                                                    <Layers className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                                </div>
                                                <span className="text-base font-black text-neutral-900 dark:text-neutral-100 uppercase tracking-widest">
                                                    {section.name}
                                                </span>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <Badge variant="secondary" className="px-3 py-1 text-xs font-bold bg-white border dark:bg-neutral-800">
                                                {section.grade?.name || 'N/A'}
                                            </Badge>
                                        </td>
                                        <td className="px-6 py-4 text-neutral-500 font-semibold tracking-tight">
                                            {academicYears.find(y => y.id === section.academic_year_id)?.name || 'N/A'}
                                        </td>
                                        <td className="px-6 py-4 text-right">
                                            <Button variant="ghost" size="icon" asChild className="h-9 w-9 text-neutral-400 hover:text-blue-600 transition-colors group-hover:bg-blue-50/50">
                                                <Link href={`/admin/sections/${section.id}/edit`}>
                                                    <Edit className="h-4 w-4" />
                                                </Link>
                                            </Button>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={4} className="px-6 py-20 text-center">
                                        <div className="flex flex-col items-center gap-2">
                                            <Layers className="h-10 w-10 text-neutral-200" />
                                            <p className="text-sm font-bold text-neutral-400 uppercase tracking-widest mt-2">No se encontraron registros</p>
                                        </div>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
