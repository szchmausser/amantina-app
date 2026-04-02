import { FormEvent, useMemo, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Search, UserSearch } from 'lucide-react';
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
        { title: 'Nuevo Ingreso (Lote)', href: '#' },
    ];

    const { errors } = usePage().props;
    
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedStudents, setSelectedStudents] = useState<number[]>([]);
    const [isProcessing, setIsProcessing] = useState(false);
    
    const [destGradeId, setDestGradeId] = useState<number | null>(null);

    // Filtrado de alumnos en panel izquierdo
    const filteredStudents = useMemo(() => {
        if (!searchQuery) return availableStudents;
        const q = searchQuery.toLowerCase();
        return availableStudents.filter(s => 
            s.name.toLowerCase().includes(q) || 
            s.cedula.toLowerCase().includes(q)
        );
    }, [availableStudents, searchQuery]);

    const areAllFilteredSelected = filteredStudents.length > 0 && 
        filteredStudents.every(s => selectedStudents.includes(s.id));

    const toggleAll = () => {
        if (areAllFilteredSelected) {
            // Deseleccionar los que estan en la vista actual
            const filteredIds = filteredStudents.map(s => s.id);
            setSelectedStudents(selectedStudents.filter(id => !filteredIds.includes(id)));
        } else {
            // Seleccionar todos los de la vista junto a los que ya estaban
            const newIds = [...selectedStudents];
            filteredStudents.forEach(s => {
                if (!newIds.includes(s.id)) newIds.push(s.id);
            });
            setSelectedStudents(newIds);
        }
    };

    const toggleStudent = (userId: number) => {
        if (selectedStudents.includes(userId)) {
            setSelectedStudents(selectedStudents.filter(id => id !== userId));
        } else {
            setSelectedStudents([...selectedStudents, userId]);
        }
    };

    const enrollTo = (destSection: Section) => {
        if (selectedStudents.length === 0) return;
        
        if (confirm(`¿Inscribir ${selectedStudents.length} alumnos de nuevo ingreso en ${destSection.name}?`)) {
            router.post('/admin/enrollments', {
                academic_year_id: activeYear.id,
                user_ids: selectedStudents,
                grade_id: destGradeId?.toString() || '',
                section_id: destSection.id.toString(),
            }, {
                preserveScroll: true,
                onStart: () => setIsProcessing(true),
                onFinish: () => setIsProcessing(false),
                onSuccess: () => {
                    setSelectedStudents([]);
                    setSearchQuery('');
                },
            });
        }
    };

    const destSections = grades.find(g => g.id === destGradeId)?.sections || [];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Nuevo Ingreso | ${activeYear.name}`} />

            <div className="flex flex-col gap-6 p-4 lg:p-8 h-[calc(100vh-4rem)] overflow-hidden">
                <div className="flex items-center gap-4 shrink-0">
                    <Button variant="outline" size="icon" asChild>
                        <Link href="/admin/enrollments">
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                            Nuevos Ingresos en Lote
                        </h1>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                            Acumula e inscribe rápidamente alumnos al año escolar activo ({activeYear.name}).
                        </p>
                    </div>
                </div>

                <div className="grid h-full grid-cols-1 md:grid-cols-2 gap-6 min-h-0">
                    {/* Panel Izquierdo: Alumnos Sin Inscripción */}
                    <Card className="flex flex-col min-h-0 border-primary/20 bg-neutral-50/50 dark:bg-neutral-800/10">
                        <CardHeader className="shrink-0 bg-neutral-100/50 dark:bg-neutral-800/50 border-b">
                            <CardTitle className="text-lg flex items-center gap-2">
                                <span className="bg-primary/10 text-primary rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                                Seleccionar/Acumular Alumnos
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
                            {availableStudents.length > 0 ? (
                                <div className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                    <div className="flex items-center justify-between p-3 bg-white dark:bg-neutral-900 sticky top-0 border-b z-10">
                                        <div className="flex items-center gap-2">
                                            <Checkbox 
                                                checked={areAllFilteredSelected} 
                                                onCheckedChange={toggleAll}
                                                disabled={filteredStudents.length === 0}
                                            />
                                            <span className="text-sm font-medium">Seleccionar de la lista</span>
                                        </div>
                                        {selectedStudents.length > 0 && (
                                            <Badge variant="outline" className="border-primary text-primary">
                                                {selectedStudents.length} acumulados
                                            </Badge>
                                        )}
                                    </div>
                                    <div className="p-2">
                                        {filteredStudents.length > 0 ? (
                                            filteredStudents.map((s, index) => (
                                                <label 
                                                    key={s.id} 
                                                    className="flex items-center gap-2 p-3 rounded-lg cursor-pointer transition-colors hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                                >
                                                    <span className="text-[10px] font-mono text-neutral-400 w-5 shrink-0 text-right">{index + 1}</span>
                                                    <Checkbox 
                                                        checked={selectedStudents.includes(s.id)} 
                                                        onCheckedChange={() => toggleStudent(s.id)}
                                                    />
                                                    <div className="flex-1">
                                                        <div className="font-medium text-sm">{s.name}</div>
                                                        <div className="text-xs text-neutral-500 font-mono">{s.cedula}</div>
                                                    </div>
                                                </label>
                                            ))
                                        ) : (
                                            <div className="p-8 text-center text-sm text-neutral-500">
                                                No hay resultados para "{searchQuery}"
                                            </div>
                                        )}
                                    </div>
                                </div>
                            ) : (
                                <div className="flex h-full flex-col items-center justify-center p-8 text-neutral-500 text-sm text-center">
                                    <UserSearch className="h-10 w-10 mb-2 opacity-20" />
                                    No hay alumnos pendientes por inscripción en el sistema.
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
                            </CardTitle>
                            
                            <div className="mt-4">
                                <Label className="text-xs text-neutral-600 dark:text-neutral-400">Grado Destino (Activo)</Label>
                                <Select 
                                    value={destGradeId?.toString() || ''} 
                                    onValueChange={(v) => setDestGradeId(Number(v))}
                                >
                                    <SelectTrigger className="w-full bg-white dark:bg-neutral-900 mt-1">
                                        <SelectValue placeholder="Seleccione grado destino..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {grades.map(g => (
                                            <SelectItem key={g.id} value={g.id.toString()}>
                                                {g.name}
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
                                            Acumula alumnos de nuevo ingreso en el panel izquierdo.
                                        </p>
                                    </div>
                                ) : !destGradeId ? (
                                    <div className="text-center text-sm text-amber-600 dark:text-amber-500">
                                        Selecciona un grado destino arriba.
                                    </div>
                                ) : (
                                    <div className="w-full space-y-4">
                                        <div className="text-center mb-6">
                                            <Badge variant="outline" className="px-4 py-1 text-base bg-white dark:bg-neutral-900 shadow-sm border-primary/20">
                                                Inscribir {selectedStudents.length} alumnos
                                            </Badge>
                                        </div>
                                        
                                        <div className="grid gap-3 sm:grid-cols-2">
                                            {destSections.length > 0 ? (
                                                destSections.map(section => (
                                                    <Button 
                                                        key={section.id} 
                                                        variant="default"
                                                        className="h-auto py-4 flex flex-col items-center gap-1 shadow-md hover:scale-105 transition-transform"
                                                        onClick={() => enrollTo(section)}
                                                        disabled={isProcessing}
                                                    >
                                                        <span className="font-bold text-lg">{section.name}</span>
                                                        <span className="text-xs opacity-80 font-normal">Hacer clic para inscribir</span>
                                                    </Button>
                                                ))
                                            ) : (
                                                <div className="col-span-full text-center p-4 border border-dashed border-red-200 bg-red-50 text-red-600 rounded-lg dark:bg-red-950/30 dark:border-red-900">
                                                    No hay secciones configuradas para este grado.
                                                </div>
                                            )}
                                        </div>
                                        <InputError message={errors?.user_ids || errors?.grade_id || errors?.section_id} className="text-center mt-4" />
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
