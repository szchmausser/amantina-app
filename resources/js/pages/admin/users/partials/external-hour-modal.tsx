import { useRef, useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { X, Upload, FileText, Trash2, Paperclip } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';

export interface ExternalHourItem {
    id: number;
    hours: number;
    period: string;
    institution_name: string;
    description: string | null;
    created_at: string;
    admin: { id: number; name: string } | null;
    media: {
        id: number;
        name: string;
        original_url: string;
        mime_type: string;
    }[];
}

interface Props {
    isOpen: boolean;
    onClose: () => void;
    studentId: number;
    studentName: string;
    existingRecord?: ExternalHourItem | null;
    isEditing?: boolean;
}

export default function ExternalHourModal({
    isOpen,
    onClose,
    studentId,
    studentName,
    existingRecord,
    isEditing = false,
}: Props) {
    const fileInputRef = useRef<HTMLInputElement>(null);

    const [period, setPeriod] = useState(existingRecord?.period ?? '');
    const [hours, setHours] = useState(existingRecord?.hours?.toString() ?? '');
    const [institutionName, setInstitutionName] = useState(
        existingRecord?.institution_name ?? '',
    );
    const [description, setDescription] = useState(
        existingRecord?.description ?? '',
    );
    const [files, setFiles] = useState<File[]>([]);
    const [deleteMediaIds, setDeleteMediaIds] = useState<number[]>([]);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    useEffect(() => {
        if (isOpen) {
            setPeriod(existingRecord?.period ?? '');
            setHours(existingRecord?.hours?.toString() ?? '');
            setInstitutionName(existingRecord?.institution_name ?? '');
            setDescription(existingRecord?.description ?? '');
            setFiles([]);
            setDeleteMediaIds([]);
            setErrors({});
        }
    }, [isOpen, existingRecord]);

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files) {
            setFiles((prev) => [...prev, ...Array.from(e.target.files!)]);
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
        formData.append('period', period);
        formData.append('hours', hours);
        formData.append('institution_name', institutionName);
        if (description) {
            formData.append('description', description);
        }
        files.forEach((file, index) => {
            formData.append(`documents[${index}]`, file);
        });
        deleteMediaIds.forEach((id) => {
            formData.append('delete_media_ids[]', id.toString());
        });

        const opts = {
            forceFormData: true as const,
            preserveScroll: true,
            onStart: () => setIsSubmitting(true),
            onFinish: () => setIsSubmitting(false),
            onError: (errs: Record<string, string>) => setErrors(errs),
            onSuccess: () => handleClose(),
        };

        if (isEditing) {
            formData.append('_method', 'PUT');
            router.post(
                `/admin/external-hours/${existingRecord?.id}`,
                formData,
                opts,
            );
        } else {
            router.post(
                `/admin/users/${studentId}/external-hours`,
                formData,
                opts,
            );
        }
    };

    const handleClose = () => {
        setPeriod('');
        setHours('');
        setInstitutionName('');
        setDescription('');
        setFiles([]);
        setDeleteMediaIds([]);
        setErrors({});
        onClose();
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div className="w-full max-w-lg rounded-xl bg-white shadow-xl dark:bg-neutral-900">
                {/* Encabezado */}
                <div className="flex items-center justify-between border-b px-6 py-4">
                    <h2 className="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                        {isEditing
                            ? 'Editar Horas Externas'
                            : 'Registrar Horas Externas'}
                    </h2>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="h-8 w-8"
                        onClick={handleClose}
                        type="button"
                    >
                        <X className="h-4 w-4" />
                    </Button>
                </div>

                {/* Formulario */}
                <form
                    onSubmit={handleSubmit}
                    className="max-h-[70vh] space-y-4 overflow-y-auto p-6"
                >
                    {/* Estudiante (solo lectura) */}
                    <div className="space-y-1">
                        <Label className="text-xs font-semibold tracking-wider text-neutral-400 uppercase">
                            Estudiante
                        </Label>
                        <p className="text-sm font-medium text-neutral-700 dark:text-neutral-300">
                            {studentName}
                        </p>
                    </div>

                    {/* Período libre + Horas en grid */}
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="period">Período</Label>
                            <Input
                                id="period"
                                value={period}
                                onChange={(e) => setPeriod(e.target.value)}
                                placeholder="Ej: 2021-2025"
                                maxLength={50}
                                required
                            />
                            <p className="text-xs text-neutral-400">
                                Rango de años del período externo
                            </p>
                            <InputError message={errors.period} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="hours">Horas Acreditadas</Label>
                            <Input
                                id="hours"
                                type="number"
                                min="0.5"
                                step="0.5"
                                value={hours}
                                onChange={(e) => setHours(e.target.value)}
                                placeholder="Ej: 120"
                                required
                            />
                            <InputError message={errors.hours} />
                        </div>
                    </div>

                    {/* Institución de origen */}
                    <div className="space-y-2">
                        <Label htmlFor="institution_name">
                            Institución de Origen
                        </Label>
                        <Input
                            id="institution_name"
                            value={institutionName}
                            onChange={(e) => setInstitutionName(e.target.value)}
                            placeholder="Ej: U.E. La Salle"
                            required
                        />
                        <InputError message={errors.institution_name} />
                    </div>

                    {/* Descripción */}
                    <div className="space-y-2">
                        <Label htmlFor="description">
                            Descripción / Observaciones{' '}
                            <span className="text-neutral-400">(opcional)</span>
                        </Label>
                        <textarea
                            id="description"
                            className="flex min-h-[80px] w-full rounded-md border border-neutral-200 bg-transparent px-3 py-2 text-sm placeholder:text-neutral-400 focus:ring-2 focus:ring-neutral-400 focus:outline-none dark:border-neutral-800 dark:placeholder:text-neutral-600"
                            value={description}
                            onChange={(e) => setDescription(e.target.value)}
                            placeholder="Detalles sobre las horas acreditadas..."
                        />
                        <InputError message={errors.description} />
                    </div>

                    {/* Documentos de respaldo */}
                    <div className="space-y-2">
                        <Label>Documentos de Respaldo</Label>
                        <div
                            className="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-neutral-200 p-6 transition-colors hover:border-neutral-300 dark:border-neutral-800 dark:hover:border-neutral-700"
                            onClick={() => fileInputRef.current?.click()}
                        >
                            <Upload className="h-8 w-8 text-neutral-400" />
                            <p className="mt-2 text-sm text-neutral-500">
                                Clic para seleccionar archivos
                            </p>
                            <p className="text-xs text-neutral-400">
                                PDF, JPG, JPEG, PNG, WebP
                            </p>
                            <input
                                ref={fileInputRef}
                                type="file"
                                multiple
                                accept=".pdf,.jpg,.jpeg,.png,.webp"
                                className="hidden"
                                onChange={handleFileChange}
                            />
                        </div>
                        <InputError message={errors['documents.0']} />
                        <InputError message={errors.documents} />

                        {/* Archivos nuevos */}
                        {files.length > 0 && (
                            <div className="space-y-2">
                                {files.map((file, index) => (
                                    <div
                                        key={index}
                                        className="flex items-center gap-2 rounded-lg border bg-neutral-50 p-3 dark:bg-neutral-800"
                                    >
                                        <FileText className="h-4 w-4 shrink-0 text-neutral-400" />
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

                        {/* Archivos existentes en edición */}
                        {isEditing &&
                            existingRecord?.media &&
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
                                                    {media.name}
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

                    {/* Acciones */}
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
                                  : 'Registrar Horas'}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    );
}
