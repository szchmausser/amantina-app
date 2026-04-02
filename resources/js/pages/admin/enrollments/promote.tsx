import { FormEvent, useEffect, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, CheckCircle2, GraduationCap, X } from 'lucide-react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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

// Tipos simplificados para las props
interface Section { id: number; name: string; grade_id: number; }
interface Grade { id: number; name: string; order: number; sections: Section[]; }
interface Enrollment { id: number; user_id: number; already_enrolled: boolean; student: { name: string; cedula: string; }; }
interface AcademicYear { id: number; name: string; }

interface Props {
    activeYear: AcademicYear;
    previousYears: AcademicYear[];
    sourceGrades: Grade[];
    sourceEnrollments: Enrollment[];
    suggestedGrade: Grade | null;
    allActiveGrades: Grade[];
    sourceYearId: number | null;
    sourceGradeId: number | null;
    sourceSectionId: number | null;
}

export default function PromoteEnrollments({
    activeYear,
    previousYears,
    sourceGrades,
    sourceEnrollments,
    suggestedGrade,
    allActiveGrades,
    sourceYearId,
    sourceGradeId,
    sourceSectionId,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Inscripciones', href: '/admin/enrollments' },
        { title: 'Panel de Promoción', href: '#' },
    ];

    const { errors } = usePage().props;
    const [selectedStudents, setSelectedStudents] = useState<number[]>([]);
    const [isProcessing, setIsProcessing] = useState(false);
    
    // Select manual si no hay sugerencia o el usuario quiere cambiar
    const [manualDestGradeId, setManualDestGradeId] = useState<number | null>(suggestedGrade?.id ?? null);

    useEffect(() => {
        setManualDestGradeId(suggestedGrade?.id ?? null);
    }, [suggestedGrade]);

    useEffect(() => {
        setManualDestGradeId(suggestedGrade?.id ?? null);
    }, [suggestedGrade]);

    const handleSourceChange = (key: 'source_year_id' | 'source_grade_id' | 'source_section_id', value: string) => {
        const query: any = {
            source_year_id: sourceYearId,
            source_grade_id: sourceGradeId,
            source_section_id: sourceSectionId,
        };
        
        query[key] = value === 'all' ? null : value;
        
        // Cascading reset
        if (key === 'source_year_id') { query.source_grade_id = null; query.source_section_id = null; }
        if (key === 'source_grade_id') { query.source_section_id = null; }
        
        setSelectedStudents([]); // Reset select on changing source
        router.get('/admin/enrollments/promote', query, { preserveState: true });
    };

    const eligibleStudents = sourceEnrollments.filter((e) => !e.already_enrolled);
    const areAllEligibleSelected = eligibleStudents.length > 0 && selectedStudents.length === eligibleStudents.length;

    const toggleAll = () => {
        if (areAllEligibleSelected) {
            setSelectedStudents([]);
        } else {
            setSelectedStudents(eligibleStudents.map(e => e.user_id));
        }
    };

    const toggleStudent = (userId: number) => {
        if (selectedStudents.includes(userId)) {
            setSelectedStudents(selectedStudents.filter(id => id !== userId));
        } else {
            setSelectedStudents([...selectedStudents, userId]);
        }
    };

    const promoteTo = (destSection: Section) => {
        if (selectedStudents.length === 0) return;
        
        if (confirm(`¿Promover ${selectedStudents.length} alumnos a ${destSection.name}?`)) {
            router.post('/admin/enrollments/promote', {
                academic_year_id: activeYear.id,
                user_ids: selectedStudents,
                grade_id: destSection.grade_id.toString(),
                section_id: destSection.id.toString(),
            }, {
                preserveScroll: true,
                onStart: () => setIsProcessing(true),
                onFinish: () => setIsProcessing(false),
                onSuccess: () => setSelectedStudents([]),
            });
        }
    };

    const sourceSections = sourceGrades.find(g => g.id === sourceGradeId)?.sections || [];
    const destSections = allActiveGrades.find(g => g.id === manualDestGradeId)?.sections || [];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Promoción | ${activeYear.name}`} />

            <div className="flex flex-col gap-6 p-4 lg:p-8 h-[calc(100vh-4rem)] overflow-hidden">
                <div className="flex items-center gap-4 shrink-0">
                    <Button variant="outline" size="icon" asChild>
                        <Link href="/admin/enrollments">
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                            Panel de Promoción Masiva
                        </h1>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                            Traslada alumnos de años anteriores al año escolar activo ({activeYear.name})
                        </p>
                    </div>
                </div>

                <div className="grid h-full grid-cols-1 md:grid-cols-2 gap-6 min-h-0">
                    {/* Panel Izquierdo: Origen */}
                    <Card className="flex flex-col min-h-0 border-primary/20 bg-neutral-50/50 dark:bg-neutral-800/10">
                        <CardHeader className="shrink-0 bg-neutral-100/50 dark:bg-neutral-800/50 border-b">
                            <CardTitle className="text-lg flex items-center gap-2">
                                <span className="bg-primary/10 text-primary rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                                Seleccionar Origen
                            </CardTitle>
                            
                            <div className="grid grid-cols-3 gap-2 mt-4">
                                <div className="space-y-1">
                                    <Label className="text-xs">Año Escolar</Label>
                                    <Select value={sourceYearId?.toString() || 'all'} onValueChange={(v) => handleSourceChange('source_year_id', v)}>
                                        <SelectTrigger className="h-8 text-xs"><SelectValue placeholder="Seleccionar" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Seleccionar</SelectItem>
                                            {previousYears.map(y => <SelectItem key={y.id} value={y.id.toString()}>{y.name}</SelectItem>)}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-1">
                                    <Label className="text-xs">Grado</Label>
                                    <Select value={sourceGradeId?.toString() || 'all'} onValueChange={(v) => handleSourceChange('source_grade_id', v)} disabled={!sourceYearId}>
                                        <SelectTrigger className="h-8 text-xs"><SelectValue placeholder="Seleccionar" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Seleccionar</SelectItem>
                                            {sourceGrades.map(g => <SelectItem key={g.id} value={g.id.toString()}>{g.name}</SelectItem>)}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-1">
                                    <Label className="text-xs">Sección</Label>
                                    <Select value={sourceSectionId?.toString() || 'all'} onValueChange={(v) => handleSourceChange('source_section_id', v)} disabled={!sourceGradeId}>
                                        <SelectTrigger className="h-8 text-xs"><SelectValue placeholder="Seleccionar" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Seleccionar</SelectItem>
                                            {sourceSections.map(s => <SelectItem key={s.id} value={s.id.toString()}>{s.name}</SelectItem>)}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </CardHeader>
                        
                        <CardContent className="flex-1 overflow-auto p-0">
                            {sourceEnrollments.length > 0 ? (
                                <div className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                    <div className="flex items-center justify-between p-3 bg-white dark:bg-neutral-900 sticky top-0 border-b z-10">
                                        <div className="flex items-center gap-2">
                                            <Checkbox 
                                                checked={areAllEligibleSelected} 
                                                onCheckedChange={toggleAll}
                                                disabled={eligibleStudents.length === 0}
                                            />
                                            <span className="text-sm font-medium">Seleccionar Todos</span>
                                        </div>
                                        <Badge variant="secondary">{selectedStudents.length} / {eligibleStudents.length} seleccionados</Badge>
                                    </div>
                                    <div className="p-2">
                                        {sourceEnrollments.map((enr, index) => (
                                            <label 
                                                key={enr.id} 
                                                className={`flex items-center gap-2 p-3 rounded-lg transition-colors ${
                                                    enr.already_enrolled 
                                                    ? 'opacity-40 bg-neutral-100 dark:bg-neutral-900 pointer-events-none' 
                                                    : 'cursor-pointer hover:bg-neutral-100 dark:hover:bg-neutral-800'
                                                }`}
                                            >
                                                <span className="text-[10px] font-mono text-neutral-400 w-5 shrink-0 text-right">{index + 1}</span>
                                                <Checkbox 
                                                    checked={selectedStudents.includes(enr.user_id)} 
                                                    onCheckedChange={() => toggleStudent(enr.user_id)}
                                                    disabled={enr.already_enrolled}
                                                />
                                                <div className="flex-1">
                                                    <div className={`font-medium text-sm ${enr.already_enrolled ? 'line-through text-neutral-400' : ''}`}>
                                                        {enr.student.name}
                                                    </div>
                                                    <div className={`text-xs font-mono flex items-center ${enr.already_enrolled ? 'text-neutral-400' : 'text-neutral-500'}`}>
                                                        {enr.student.cedula}
                                                    </div>
                                                </div>
                                                {enr.already_enrolled && (
                                                    <Badge variant="outline" className="text-green-600 border-green-200 bg-green-50 dark:border-green-900 dark:bg-green-900/20">
                                                        <CheckCircle2 className="w-3 h-3 mr-1" /> Inscrito
                                                    </Badge>
                                                )}
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            ) : (
                                <div className="flex h-full flex-col items-center justify-center p-8 text-neutral-500 text-sm text-center">
                                    <GraduationCap className="h-10 w-10 mb-2 opacity-20" />
                                    Selecciona un año, grado y sección de origen para ver los alumnos.
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Panel Derecho: Destino */}
                    <Card className="flex flex-col min-h-0 border-primary/20 bg-primary/5 dark:bg-primary/5">
                        <CardHeader className="shrink-0 bg-primary/10 border-b border-primary/10">
                            <CardTitle className="text-lg flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <span className="bg-primary text-primary-foreground rounded-full w-6 h-6 flex items-center justify-center text-sm">2</span>
                                    Destino ({activeYear.name})
                                </div>
                                {suggestedGrade && manualDestGradeId === suggestedGrade.id && (
                                    <Badge className="bg-amber-500 hover:bg-amber-600">Sugerido para promover</Badge>
                                )}
                            </CardTitle>
                            
                            <div className="mt-4">
                                <Label className="text-xs text-neutral-600 dark:text-neutral-400">Grado Destino (Activo)</Label>
                                <Select 
                                    value={manualDestGradeId?.toString() || ''} 
                                    onValueChange={(v) => setManualDestGradeId(Number(v))}
                                >
                                    <SelectTrigger className="w-full bg-white dark:bg-neutral-900">
                                        <SelectValue placeholder="Seleccione grado destino..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {allActiveGrades.map(g => (
                                            <SelectItem key={g.id} value={g.id.toString()}>
                                                {g.name} {suggestedGrade && g.id === suggestedGrade.id ? '(Sugerido)' : ''}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </CardHeader>

                        <CardContent className="flex-1 overflow-auto p-4 lg:p-6">
                            <div className="flex flex-col items-center justify-center h-full">
                                {selectedStudents.length === 0 ? (
                                    <div className="text-center space-y-3">
                                        <ArrowRight className="h-12 w-12 mx-auto text-primary/30" strokeWidth={1} />
                                        <p className="text-sm text-neutral-500 max-w-[200px]">
                                            Selecciona alumnos en el panel izquierdo para promoverlos.
                                        </p>
                                    </div>
                                ) : !manualDestGradeId ? (
                                    <div className="text-center text-sm text-amber-600 dark:text-amber-500">
                                        Selecciona un grado destino arriba.
                                    </div>
                                ) : (
                                    <div className="w-full space-y-4">
                                        <div className="text-center mb-6">
                                            <Badge variant="outline" className="px-4 py-1 text-base bg-white dark:bg-neutral-900 shadow-sm border-primary/20">
                                                Promover {selectedStudents.length} alumnos
                                            </Badge>
                                        </div>
                                        
                                        <div className="grid gap-3 sm:grid-cols-2">
                                            {destSections.length > 0 ? (
                                                destSections.map(section => (
                                                    <Button 
                                                        key={section.id} 
                                                        variant="default"
                                                        className="h-auto py-4 flex flex-col items-center gap-1 shadow-md hover:scale-105 transition-transform"
                                                        onClick={() => promoteTo(section)}
                                                        disabled={isProcessing}
                                                    >
                                                        <span className="font-bold text-lg">{section.name}</span>
                                                        <span className="text-xs opacity-80 font-normal">Hacer clic para promover</span>
                                                    </Button>
                                                ))
                                            ) : (
                                                <div className="col-span-full text-center p-4 border border-dashed border-red-200 bg-red-50 text-red-600 rounded-lg dark:bg-red-950/30 dark:border-red-900">
                                                    No hay secciones configuradas para este grado.
                                                </div>
                                            )}
                                        </div>
                                        <InputError message={errors.user_ids} className="text-center mt-4" />
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
