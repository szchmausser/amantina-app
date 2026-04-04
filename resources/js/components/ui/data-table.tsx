import * as React from "react"
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
    Search,
    X,
} from "lucide-react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select"
import { cn } from "@/lib/utils"

export interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

export interface PaginationInfo {
    links: PaginationLink[]
    total: number
    current_page: number
    last_page: number
    per_page?: number
}

interface DataTableProps<T> {
    data: T[]
    columns: React.ReactNode
    pagination?: PaginationInfo
    onPageChange?: (page: number, url: string) => void  // Callback para navegación
    perPage?: number  // Cantidad actual de registros por página
    onPerPageChange?: (perPage: number) => void  // Callback para cambio de registros por página
    perPageOptions?: number[]  // Opciones disponibles (ej: [7, 15, 25, 50, 100])
    searchable?: boolean
    searchPlaceholder?: string
    searchValue?: string
    onSearchChange?: (value: string) => void
    onSearch?: () => void
    searchLoading?: boolean
    onClearFilters?: () => void
    hasFilters?: boolean
    emptyMessage?: string
    className?: string
}

function DataTableSearch<T>({
    searchValue,
    onSearchChange,
    searchPlaceholder = "Buscar...",
    searchLoading = false,
    onClearFilters,
    hasFilters,
}: {
    searchValue?: string | null
    onSearchChange?: (value: string) => void
    searchPlaceholder?: string
    searchLoading?: boolean
    onClearFilters?: () => void
    hasFilters?: boolean
}) {
    return (
        <div className="relative flex-1">
            <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-neutral-400" />
            <Input
                placeholder={searchPlaceholder}
                className="pl-10 pr-8"
                value={searchValue || ''}
                onChange={(e) => onSearchChange?.(e.target.value)}
            />
            {searchValue && (
                <button
                    type="button"
                    onClick={() => onSearchChange?.("")}
                    className="absolute top-1/2 right-3 -translate-y-1/2 text-neutral-400 hover:text-neutral-600"
                >
                    <X className="h-4 w-4" />
                </button>
            )}
            {searchLoading && (
                <div className="absolute top-1/2 right-10 -translate-y-1/2">
                    <div className="h-3 w-3 animate-spin rounded-full border-2 border-neutral-300 border-t-neutral-600" />
                </div>
            )}
        </div>
    )
}

function DataTablePagination({
    pagination,
    onPageChange,
    perPage,
    onPerPageChange,
    perPageOptions = [5, 15, 25, 50, 100],
}: {
    pagination?: PaginationInfo
    onPageChange?: (page: number, url: string) => void
    perPage?: number
    onPerPageChange?: (perPage: number) => void
    perPageOptions?: number[]
}) {
    if (!pagination || pagination.last_page <= 1) return null

    const { links, current_page, last_page, total } = pagination

    // Extraer número de página de una URL
    const extractPageFromUrl = (url: string | null): number | null => {
        if (!url) return null
        const match = url.match(/[?&]page=(\d+)/)
        return match ? parseInt(match[1], 10) : 1 // Si no hay parámetro page, es página 1
    }

    // Construir URL para una página específica
    const buildUrlForPage = (pageNum: number): string | null => {
        // Buscar cualquier link válido para usar como base
        const baseLink = links.find(l => l.url !== null)
        if (!baseLink?.url) return null

        const url = new URL(baseLink.url, window.location.origin)
        if (pageNum === 1) {
            url.searchParams.delete('page')
        } else {
            url.searchParams.set('page', pageNum.toString())
        }
        return url.pathname + url.search
    }

    // Navegar a una página
    const navigateToPage = (pageNum: number) => {
        const url = buildUrlForPage(pageNum)
        if (url && onPageChange) {
            onPageChange(pageNum, url)
        }
    }

    // Encontrar URLs para navegación
    const firstUrl = current_page > 1 ? buildUrlForPage(1) : null
    const prevUrl = current_page > 1 ? buildUrlForPage(current_page - 1) : null
    const nextUrl = current_page < last_page ? buildUrlForPage(current_page + 1) : null
    const lastUrl = current_page < last_page ? buildUrlForPage(last_page) : null

    // Páginas visibles (mostrar alrededor de la página actual)
    const getVisiblePages = (): number[] => {
        const pages: number[] = []
        const start = Math.max(1, current_page - 2)
        const end = Math.min(last_page, current_page + 2)
        for (let i = start; i <= end; i++) {
            pages.push(i)
        }
        return pages
    }

    const visiblePages = getVisiblePages()

    return (
        <div className="flex items-center justify-between px-4 py-3">
            {/* Info de resultados y selector de registros por página */}
            <div className="flex items-center gap-4">
                <div className="text-sm text-neutral-500">
                    Página {current_page} de {last_page} ({total} resultados)
                </div>
                
                {/* Selector de registros por página */}
                {onPerPageChange && perPageOptions.length > 0 && (
                    <div className="flex items-center gap-2">
                        <span className="text-xs text-neutral-500">Mostrar:</span>
                        <Select
                            value={perPage?.toString() || perPageOptions[0].toString()}
                            onValueChange={(val) => onPerPageChange(parseInt(val, 10))}
                        >
                            <SelectTrigger className="h-8 w-20 text-xs">
                                <SelectValue placeholder={perPage?.toString() || perPageOptions[0].toString()} />
                            </SelectTrigger>
                            <SelectContent side="top">
                                {perPageOptions.map((option) => (
                                    <SelectItem
                                        key={option}
                                        value={option.toString()}
                                        className="text-xs"
                                    >
                                        {option}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                )}
            </div>

            {/* Controles de paginación */}
            <div className="flex items-center gap-2">
                {/* Selector de página rápido */}
                <div className="flex items-center gap-2 mr-4">
                    <span className="text-xs text-neutral-500">Ir a:</span>
                    <Select
                        value={current_page.toString()}
                        onValueChange={(val) => navigateToPage(parseInt(val, 10))}
                    >
                        <SelectTrigger className="h-8 w-20 text-xs">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent side="top" align="center">
                            {Array.from({ length: last_page }, (_, i) => i + 1).map((page) => (
                                <SelectItem
                                    key={page}
                                    value={page.toString()}
                                    className="text-xs"
                                >
                                    {page}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* Botón primera página */}
                <Button
                    variant="ghost"
                    size="icon"
                    className="h-8 w-8"
                    disabled={!firstUrl}
                    onClick={() => firstUrl && navigateToPage(1)}
                >
                    <ChevronsLeft className="h-4 w-4" />
                </Button>

                {/* Botón página anterior */}
                <Button
                    variant="ghost"
                    size="icon"
                    className="h-8 w-8"
                    disabled={!prevUrl}
                    onClick={() => prevUrl && navigateToPage(current_page - 1)}
                >
                    <ChevronLeft className="h-4 w-4" />
                </Button>

                {/* Números de página visibles */}
                {visiblePages.map((page) => (
                    <Button
                        key={page}
                        variant={page === current_page ? "default" : "ghost"}
                        size="icon"
                        className="h-8 w-8"
                        onClick={() => navigateToPage(page)}
                    >
                        {page}
                    </Button>
                ))}

                {/* Botón página siguiente */}
                <Button
                    variant="ghost"
                    size="icon"
                    className="h-8 w-8"
                    disabled={!nextUrl}
                    onClick={() => nextUrl && navigateToPage(current_page + 1)}
                >
                    <ChevronRight className="h-4 w-4" />
                </Button>

                {/* Botón última página */}
                <Button
                    variant="ghost"
                    size="icon"
                    className="h-8 w-8"
                    disabled={!lastUrl}
                    onClick={() => lastUrl && navigateToPage(last_page)}
                >
                    <ChevronsRight className="h-4 w-4" />
                </Button>
            </div>
        </div>
    )
}

function DataTableEmpty({
    emptyMessage = "No se encontraron resultados",
}: {
    emptyMessage?: string
}) {
    return (
        <div className="flex flex-col items-center justify-center py-16 text-center">
            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 dark:bg-neutral-800">
                <Search className="h-6 w-6 text-neutral-400" />
            </div>
            <h3 className="mt-4 text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                {emptyMessage}
            </h3>
            <p className="mt-1 text-sm text-neutral-500">
                Intenta con otros criterios de búsqueda
            </p>
        </div>
    )
}

function DataTableContent<T>({
    data,
    columns,
    emptyMessage,
}: {
    data: T[]
    columns: React.ReactNode
    emptyMessage?: string
}) {
    if (!data || data.length === 0) {
        return <DataTableEmpty emptyMessage={emptyMessage} />
    }

    return (
        <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <div className="overflow-x-auto">
                <table className="w-full text-left text-sm">
                    {columns}
                </table>
            </div>
        </div>
    )
}

export function DataTable<T>({
    data,
    columns,
    pagination,
    onPageChange,
    perPage,
    onPerPageChange,
    perPageOptions,
    searchable = false,
    searchPlaceholder,
    searchValue,
    onSearchChange,
    searchLoading = false,
    onClearFilters,
    hasFilters = false,
    emptyMessage = "No se encontraron resultados",
    className,
}: DataTableProps<T>) {
    return (
        <div className={cn("flex flex-col gap-4", className)}>
            {searchable && (
                <div className="flex items-center gap-3">
                    <DataTableSearch
                        searchValue={searchValue}
                        onSearchChange={onSearchChange}
                        searchPlaceholder={searchPlaceholder}
                        searchLoading={searchLoading}
                        hasFilters={hasFilters}
                    />
                    {hasFilters && onClearFilters && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={onClearFilters}
                            className="text-neutral-500 hover:text-neutral-700"
                        >
                            Limpiar filtros
                        </Button>
                    )}
                </div>
            )}

            <DataTableContent
                data={data}
                columns={columns}
                emptyMessage={emptyMessage}
            />

            <DataTablePagination 
                pagination={pagination} 
                onPageChange={onPageChange}
                perPage={perPage}
                onPerPageChange={onPerPageChange}
                perPageOptions={perPageOptions}
            />
        </div>
    )
}

// Componentes helpers para usar dentro de las columnas de la tabla
export function DataTableHead({
    children,
    className,
}: {
    children: React.ReactNode
    className?: string
}) {
    return (
        <thead className="border-b bg-neutral-50/50 text-xs uppercase text-neutral-500 dark:bg-neutral-800/30 dark:text-neutral-400">
            <tr className={className}>{children}</tr>
        </thead>
    )
}

export function DataTableTH({
    children,
    className,
}: {
    children: React.ReactNode
    className?: string
}) {
    return (
        <th className={cn("px-4 py-3 font-semibold text-neutral-600 dark:text-neutral-300", className)}>
            {children}
        </th>
    )
}

export function DataTableBody({
    children,
    className,
}: {
    children: React.ReactNode
    className?: string
}) {
    return (
        <tbody className={cn("divide-y divide-sidebar-border/70", className)}>
            {children}
        </tbody>
    )
}

export function DataTableTR({
    children,
    className,
    onClick,
}: {
    children: React.ReactNode
    className?: string
    onClick?: () => void
}) {
    return (
        <tr
            className={cn(
                "hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30 transition-colors",
                onClick && "cursor-pointer",
                className
            )}
            onClick={onClick}
        >
            {children}
        </tr>
    )
}

export function DataTableTD({
    children,
    className,
}: {
    children: React.ReactNode
    className?: string
}) {
    return (
        <td className={cn("px-4 py-3 text-neutral-600 dark:text-neutral-400", className)}>
            {children}
        </td>
    )
}