import { Head, Link, router } from '@inertiajs/react';
import { Edit, Layers, Plus, Trash2 } from 'lucide-react';
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
import {
    DataTable,
    DataTableHead,
    DataTableTH,
    DataTableBody,
    DataTableTR,
    DataTableTD,
} from '@/components/ui/data-table';

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
        { title: 'Secciones', href: '/admin/sections' },
    ];

    const handleFilterChange = (key: string, value: string) => {
        const params: Record<string, any> = {
            academic_year_id: selectedYearId,
        };
        if (selectedGradeId) params.grade_id = selectedGradeId;

        params[key] = value;

        router.get('/admin/sections', params, { preserveState: true });
    };

    const handleDelete = (id: number) => {
        if (confirm('¿Estás seguro de que deseas eliminar esta sección?')) {
            router.delete(`/admin/sections/${id}`);
        }
    };

    // Columnas de la tabla
    const tableColumns = (
        <>
            <DataTableHead>
                <DataTableTH>Sección</DataTableTH>
                <DataTableTH>Grado</DataTableTH>
                <DataTableTH>Año Escolar</DataTableTH>
                <DataTableTH className="w-24 text-right">Acciones</DataTableTH>
            </DataTableHead>
            <DataTableBody>
                {sections.map((section) => (
                    <DataTableTR key={section.id}>
                        <DataTableTD>
                            <div className="flex items-center gap-3">
                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/20">
                                    <Layers className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                </div>
                                <span className="font-semibold text-neutral-900 uppercase dark:text-neutral-100">
                                    {section.name}
                                </span>
                            </div>
                        </DataTableTD>
                        <DataTableTD>
                            <Badge variant="secondary" className="text-xs">
                                {section.grade?.name || 'N/A'}
                            </Badge>
                        </DataTableTD>
                        <DataTableTD className="text-neutral-500">
                            {academicYears.find(
                                (y) => y.id === section.academic_year_id,
                            )?.name || 'N/A'}
                        </DataTableTD>
                        <DataTableTD className="text-right">
                            <div className="flex items-center justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-8 w-8 text-neutral-500 hover:text-blue-600"
                                    asChild
                                >
                                    <Link
                                        href={`/admin/sections/${section.id}/edit`}
                                    >
                                        <Edit className="h-4 w-4" />
                                        <span className="sr-only">Editar</span>
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-8 w-8 text-red-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/30"
                                    onClick={() => handleDelete(section.id)}
                                >
                                    <Trash2 className="h-4 w-4" />
                                    <span className="sr-only">Eliminar</span>
                                </Button>
                            </div>
                        </DataTableTD>
                    </DataTableTR>
                ))}
            </DataTableBody>
        </>
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gestión de Secciones" />

            <SettingsLayout>
                <div className="flex flex-col gap-6">
                    {/* Encabezado */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                Secciones del Plantel
                            </h1>
                            <p className="text-sm text-neutral-500">
                                Administra la división de grupos para cada grado
                                y periodo.
                            </p>
                        </div>
                        <Button asChild>
                            <Link
                                href={`/admin/sections/create?academic_year_id=${selectedYearId}${selectedGradeId ? `&grade_id=${selectedGradeId}` : ''}`}
                            >
                                <Plus className="mr-2 h-4 w-4" />
                                Nueva Sección
                            </Link>
                        </Button>
                    </div>

                    {/* Filtros en card */}
                    <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                        <CardHeader className="border-b bg-neutral-50/50 pb-3 dark:bg-neutral-800/30">
                            <div className="flex items-center gap-2">
                                <CardTitle className="text-sm font-medium">
                                    Filtros
                                </CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent className="pt-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <label className="text-xs font-semibold tracking-wider text-neutral-500 uppercase">
                                        Año Escolar
                                    </label>
                                    <Select
                                        value={selectedYearId.toString()}
                                        onValueChange={(v) =>
                                            handleFilterChange(
                                                'academic_year_id',
                                                v,
                                            )
                                        }
                                    >
                                        <SelectTrigger className="h-10">
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
                                </div>
                                <div className="space-y-2">
                                    <label className="text-xs font-semibold tracking-wider text-neutral-500 uppercase">
                                        Grado
                                    </label>
                                    <Select
                                        value={
                                            selectedGradeId?.toString() || 'all'
                                        }
                                        onValueChange={(v) =>
                                            handleFilterChange(
                                                'grade_id',
                                                v === 'all' ? '' : v,
                                            )
                                        }
                                    >
                                        <SelectTrigger className="h-10">
                                            <SelectValue placeholder="Todos los grados" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">
                                                Todos los grados
                                            </SelectItem>
                                            {grades.map((grade) => (
                                                <SelectItem
                                                    key={grade.id}
                                                    value={grade.id.toString()}
                                                >
                                                    {grade.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Tabla */}
                    <DataTable
                        data={sections}
                        columns={tableColumns}
                        emptyMessage="No se encontraron secciones para los filtros seleccionados."
                    />
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
