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
}

interface DataTableProps<T> {
    data: T[]
    columns: React.ReactNode
    pagination?: PaginationInfo
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
}: {
    pagination?: PaginationInfo
}) {
    if (!pagination || pagination.last_page <= 1) return null

    const { links, current_page, last_page, total } = pagination

    // Filtrar links válidos (con URL)
    const validLinks = links.filter(link => link.url !== null || !link.url)
    const firstLink = links[0]
    const prevLink = links[current_page - 2]
    const nextLink = links[current_page]
    const lastLink = links[links.length - 1]

    return (
        <div className="flex items-center justify-between px-4 py-3">
            <div className="text-sm text-neutral-500">
                Página {current_page} de {last_page} ({total} resultados)
            </div>
            <div className="flex items-center gap-1">
                {/* Botón primera página */}
                {firstLink?.url ? (
                    <Button variant="ghost" size="icon" asChild className="h-8 w-8">
                        <a href={firstLink.url || undefined}>
                            <ChevronsLeft className="h-4 w-4" />
                        </a>
                    </Button>
                ) : (
                    <Button variant="ghost" size="icon" disabled className="h-8 w-8">
                        <ChevronsLeft className="h-4 w-4" />
                    </Button>
                )}

                {/* Botón página anterior */}
                {prevLink?.url ? (
                    <Button variant="ghost" size="icon" asChild className="h-8 w-8">
                        <a href={prevLink.url || undefined}>
                            <ChevronLeft className="h-4 w-4" />
                        </a>
                    </Button>
                ) : (
                    <Button variant="ghost" size="icon" disabled className="h-8 w-8">
                        <ChevronLeft className="h-4 w-4" />
                    </Button>
                )}

                {/* Números de página (limitado a 5 máximo) */}
                {links
                    .filter((_, i) => i > 0 && i < links.length - 1)
                    .slice(Math.max(0, current_page - 3), Math.min(links.length - 2, current_page + 1))
                    .map((link) => (
                        <Button
                            key={link.label}
                            variant={link.active ? "default" : "ghost"}
                            size="icon"
                            className="h-8 w-8"
                            disabled={!link.url}
                        >
                            <span dangerouslySetInnerHTML={{ __html: link.label }} />
                        </Button>
                    ))}

                {/* Botón página siguiente */}
                {nextLink?.url ? (
                    <Button variant="ghost" size="icon" asChild className="h-8 w-8">
                        <a href={nextLink.url || undefined}>
                            <ChevronRight className="h-4 w-4" />
                        </a>
                    </Button>
                ) : (
                    <Button variant="ghost" size="icon" disabled className="h-8 w-8">
                        <ChevronRight className="h-4 w-4" />
                    </Button>
                )}

                {/* Botón última página */}
                {lastLink?.url ? (
                    <Button variant="ghost" size="icon" asChild className="h-8 w-8">
                        <a href={lastLink.url || undefined}>
                            <ChevronsRight className="h-4 w-4" />
                        </a>
                    </Button>
                ) : (
                    <Button variant="ghost" size="icon" disabled className="h-8 w-8">
                        <ChevronsRight className="h-4 w-4" />
                    </Button>
                )}
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

            <DataTablePagination pagination={pagination} />
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