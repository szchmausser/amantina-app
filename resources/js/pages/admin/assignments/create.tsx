import { useEffect, useMemo, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, BookUser, Search, Users } from 'lucide-react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem } from '@/types';

interface Teacher {
    id: number;
    name: string;
    cedula: string;
}

interface TeacherAssignment {
    id: number;
    teacher: {
        id: number;
        name: string;
    };
}

interface Section {
    id: number;
    name: string;
    enrollments_count: number;
    teacher_assignments: TeacherAssignment[];
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
    availableTeachers: Teacher[];
    grades: Grade[];
}

export default function TeacherAssignmentsCreate({ activeYear, availableTeachers, grades }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Asignaciones Docentes', href: '/admin/teacher-assignments' },
        { title: 'Consola de Asignación', href: '#' },
    ];

    const { errors } = usePage().props;
    
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedTeacherId, setSelectedTeacherId] = useState<number | null>(null);
    const [selectedSections, setSelectedSections] = useState<number[]>([]);
    const [isProcessing, setIsProcessing] = useState(false);

    // Filtrado de profesores
    const filteredTeachers = useMemo(() => {
        if (!searchQuery) return availableTeachers;
        const q = searchQuery.toLowerCase();
        return availableTeachers.filter(t => 
            t.name.toLowerCase().includes(q) || 
            t.cedula.toLowerCase().includes(q)
        );
    }, [availableTeachers, searchQuery]);

    // Cuando se selecciona un profesor, pre-llenar las secciones que ya tiene asignadas
    useEffect(() => {
        if (!selectedTeacherId) {
            setSelectedSections([]);
            return;
        }
        
        const currentIds: number[] = [];
        grades.forEach(g => {
            g.sections.forEach(s => {
                if (s.teacher_assignments?.some(ta => ta.teacher.id === selectedTeacherId)) {
                    currentIds.push(s.id);
                }
            });
        });
        setSelectedSections(currentIds);
    }, [selectedTeacherId, grades]);

    const toggleSection = (sectionId: number) => {
        if (selectedSections.includes(sectionId)) {
            setSelectedSections(selectedSections.filter(id => id !== sectionId));
        } else {
            setSelectedSections([...selectedSections, sectionId]);
        }
    };

    const isDirty = useMemo(() => {
        if (!selectedTeacherId) return false;
        
        // Comparar lo que tiene vs lo que el usuario ha checkeado
        const originalIds: number[] = [];
        grades.forEach(g => {
            g.sections.forEach(s => {
                if (s.teacher_assignments?.some(ta => ta.teacher.id === selectedTeacherId)) {
                    originalIds.push(s.id);
                }
            });
        });

        if (originalIds.length !== selectedSections.length) return true;
        
        const sortedOriginal = [...originalIds].sort();
        const sortedSelected = [...selectedSections].sort();
        
        return sortedOriginal.some((id, index) => id !== sortedSelected[index]);
    }, [selectedTeacherId, selectedSections, grades]);

    const saveAssignments = () => {
        if (!selectedTeacherId) return;
        
        const teacher = availableTeachers.find(t => t.id === selectedTeacherId);
        
        if (confirm(`¿Guardar la asignación de ${selectedSections.length} sección(es) para el Prof. ${teacher?.name}?`)) {
            router.post('/admin/teacher-assignments', {
                academic_year_id: activeYear.id,
                user_id: selectedTeacherId.toString(),
                section_ids: selectedSections, // Bulk post
            }, {
                preserveScroll: true,
                onStart: () => setIsProcessing(true),
                onFinish: () => setIsProcessing(false),
            });
        }
    };

    const getAssignedTeacherNames = (section: Section): string => {
        if (!section.teacher_assignments || section.teacher_assignments.length === 0) return 'Ninguno';
        return section.teacher_assignments.map(ta => ta.teacher.name).join(', ');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Asignar Profesores | ${activeYear.name}`} />

            <SettingsLayout>
                <div className="flex flex-col gap-6 h-[calc(100vh-10rem)] overflow-hidden">
                    <div className="flex items-center gap-4 shrink-0">
                        <Button variant="outline" size="icon" asChild>
                            <Link href="/admin/teacher-assignments">
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                Consola de Asignación Docente
                            </h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Gestiona rápidamente qué secciones imparte cada profesor en el año activo ({activeYear.name}).
                            </p>
                        </div>
                    </div>

                    <div className="grid h-full grid-cols-1 md:grid-cols-[1fr_2fr] gap-6 min-h-0">
                        {/* Panel Izquierdo: Lista de Profesores */}
                        <Card className="flex flex-col min-h-0 border-primary/20 bg-neutral-50/50 dark:bg-neutral-800/10">
                            <CardHeader className="shrink-0 bg-neutral-100/50 dark:bg-neutral-800/50 border-b">
                                <CardTitle className="text-lg flex items-center gap-2">
                                    <span className="bg-primary/10 text-primary rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                                    Seleccionar Profesor
                                </CardTitle>
                                
                                <div className="mt-4 relative">
                                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-neutral-400" />
                                    <Input 
                                        className="pl-9 bg-white dark:bg-neutral-900" 
                                        placeholder="Buscar por cédula o nombre..." 
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                    />
                                </div>
                            </CardHeader>
                            
                            <CardContent className="flex-1 overflow-auto p-0">
                                {availableTeachers.length > 0 ? (
                                    <div className="divide-y divide-neutral-200 dark:divide-neutral-800 p-2">
                                        {filteredTeachers.length > 0 ? (
                                            filteredTeachers.map((t) => {
                                                const isSelected = selectedTeacherId === t.id;
                                                return (
                                                    <button
                                                        key={t.id}
                                                        onClick={() => setSelectedTeacherId(t.id)}
                                                        className={`w-full flex text-left items-center gap-3 p-3 rounded-lg transition-colors border ${
                                                            isSelected 
                                                                ? 'bg-primary/10 border-primary shadow-sm' 
                                                                : 'border-transparent hover:bg-neutral-100 dark:hover:bg-neutral-800'
                                                        }`}
                                                    >
                                                        <div className="flex-1">
                                                            <div className={`font-medium text-sm ${isSelected ? 'text-primary' : ''}`}>{t.name}</div>
                                                            <div className="text-xs text-neutral-500 font-mono">{t.cedula}</div>
                                                        </div>
                                                    </button>
                                                );
                                            })
                                        ) : (
                                            <div className="p-8 text-center text-sm text-neutral-500">
                                                No hay resultados para "{searchQuery}"
                                            </div>
                                        )}
                                    </div>
                                ) : (
                                    <div className="flex h-full flex-col items-center justify-center p-8 text-neutral-500 text-sm text-center">
                                        <BookUser className="h-10 w-10 mb-2 opacity-20" />
                                        No hay profesores registrados en el sistema.
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Panel Derecho: Grilla de Secciones */}
                        <Card className="flex flex-col min-h-0 border-primary/20 bg-primary/5 dark:bg-primary/5">
                            <CardHeader className="shrink-0 bg-primary/10 border-b border-primary/10 flex flex-row items-center justify-between py-4">
                                <CardTitle className="text-lg flex items-center gap-2 m-0">
                                    <span className="bg-primary text-primary-foreground rounded-full w-6 h-6 flex items-center justify-center text-sm">2</span>
                                    Configurar Secciones Asignadas
                                </CardTitle>
                                
                                <Button 
                                    onClick={saveAssignments} 
                                    disabled={!selectedTeacherId || !isDirty || isProcessing}
                                    className="ml-auto"
                                >
                                    {isProcessing ? 'Guardando...' : 'Guardar Cambios'}
                                </Button>
                            </CardHeader>

                            <CardContent className="flex-1 overflow-auto p-4 lg:p-6">
                                {!selectedTeacherId ? (
                                    <div className="flex flex-col items-center justify-center h-full space-y-3">
                                        <BookUser className="h-16 w-16 text-primary/20" strokeWidth={1} />
                                        <p className="text-neutral-500 font-medium">Seleccione un profesor en el panel izquierdo.</p>
                                    </div>
                                ) : grades.length === 0 ? (
                                    <div className="flex h-full items-center justify-center text-neutral-500">
                                        No hay grados ni secciones configuradas en el año escolar activo.
                                    </div>
                                ) : (
                                    <div className="space-y-8">
                                        {grades.map(grade => (
                                            <div key={grade.id} className="space-y-3">
                                                <h3 className="font-semibold text-lg border-b border-neutral-200 dark:border-neutral-800 pb-1">
                                                    {grade.name}
                                                </h3>
                                                
                                                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                                    {grade.sections.length > 0 ? (
                                                        grade.sections.map(section => {
                                                            const isChecked = selectedSections.includes(section.id);
                                                            
                                                            return (
                                                                <div 
                                                                    key={section.id}
                                                                    onClick={() => toggleSection(section.id)}
                                                                    className={`relative flex flex-col p-4 rounded-xl border cursor-pointer transition-all ${
                                                                        isChecked 
                                                                        ? 'bg-white dark:bg-neutral-900 border-primary ring-1 ring-primary shadow-md' 
                                                                        : 'bg-white/60 dark:bg-neutral-900/60 border-neutral-200 dark:border-neutral-800 hover:border-primary/50'
                                                                    }`}
                                                                >
                                                                    <div className="flex items-start justify-between mb-2">
                                                                        <div className="font-bold text-base">{section.name}</div>
                                                                        <Checkbox 
                                                                            checked={isChecked}
                                                                            onCheckedChange={() => toggleSection(section.id)}
                                                                            // pointer-events-none makes the entire card click handle the toggle easily
                                                                            className="pointer-events-none mt-1" 
                                                                        />
                                                                    </div>
                                                                    
                                                                    <div className="space-y-1 mt-auto">
                                                                        <div className="flex items-center gap-1.5 text-xs text-neutral-600 dark:text-neutral-400">
                                                                            <Users className="h-3.5 w-3.5" />
                                                                            {section.enrollments_count ?? 0} alumno(s)
                                                                        </div>
                                                                        
                                                                        <div className="text-xs text-neutral-500 mt-2 border-t pt-2">
                                                                            <span className="font-medium text-neutral-700 dark:text-neutral-300">
                                                                                Profesores asignados:{' '}
                                                                            </span>
                                                                            {getAssignedTeacherNames(section)}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            );
                                                        })
                                                    ) : (
                                                        <div className="text-sm text-neutral-500 py-2">
                                                            Sin secciones para este grado.
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                        
                                        <InputError message={errors?.user_id || errors?.section_ids} className="text-center mt-4" />
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
