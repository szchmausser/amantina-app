import { useForm } from '@inertiajs/react';
import { Upload, X } from 'lucide-react';
import { useCallback, useState } from 'react';
import InstitutionController from '@/actions/App/Http/Controllers/Settings/InstitutionController';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';

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
            onSuccess: () => {
                setPreview(null);
            },
        });
    }, [post]);

    const handleRemove = useCallback(() => {
        removeLogo(InstitutionController.removeLogo.url(), {
            onSuccess: () => {
                setPreview(null);
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

            {preview && (
                <div className="flex gap-2">
                    <Button
                        type="button"
                        size="sm"
                        onClick={handleUpload}
                        disabled={processing}
                        data-testid="logo-save-btn"
                    >
                        Guardar
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => setPreview(null)}
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
                JPG, PNG, GIF o WebP. Máximo 2MB.
            </p>
        </div>
    );
}
