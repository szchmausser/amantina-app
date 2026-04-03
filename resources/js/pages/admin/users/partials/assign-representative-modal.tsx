import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Search, UserPlus, X } from 'lucide-react';
import { store as linkRepresentative } from '@/routes/admin/student-representatives';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';

interface Props {
    isOpen: boolean;
    onClose: () => void;
    studentId: number;
    relationshipTypes: any[];
    availableRepresentatives: any[];
}

export default function AssignRepresentativeModal({
    isOpen,
    onClose,
    studentId,
    relationshipTypes,
    availableRepresentatives,
}: Props) {
    const [searchTerm, setSearchTerm] = useState('');
    
    const { data, setData, post, processing, errors, reset } = useForm({
        student_id: studentId,
        representative_id: '',
        relationship_type_id: '',
    });

    const filteredRepresentatives = availableRepresentatives.filter(
        (rep) =>
            rep.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
            rep.cedula.toLowerCase().includes(searchTerm.toLowerCase())
    );

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(linkRepresentative().url, {
            onSuccess: () => {
                onClose();
                reset();
            },
        });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-[500px]">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Asignar Representante</DialogTitle>
                        <DialogDescription>
                            Selecciona un representante registrado y define su parentesco con el estudiante.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-6 py-6">
                        {/* Búsqueda de Representante */}
                        <div className="space-y-4">
                            <Label>Buscar Representante</Label>
                            <div className="relative">
                                <Search className="absolute stroke-neutral-400 left-3 top-1/2 h-4 w-4 -translate-y-1/2" />
                                <Input
                                    placeholder="Nombre o Cédula..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    className="pl-10"
                                />
                            </div>

                            <div className="max-h-[200px] overflow-y-auto rounded-md border border-neutral-200 p-1 dark:border-neutral-800">
                                {filteredRepresentatives.length > 0 ? (
                                    <div className="grid gap-1">
                                        {filteredRepresentatives.map((rep) => (
                                            <button
                                                key={rep.id}
                                                type="button"
                                                onClick={() => setData('representative_id', rep.id.toString())}
                                                className={`flex items-center justify-between rounded-sm px-3 py-2 text-left text-sm transition-colors hover:bg-neutral-100 dark:hover:bg-neutral-800 ${
                                                    data.representative_id === rep.id.toString()
                                                        ? 'bg-neutral-100 ring-1 ring-neutral-300 dark:bg-neutral-800 dark:ring-neutral-700'
                                                        : ''
                                                }`}
                                            >
                                                <div>
                                                    <p className="font-medium">{rep.name}</p>
                                                    <p className="text-xs text-neutral-500">{rep.cedula}</p>
                                                </div>
                                                {data.representative_id === rep.id.toString() && (
                                                    <Badge variant="secondary" className="h-5 px-1.5">
                                                        Seleccionado
                                                    </Badge>
                                                )}
                                            </button>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="py-8 text-center text-sm text-neutral-500">
                                        No se encontraron representantes.
                                    </div>
                                )}
                            </div>
                            {errors.representative_id && (
                                <p className="text-sm font-medium text-destructive">{errors.representative_id}</p>
                            )}
                        </div>

                        {/* Tipo de Parentesco */}
                        <div className="space-y-2">
                            <Label htmlFor="relationship_type">Parentesco</Label>
                            <Select
                                value={data.relationship_type_id}
                                onValueChange={(value) => setData('relationship_type_id', value)}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar parentesco..." />
                                </SelectTrigger>
                                <SelectContent>
                                    {relationshipTypes.map((type) => (
                                        <SelectItem key={type.id} value={type.id.toString()}>
                                            {type.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.relationship_type_id && (
                                <p className="text-sm font-medium text-destructive">{errors.relationship_type_id}</p>
                            )}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={onClose}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={processing || !data.representative_id || !data.relationship_type_id}>
                            <UserPlus className="mr-2 h-4 w-4" />
                            Vincular
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
