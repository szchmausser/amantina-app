import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Search, UserPlus, X, Check } from 'lucide-react';
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
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';

interface Props {
    isOpen: boolean;
    onClose: () => void;
    representativeId: number;
    relationshipTypes: any[];
    availableStudents: any[];
}

export default function AssignStudentModal({
    isOpen,
    onClose,
    representativeId,
    relationshipTypes,
    availableStudents,
}: Props) {
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedUser, setSelectedUser] = useState<any>(null);
    const getInitials = useInitials();

    const { data, setData, post, processing, errors, reset } = useForm({
        student_id: '',
        representative_id: representativeId.toString(),
        relationship_type_id: '',
    });

    const filteredUsers =
        searchTerm.length >= 2
            ? availableStudents
                  .filter(
                      (u) =>
                          u.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                          u.cedula.toLowerCase().includes(searchTerm.toLowerCase()) ||
                          (u.email && u.email.toLowerCase().includes(searchTerm.toLowerCase())),
                  )
                  .slice(0, 10)
            : [];

    const handleSelectUser = (user: any) => {
        setSelectedUser(user);
        setData('student_id', user.id.toString());
        setSearchTerm('');
    };

    const handleClearSelection = () => {
        setSelectedUser(null);
        setData('student_id', '');
    };

    const handleClose = () => {
        setSelectedUser(null);
        setSearchTerm('');
        reset();
        onClose();
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(linkRepresentative().url, {
            onSuccess: () => {
                handleClose();
            },
        });
    };

    const canSubmit = selectedUser !== null && data.relationship_type_id !== '';

    return (
        <Dialog open={isOpen} onOpenChange={handleClose}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Asignar Alumno</DialogTitle>
                    <DialogDescription>
                        Vincula un alumno a este representante legal.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-4">
                    {!selectedUser ? (
                        <div className="space-y-2">
                            <Label>Buscar alumno</Label>
                            <div className="relative">
                                <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    placeholder="Escribe nombre, cédula o correo del alumno..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    className="pl-8"
                                    autoFocus
                                />
                            </div>
                            {searchTerm.length === 1 && (
                                <p className="text-xs text-muted-foreground">
                                    Escribe al menos 2 caracteres para buscar
                                </p>
                            )}
                            {filteredUsers.length > 0 && (
                                <div className="max-h-60 space-y-1 overflow-y-auto rounded-lg border">
                                    {filteredUsers.map((user) => (
                                        <button
                                            key={user.id}
                                            type="button"
                                            onClick={() => handleSelectUser(user)}
                                            className="flex w-full items-center gap-3 px-3 py-2.5 text-left transition-colors hover:bg-accent"
                                        >
                                            <Avatar className="h-8 w-8">
                                                <AvatarFallback className="text-xs">
                                                    {getInitials(user.name)}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate text-sm font-medium">{user.name}</p>
                                                <p className="text-xs text-muted-foreground">
                                                    {user.cedula}
                                                    {user.email ? ` · ${user.email}` : ''}
                                                </p>
                                            </div>
                                        </button>
                                    ))}
                                </div>
                            )}
                            {searchTerm.length >= 2 && filteredUsers.length === 0 && (
                                <p className="py-4 text-center text-sm text-muted-foreground">
                                    No se encontraron resultados
                                </p>
                            )}
                        </div>
                    ) : (
                        <div className="flex items-center gap-3 rounded-lg border bg-muted/50 p-3">
                            <Avatar className="h-10 w-10">
                                <AvatarFallback>{getInitials(selectedUser.name)}</AvatarFallback>
                            </Avatar>
                            <div className="min-w-0 flex-1">
                                <p className="text-sm font-medium">{selectedUser.name}</p>
                                <p className="text-xs text-muted-foreground">{selectedUser.cedula}</p>
                            </div>
                            <Check className="h-5 w-5 text-emerald-500 dark:text-emerald-400" />
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="h-8 w-8"
                                onClick={handleClearSelection}
                            >
                                <X className="h-4 w-4" />
                            </Button>
                        </div>
                    )}

                    <div className="space-y-2">
                        <Label>Tipo de Vínculo</Label>
                        <Select
                            value={data.relationship_type_id}
                            onValueChange={(val) => setData('relationship_type_id', val)}
                            disabled={!selectedUser}
                        >
                            <SelectTrigger>
                                <SelectValue
                                    placeholder={
                                        selectedUser
                                            ? 'Seleccionar vínculo'
                                            : 'Primero selecciona una persona'
                                    }
                                />
                            </SelectTrigger>
                            <SelectContent>
                                {(() => {
                                    const parentType = relationshipTypes.find((rt: any) => ['Madre', 'Padre'].includes(rt.name));
                                    const otherType = relationshipTypes.find((rt: any) => !['Madre', 'Padre'].includes(rt.name));
                                    const options = [];
                                    if (parentType) options.push({ id: parentType.id, label: 'hijo(a)' });
                                    if (otherType) options.push({ id: otherType.id, label: 'otro' });
                                    return options.map((opt) => (
                                        <SelectItem key={opt.id} value={opt.id.toString()}>
                                            {opt.label}
                                        </SelectItem>
                                    ));
                                })()}
                            </SelectContent>
                        </Select>
                        {errors.relationship_type_id && (
                            <p className="text-xs text-red-500 dark:text-red-400">{errors.relationship_type_id}</p>
                        )}
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={handleClose}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={processing || !canSubmit}>
                            <UserPlus className="mr-2 h-4 w-4" />
                            Asignar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
