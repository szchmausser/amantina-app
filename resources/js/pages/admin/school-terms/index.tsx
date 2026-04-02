import { Head, Link, router } from '@inertiajs/react';
import { formatDate } from '@/lib/utils';
import { Clock, Edit, Plus, Search } from 'lucide-react';
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

interface SchoolTerm {
    id: number;
    term_number: number;
    start_date: string;
    end_date: string;
    academic_year_id: number;
}

interface AcademicYear {
    id: number;
    name: string;
}

interface Props {
    schoolTerms: SchoolTerm[];
    academicYears: AcademicYear[];
    selectedYearId: number;
}

export default function SchoolTermsIndex({
    schoolTerms,
    academicYears,
    selectedYearId,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Años Escolares', href: '/admin/academic-years' },
        { title: 'Lapsos Académicos', href: '/admin/school-terms' },
    ];

    const handleYearChange = (yearId: string) => {
        router.get('/admin/school-terms', { academic_year_id: yearId }, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gestión de Lapsos" />

            <SettingsLayout>
                <div className="flex flex-col gap-6">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                Lapsos Académicos
                            </h1>
                            <p className="text-sm text-neutral-500">
                                Administra los periodos de evaluación para cada ciclo escolar.
                            </p>
                        </div>
                        <Button asChild>
                            <Link href={`/admin/school-terms/create?academic_year_id=${selectedYearId}`}>
                                <Plus className="mr-2 h-4 w-4" />
                                Nuevo Lapso
                            </Link>
                        </Button>
                    </div>

                    <Card className="border-sidebar-border/70 dark:border-sidebar-border shadow-sm">
                        <CardHeader className="pb-3 border-b bg-neutral-50/50 dark:bg-neutral-800/30">
                            <div className="flex items-center gap-2">
                                <Search className="h-4 w-4 text-neutral-400" />
                                <CardTitle className="text-sm font-medium uppercase tracking-tight">Filtro por Ciclo</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent className="pt-6">
                            <div className="w-full max-w-xs space-y-1.5">
                                <label className="text-[10px] font-bold uppercase tracking-wider text-neutral-400">
                                    Seleccionar Año Escolar
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
                        </CardContent>
                    </Card>

                    <div className="grid gap-6 md:grid-cols-2">
                        {schoolTerms.length > 0 ? (
                            schoolTerms.map((term) => (
                                <Card key={term.id} className="overflow-hidden border-sidebar-border/70 group dark:border-sidebar-border">
                                    <CardHeader className="bg-neutral-50/50 pb-4 dark:bg-neutral-800/30">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-3">
                                                <div className="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100/50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400 border border-blue-200/50 dark:border-blue-900/30">
                                                    <span className="text-lg font-black">{term.term_number}</span>
                                                </div>
                                                <div>
                                                    <CardTitle className="text-lg font-bold uppercase tracking-tight">Lapso {term.term_number}</CardTitle>
                                                    <p className="text-[10px] font-bold text-neutral-500 uppercase">Periodo de Evaluación</p>
                                                </div>
                                            </div>
                                            <Button variant="ghost" size="icon" asChild className="h-9 w-9 text-neutral-400 hover:text-blue-600">
                                                <Link href={`/admin/school-terms/${term.id}/edit`}>
                                                    <Edit className="h-4.5 w-4.5" />
                                                </Link>
                                            </Button>
                                        </div>
                                    </CardHeader>
                                    <CardContent className="pt-6 space-y-6">
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="space-y-1">
                                                <p className="text-[10px] font-bold text-neutral-400 uppercase tracking-widest font-mono">Inicio</p>
                                                <div className="flex items-center gap-2 font-mono">
                                                    <Clock className="h-3 w-3 text-neutral-400" />
                                                    <span className="text-sm font-semibold">{formatDate(term.start_date)}</span>
                                                </div>
                                            </div>
                                            <div className="space-y-1 font-mono">
                                                <p className="text-[10px] font-bold text-neutral-400 uppercase tracking-widest">Cierre</p>
                                                <div className="flex items-center gap-2">
                                                    <Clock className="h-3 w-3 text-neutral-400" />
                                                    <span className="text-sm font-semibold">{formatDate(term.end_date)}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="pt-4 border-t border-sidebar-border/50">
                                            <Badge variant="outline" className="text-[10px] font-bold uppercase tracking-widest text-neutral-400 border-dashed">
                                                {academicYears.find(y => y.id === term.academic_year_id)?.name}
                                            </Badge>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))
                        ) : (
                            <div className="col-span-full flex flex-col items-center justify-center rounded-xl border border-dashed border-neutral-200 py-20 dark:border-neutral-800">
                                <Clock className="h-10 w-10 text-neutral-200 mb-4" />
                                <p className="text-sm font-bold text-neutral-400 uppercase tracking-widest">No hay lapsos configurados</p>
                            </div>
                        )}
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
