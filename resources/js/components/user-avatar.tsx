import { useCallback, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Upload, X } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/hooks/use-initials';

interface UserAvatarProps {
    name: string;
    avatarUrl?: string | null;
    avatarUpdateUrl: string;
    avatarRemoveUrl: string;
}

export function UserAvatar({
    name,
    avatarUrl,
    avatarUpdateUrl,
    avatarRemoveUrl,
}: UserAvatarProps) {
    const getInitials = useInitials();
    const [preview, setPreview] = useState<string | null>(null);
    const [isHovered, setIsHovered] = useState(false);

    const {
        post,
        delete: removeAvatar,
        processing,
        setData,
    } = useForm({
        avatar: null as File | null,
    });

    const handleFileChange = useCallback(
        (e: React.ChangeEvent<HTMLInputElement>) => {
            const file = e.target.files?.[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = () => {
                setPreview(reader.result as string);
            };
            reader.readAsDataURL(file);

            setData('avatar', file);
        },
        [setData],
    );

    const handleUpload = useCallback(() => {
        post(avatarUpdateUrl, {
            forceFormData: true,
            onSuccess: () => {
                setPreview(null);
            },
        });
    }, [post, avatarUpdateUrl]);

    const handleRemove = useCallback(() => {
        removeAvatar(avatarRemoveUrl, {
            onSuccess: () => {
                setPreview(null);
            },
        });
    }, [removeAvatar, avatarRemoveUrl]);

    const displayUrl = preview || avatarUrl;
    const initials = getInitials(name);

    return (
        <div className="flex flex-col items-center gap-4">
            <div
                className="group relative"
                onMouseEnter={() => setIsHovered(true)}
                onMouseLeave={() => setIsHovered(false)}
            >
                <Avatar className="h-24 w-24 ring-2 ring-neutral-200 ring-offset-2 dark:ring-neutral-700">
                    {displayUrl ? (
                        <AvatarImage src={displayUrl} alt={name} />
                    ) : (
                        <AvatarFallback className="text-2xl font-semibold">
                            {initials}
                        </AvatarFallback>
                    )}
                </Avatar>

                {(isHovered || preview) && (
                    <div className="absolute inset-0 flex items-center justify-center rounded-full bg-black/50">
                        <label
                            htmlFor="avatar-input"
                            className="flex cursor-pointer flex-col items-center gap-1 text-white"
                        >
                            <Upload className="h-6 w-6" />
                            <span className="text-xs">Cambiar</span>
                        </label>
                    </div>
                )}

                <input
                    id="avatar-input"
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
                    >
                        Guardar
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => setPreview(null)}
                    >
                        <X className="h-4 w-4" />
                    </Button>
                </div>
            )}

            {avatarUrl && !preview && (
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={handleRemove}
                    disabled={processing}
                    className="text-destructive hover:text-destructive"
                >
                    Eliminar foto
                </Button>
            )}

            <p className="text-xs text-muted-foreground">
                JPG, PNG, GIF o WebP. Máximo 2MB.
            </p>
        </div>
    );
}
