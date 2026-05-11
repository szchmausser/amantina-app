import { useState, useEffect, useCallback } from 'react';
import { X, ChevronLeft, ChevronRight, ImageIcon, Film } from 'lucide-react';
import * as DialogPrimitive from '@radix-ui/react-dialog';

interface MediaItem {
    id: number;
    url: string;
    name: string;
}

interface MediaGalleryProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    items: MediaItem[];
    initialIndex?: number;
}

function isVideo(url: string): boolean {
    const videoExtensions = ['mp4', 'webm', 'mov', 'avi', 'mkv'];
    const ext = url.split('.').pop()?.toLowerCase() || '';
    return videoExtensions.includes(ext);
}

export function MediaGallery({
    open,
    onOpenChange,
    items,
    initialIndex = 0,
}: MediaGalleryProps) {
    const [currentIndex, setCurrentIndex] = useState(initialIndex);

    const goTo = useCallback(
        (index: number) => {
            if (index < 0) {
                setCurrentIndex(items.length - 1);
            } else if (index >= items.length) {
                setCurrentIndex(0);
            } else {
                setCurrentIndex(index);
            }
        },
        [items.length],
    );

    useEffect(() => {
        if (open) {
            setCurrentIndex(initialIndex);
        }
    }, [open, initialIndex]);

    useEffect(() => {
        if (!open) return;

        const handleKeyDown = (e: KeyboardEvent) => {
            if (e.key === 'ArrowLeft') {
                goTo(currentIndex - 1);
            } else if (e.key === 'ArrowRight') {
                goTo(currentIndex + 1);
            } else if (e.key === 'Escape') {
                onOpenChange(false);
            }
        };

        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [open, currentIndex, goTo, onOpenChange]);

    if (items.length === 0) return null;

    const currentItem = items[currentIndex];
    const isCurrentVideo = isVideo(currentItem.url);

    return (
        <DialogPrimitive.Root open={open} onOpenChange={onOpenChange}>
            <DialogPrimitive.Portal>
                {/* Overlay clickeable para cerrar */}
                <DialogPrimitive.Overlay
                    data-slot="dialog-overlay"
                    className="fixed inset-0 z-50 bg-black/90 backdrop-blur-sm transition-all duration-300"
                    onClick={() => onOpenChange(false)}
                />

                {/* Content container — clic en el fondo vacío cierra la galería */}
                <DialogPrimitive.Content
                    data-slot="dialog-content"
                    className="fixed inset-0 z-50 flex items-center justify-center outline-none"
                    onClick={(e) => {
                        if (e.target === e.currentTarget) {
                            onOpenChange(false);
                        }
                    }}
                >
                    {/* Wrapper que bloquea el click-through para elementos internos */}
                    <div className="relative flex h-full w-full max-w-5xl flex-col items-center justify-center px-16 py-12">
                        {/* Close button */}
                        <button
                            className="absolute top-4 right-4 z-50 rounded-full bg-black/50 p-2 text-white transition hover:bg-black/70"
                            onClick={(e) => {
                                e.stopPropagation();
                                onOpenChange(false);
                            }}
                            aria-label="Cerrar galería"
                        >
                            <X className="h-5 w-5" />
                        </button>

                        {/* Navigation - Previous */}
                        {items.length > 1 && (
                            <button
                                className="absolute left-4 z-50 rounded-full bg-black/50 p-2 text-white transition hover:bg-black/70"
                                onClick={(e) => {
                                    e.stopPropagation();
                                    goTo(currentIndex - 1);
                                }}
                                aria-label="Anterior"
                            >
                                <ChevronLeft className="h-6 w-6" />
                            </button>
                        )}

                        {/* Navigation - Next */}
                        {items.length > 1 && (
                            <button
                                className="absolute right-4 z-50 rounded-full bg-black/50 p-2 text-white transition hover:bg-black/70"
                                onClick={(e) => {
                                    e.stopPropagation();
                                    goTo(currentIndex + 1);
                                }}
                                aria-label="Siguiente"
                            >
                                <ChevronRight className="h-6 w-6" />
                            </button>
                        )}

                        {/* Media Display */}
                        <div className="flex h-full w-full flex-col items-center justify-center">
                            <div className="relative flex max-h-[70vh] w-full items-center justify-center">
                                {isCurrentVideo ? (
                                    <video
                                        key={currentItem.id}
                                        src={currentItem.url}
                                        controls
                                        className="max-h-[65vh] max-w-full rounded-lg shadow-2xl"
                                        autoPlay
                                        onClick={(e) => e.stopPropagation()}
                                    />
                                ) : (
                                    <img
                                        key={currentItem.id}
                                        src={currentItem.url}
                                        alt={currentItem.name}
                                        className="max-h-[65vh] max-w-full rounded-lg object-contain shadow-2xl"
                                        onClick={(e) => e.stopPropagation()}
                                    />
                                )}
                            </div>

                            {/* Info bar */}
                            <div className="mt-4 flex items-center gap-3 text-white/80">
                                {isCurrentVideo ? (
                                    <Film className="h-4 w-4" />
                                ) : (
                                    <ImageIcon className="h-4 w-4" />
                                )}
                                <span className="text-sm font-medium">
                                    {currentItem.name}
                                </span>
                                <span className="text-sm text-white/50">
                                    {currentIndex + 1} / {items.length}
                                </span>
                            </div>

                            {/* Thumbnails strip */}
                            {items.length > 1 && (
                                <div className="mt-4 flex max-w-full gap-2 overflow-x-auto px-2 py-1">
                                    {items.map((item, idx) => {
                                        const itemIsVideo = isVideo(item.url);
                                        return (
                                            <button
                                                key={item.id}
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    goTo(idx);
                                                }}
                                                className={`relative shrink-0 overflow-hidden rounded border-2 transition ${
                                                    idx === currentIndex
                                                        ? 'border-white'
                                                        : 'border-transparent opacity-60 hover:opacity-100'
                                                }`}
                                            >
                                                {itemIsVideo ? (
                                                    <div className="flex h-12 w-16 items-center justify-center bg-neutral-800">
                                                        <Film className="h-4 w-4 text-white" />
                                                    </div>
                                                ) : (
                                                    <img
                                                        src={item.url}
                                                        alt={item.name}
                                                        className="h-12 w-16 object-cover"
                                                    />
                                                )}
                                            </button>
                                        );
                                    })}
                                </div>
                            )}
                        </div>
                    </div>
                </DialogPrimitive.Content>
            </DialogPrimitive.Portal>
        </DialogPrimitive.Root>
    );
}
