import { useState, useRef } from 'react';
import { router } from '@inertiajs/react';
import { X, Upload, FileText, Trash2, Paperclip } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import InputError from '@/components/input-error';

interface HealthRecordModalProps {
    isOpen: boolean;
    onClose: () => void;
    studentId: number;
    studentName: string;
    healthConditions: { id: number; name: string }[];
    currentUserId: number;
    existingRecord?: {
        id: number;
        health_condition_id: number;
        received_at: string;
        received_at_location: string | null;
        observations: string | null;
        media: {
            id: number;
            file_name: string;
            custom_properties: { description?: string };
        }[];
    } | null;
    isEditing?: boolean;
}

export default function HealthRecordModal({
    isOpen,
    onClose,
    studentId,
    studentName,
    healthConditions,
    currentUserId,
    existingRecord,
    isEditing = false,
}: HealthRecordModalProps) {
    const fileInputRef = useRef<HTMLInputElement>(null);

    const [healthConditionId, setHealthConditionId] = useState(
        existingRecord?.health_condition_id?.toString() || '',
    );
    const [receivedAt, setReceivedAt] = useState(
        existingRecord?.received_at
            ? existingRecord.received_at.substring(0, 16)
            : '',
    );
    const [receivedAtLocation, setReceivedAtLocation] = useState(
        existingRecord?.received_at_location || '',
    );
    const [observations, setObservations] = useState(
        existingRecord?.observations || '',
    );
    const [files, setFiles] = useState<File[]>([]);
    const [fileDescriptions, setFileDescriptions] = useState<
        Record<string, string>
    >({});
    const [deleteMediaIds, setDeleteMediaIds] = useState<number[]>([]);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files) {
            const newFiles = Array.from(e.target.files);
            setFiles((prev) => [...prev, ...newFiles]);
            // Initialize descriptions
            const newDescs: Record<string, string> = {};
            newFiles.forEach((f) => {
                newDescs[f.name] = '';
            });
            setFileDescriptions((prev) => ({ ...prev, ...newDescs }));
        }
    };

    const removeFile = (index: number) => {
        setFiles((prev) => prev.filter((_, i) => i !== index));
    };

    const removeExistingMedia = (mediaId: number) => {
        setDeleteMediaIds((prev) => [...prev, mediaId]);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setErrors({});

        const formData = new FormData();
        formData.append('user_id', studentId.toString());
        formData.append('health_condition_id', healthConditionId);
        formData.append('received_by', currentUserId.toString());
        formData.append('received_at', receivedAt);
        if (receivedAtLocation)
            formData.append('received_at_location', receivedAtLocation);
        if (observations) formData.append('observations', observations);

        files.forEach((file, index) => {
            formData.append(`documents[${index}]`, file);
            if (fileDescriptions[file.name]) {
                formData.append(
                    `document_descriptions[${index}]`,
                    fileDescriptions[file.name],
                );
            }
        });

        deleteMediaIds.forEach((id) => {
            formData.append('delete_media_ids[]', id.toString());
        });

        const url = isEditing
            ? `/admin/student-health-records/${existingRecord?.id}`
            : '/admin/student-health-records';

        if (isEditing) {
            formData.append('_method', 'PUT');
        }

        router.post(url, formData, {
            forceFormData: true,
            preserveScroll: true,
            onStart: () => setIsSubmitting(true),
            onFinish: () => setIsSubmitting(false),
            onError: (errors: Record<string, string>) => {
                setErrors(errors);
                return false;
            },
            onSuccess: () => {
                handleClose();
            },
        });
    };

    const handleClose = () => {
        setHealthConditionId('');
        setReceivedAt('');
        setReceivedAtLocation('');
        setObservations('');
        setFiles([]);
        setFileDescriptions({});
        setDeleteMediaIds([]);
        setErrors({});
        onClose();
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div className="w-full max-w-lg rounded-xl bg-white shadow-xl dark:bg-neutral-900">
                {/* Header */}
                <div className="flex items-center justify-between border-b px-6 py-4">
                    <h2 className="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                        {isEditing
                            ? 'Editar Registro de Salud'
                            : 'Nuevo Registro de Salud'}
                    </h2>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="h-8 w-8"
                        onClick={handleClose}
                    >
                        <X className="h-4 w-4" />
                    </Button>
                </div>

                {/* Form */}
                <form
                    onSubmit={handleSubmit}
                    className="max-h-[70vh] space-y-4 overflow-y-auto p-6"
                >
                    <div className="space-y-2">
                        <Label>Estudiante</Label>
                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                            {studentName}
                        </p>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="condition">Condición de Salud</Label>
                        <Select
                            value={healthConditionId}
                            onValueChange={setHealthConditionId}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar condición..." />
                            </SelectTrigger>
                            <SelectContent>
                                {healthConditions.map((c) => (
                                    <SelectItem
                                        key={c.id}
                                        value={c.id.toString()}
                                    >
                                        {c.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.health_condition_id} />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="received_at">
                                Fecha de Recepción
                            </Label>
                            <Input
                                id="received_at"
                                type="datetime-local"
                                value={receivedAt}
                                onChange={(e) => setReceivedAt(e.target.value)}
                                required
                            />
                            <InputError message={errors.received_at} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="location">Lugar de Entrega</Label>
                            <Input
                                id="location"
                                value={receivedAtLocation}
                                onChange={(e) =>
                                    setReceivedAtLocation(e.target.value)
                                }
                                placeholder="Ej: Secretaría"
                            />
                            <InputError message={errors.received_at_location} />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="observations">Observaciones</Label>
                        <textarea
                            id="observations"
                            className="flex min-h-[80px] w-full rounded-md border border-neutral-200 bg-transparent px-3 py-2 text-sm placeholder:text-neutral-400 focus:ring-2 focus:ring-neutral-400 focus:outline-none dark:border-neutral-800 dark:placeholder:text-neutral-600"
                            value={observations}
                            onChange={(e) => setObservations(e.target.value)}
                            placeholder="Detalles adicionales sobre la condición..."
                        />
                        <InputError message={errors.observations} />
                    </div>

                    {/* File Upload */}
                    <div className="space-y-2">
                        <Label>Documentos de Soporte</Label>
                        <div
                            className="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-neutral-200 p-6 transition-colors hover:border-neutral-300 dark:border-neutral-800 dark:hover:border-neutral-700"
                            onClick={() => fileInputRef.current?.click()}
                        >
                            <Upload className="h-8 w-8 text-neutral-400" />
                            <p className="mt-2 text-sm text-neutral-500">
                                Clic para seleccionar archivos
                            </p>
                            <p className="text-xs text-neutral-400">
                                PDF, JPG, PNG, GIF, WebP (máx. 5MB)
                            </p>
                            <input
                                ref={fileInputRef}
                                type="file"
                                multiple
                                accept=".pdf,.jpg,.jpeg,.png,.gif,.webp"
                                className="hidden"
                                onChange={handleFileChange}
                            />
                        </div>
                        <InputError message={errors['documents.0']} />

                        {/* New Files */}
                        {files.length > 0 && (
                            <div className="space-y-2">
                                {files.map((file, index) => (
                                    <div
                                        key={index}
                                        className="flex items-start gap-2 rounded-lg border bg-neutral-50 p-3 dark:bg-neutral-800"
                                    >
                                        <FileText className="mt-0.5 h-4 w-4 shrink-0 text-neutral-400" />
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-medium">
                                                {file.name}
                                            </p>
                                            <p className="text-xs text-neutral-400">
                                                {(
                                                    file.size /
                                                    1024 /
                                                    1024
                                                ).toFixed(2)}{' '}
                                                MB
                                            </p>
                                            <Input
                                                className="mt-1 h-8 text-xs"
                                                placeholder="Descripción del documento..."
                                                value={
                                                    fileDescriptions[
                                                        file.name
                                                    ] || ''
                                                }
                                                onChange={(e) =>
                                                    setFileDescriptions(
                                                        (prev) => ({
                                                            ...prev,
                                                            [file.name]:
                                                                e.target.value,
                                                        }),
                                                    )
                                                }
                                            />
                                        </div>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            className="h-6 w-6 shrink-0 text-red-500"
                                            onClick={() => removeFile(index)}
                                        >
                                            <Trash2 className="h-3.5 w-3.5" />
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        )}

                        {/* Existing Files (editing) */}
                        {existingRecord?.media &&
                            existingRecord.media.length > 0 && (
                                <div className="space-y-2">
                                    <p className="text-xs font-medium text-neutral-500">
                                        Documentos existentes:
                                    </p>
                                    {existingRecord.media
                                        .filter(
                                            (m) =>
                                                !deleteMediaIds.includes(m.id),
                                        )
                                        .map((media) => (
                                            <div
                                                key={media.id}
                                                className="flex items-center gap-2 rounded-lg border bg-neutral-50 p-2 dark:bg-neutral-800"
                                            >
                                                <Paperclip className="h-4 w-4 text-neutral-400" />
                                                <span className="flex-1 truncate text-sm">
                                                    {media.file_name}
                                                </span>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-6 w-6 shrink-0 text-red-500"
                                                    onClick={() =>
                                                        removeExistingMedia(
                                                            media.id,
                                                        )
                                                    }
                                                >
                                                    <Trash2 className="h-3.5 w-3.5" />
                                                </Button>
                                            </div>
                                        ))}
                                </div>
                            )}
                    </div>

                    {/* Actions */}
                    <div className="flex justify-end gap-3 border-t pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={handleClose}
                            disabled={isSubmitting}
                        >
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={isSubmitting}>
                            {isSubmitting
                                ? 'Guardando...'
                                : isEditing
                                  ? 'Actualizar Registro'
                                  : 'Crear Registro'}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    );
}
