import { Search, X, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

interface TableFiltersProps {
    // Buscador
    searchValue?: string;
    onSearchChange?: (value: string) => void;
    searchPlaceholder?: string;
    searchLoading?: boolean;
    
    // Select filter
    filterSelect?: React.ReactNode;
    
    // Limpiar filtros
    hasFilters?: boolean;
    onClearFilters?: () => void;
    
    // Botón crear
    createButton?: React.ReactNode;
    
    // Clases adicionales
    className?: string;
}

export function TableFilters({
    searchValue,
    onSearchChange,
    searchPlaceholder = 'Buscar...',
    searchLoading = false,
    filterSelect,
    hasFilters = false,
    onClearFilters,
    createButton,
    className,
}: TableFiltersProps) {
    return (
        <div className={cn('flex flex-col gap-4 sm:flex-row sm:items-center sm:gap-3', className)}>
            {/* Buscador */}
            <div className="relative max-w-md flex-1">
                <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-neutral-400" />
                <Input
                    placeholder={searchPlaceholder}
                    className="pr-8 pl-10"
                    value={searchValue || ''}
                    onChange={(e) => onSearchChange?.(e.target.value)}
                />
                {searchValue && (
                    <button
                        type="button"
                        onClick={() => onSearchChange?.('')}
                        className="absolute top-1/2 right-3 -translate-y-1/2 text-neutral-400 hover:text-neutral-600"
                    >
                        <X className="h-4 w-4" />
                    </button>
                )}
            </div>

            {/* Selector de filtro (ej: rol, grado, etc) */}
            {filterSelect}

            {/* Botón limpiar filtros */}
            {hasFilters && onClearFilters && (
                <Button
                    variant="outline"
                    size="sm"
                    onClick={onClearFilters}
                    className="h-10 border-red-200 text-red-600 hover:bg-red-50 hover:text-red-700"
                >
                    <X className="mr-1 h-4 w-4" />
                    Limpiar
                </Button>
            )}

            {/* Indicador de carga - a la derecha del botón limpiar */}
            {searchLoading && (
                <span className="flex items-center text-xs text-neutral-400">
                    <Loader2 className="mr-1 h-3 w-3 animate-spin" />
                    Buscando...
                </span>
            )}

            {/* Botón crear - a la derecha */}
            {createButton}
        </div>
    );
}