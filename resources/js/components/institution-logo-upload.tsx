import { useForm } from '@inertiajs/react';
import { AlertCircle, Save, Upload, X } from 'lucide-react';
import { useCallback, useState } from 'react';
import InstitutionController from '@/actions/App/Http/Controllers/Settings/InstitutionController';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';

const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

interface InstitutionLogoUploadProps {
    logoUrl: string | null;
    institutionName: string;
}

export function InstitutionLogoUpload({
    logoUrl,
    institutionName,
}: InstitutionLogoUploadProps) {
    const [preview, setPreview] = useState<string | null>(null);
    const [isHovered, setIsHovered] = useState(false);
    const [fileError, setFileError] = useState<string | null>(null);

    const {
        post,
        delete: removeLogo,
        processing,
        setData,
    } = useForm({
        logo: null as File | null,
    });

    const handleFileChange = useCallback(
        (e: React.ChangeEvent<HTMLInputElement>) => {
            const file = e.target.files?.[0];

            if (!file) {
                return;
            }

            // Validate file size
            if (file.size > MAX_FILE_SIZE) {
                const sizeMB = (file.size / (1024 * 1024)).toFixed(1);
                setFileError(
                    `El archivo pesa ${sizeMB}MB. El tamaño máximo permitido es 10MB.`,
                );
                setPreview(null);
                setData('logo', null);
                return;
            }

            setFileError(null);

            const reader = new FileReader();
            reader.onload = () => {
                setPreview(reader.result as string);
            };
            reader.readAsDataURL(file);

            setData('logo', file);
        },
        [setData],
    );

    const handleUpload = useCallback(() => {
        post(InstitutionController.updateLogo.url(), {
            forceFormData: true,
            onError: (errors) => {
                if (errors.logo) {
                    setFileError(errors.logo);
                }
            },
            onSuccess: () => {
                setPreview(null);
                setFileError(null);
            },
        });
    }, [post]);

    const handleRemove = useCallback(() => {
        removeLogo(InstitutionController.removeLogo.url(), {
            onSuccess: () => {
                setPreview(null);
                setFileError(null);
            },
        });
    }, [removeLogo]);

    const displayUrl = preview || logoUrl;
    const initials = institutionName.charAt(0).toUpperCase();

    return (
        <div className="flex flex-col items-center gap-4">
            <div
                className="group relative"
                onMouseEnter={() => setIsHovered(true)}
                onMouseLeave={() => setIsHovered(false)}
            >
                <Avatar className="h-24 w-24 ring-2 ring-neutral-200 ring-offset-2 dark:ring-neutral-700">
                    {displayUrl ? (
                        <AvatarImage
                            src={displayUrl}
                            alt={institutionName}
                            data-testid="logo-preview"
                        />
                    ) : (
                        <AvatarFallback className="text-2xl font-semibold">
                            {initials}
                        </AvatarFallback>
                    )}
                </Avatar>

                {(isHovered || preview) && (
                    <div className="absolute inset-0 flex items-center justify-center rounded-full bg-black/50">
                        <label
                            htmlFor="institution-logo-input"
                            className="flex cursor-pointer flex-col items-center gap-1 text-white"
                        >
                            <Upload className="h-6 w-6" />
                            <span className="text-xs">Cambiar</span>
                        </label>
                    </div>
                )}

                <input
                    id="institution-logo-input"
                    data-testid="logo-upload-input"
                    type="file"
                    accept="image/jpeg,image/png,image/gif,image/webp"
                    className="hidden"
                    onChange={handleFileChange}
                />
            </div>

            {fileError && (
                <Alert variant="destructive" className="max-w-xs">
                    <AlertCircle className="h-4 w-4" />
                    <AlertDescription>{fileError}</AlertDescription>
                </Alert>
            )}

            {preview && (
                <div className="flex gap-2">
                    <Button
                        type="button"
                        size="sm"
                        onClick={handleUpload}
                        disabled={processing || !!fileError}
                        data-testid="logo-save-btn"
                    >
                        <Save className="mr-2 h-4 w-4" />
                        Guardar
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => {
                            setPreview(null);
                            setFileError(null);
                        }}
                        data-testid="logo-cancel-btn"
                    >
                        <X className="h-4 w-4" />
                    </Button>
                </div>
            )}

            {logoUrl && !preview && (
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={handleRemove}
                    disabled={processing}
                    className="text-destructive hover:text-destructive"
                    data-testid="logo-remove-btn"
                >
                    Eliminar logo
                </Button>
            )}

            <p className="text-xs text-muted-foreground">
                JPG, PNG, GIF o WebP. Máximo 10MB.
            </p>
        </div>
    );
}
