import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import { Edit, Eye, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import AppLayout from '@/layouts/app-layout';
import {
    index as userIndex,
    destroy as userDestroy,
    create as userCreate,
    edit as userEdit,
    show as userShow,
} from '@/routes/admin/users';
import type { BreadcrumbItem, User } from '@/types';
import { useDebounce } from '@/hooks/use-debounce';
import {
    DataTable,
    DataTableHead,
    DataTableTH,
    DataTableBody,
    DataTableTR,
    DataTableTD,
    type PaginationInfo,
} from '@/components/ui/data-table';
import { TableFilters } from '@/components/ui/table-filters';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedUsers {
    data: User[];
    links: PaginationLink[];
    total: number;
    current_page: number;
    last_page: number;
}

interface Props {
    users: PaginatedUsers;
    filters: {
        search?: string;
        role?: string;
        per_page?: number;
    };
    availableRoles: string[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Gestión de Usuarios',
        href: '/admin/users',
    },
];

export default function Index({ users, filters, availableRoles }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [role, setRole] = useState(filters.role || 'all');
    const [perPage, setPerPage] = useState(filters.per_page || 5);
    const [isSearching, setIsSearching] = useState(false);
    const [confirmDialogOpen, setConfirmDialogOpen] = useState(false);
    const [pendingDeleteId, setPendingDeleteId] = useState<number | null>(null);

    // Trackear si es la primera carga para evitar ejecución inmediata
    const isFirstRender = useRef(true);

    // Debounce del search para evitar múltiples llamadas mientras escribe
    const debouncedSearch = useDebounce(search, 300);

    // Efecto que ejecuta la búsqueda cuando el valor debounceado cambia
    useEffect(() => {
        // Saltar primera renderización - los filtros iniciales vienen del servidor
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }

        router.get(
            userIndex().url,
            {
                search: debouncedSearch || undefined,
                role: role === 'all' ? undefined : role,
                per_page: perPage,
            },
            {
                preserveState: true,
                replace: true,
                onFinish: () => setIsSearching(false),
            },
        );
        setIsSearching(true);
    }, [debouncedSearch, role, perPage]);

    const handleRoleChange = (value: string) => {
        setRole(value);
        router.get(
            userIndex().url,
            {
                search,
                role: value === 'all' ? undefined : value,
                per_page: perPage,
            },
            { preserveState: true, replace: true },
        );
    };

    const handleDelete = (id: number) => {
        setPendingDeleteId(id);
        setConfirmDialogOpen(true);
    };

    const confirmDelete = () => {
        if (!pendingDeleteId) return;
        router.delete(userDestroy(pendingDeleteId).url);
        setConfirmDialogOpen(false);
        setPendingDeleteId(null);
    };

    const handleClearFilters = () => {
        setSearch('');
        setRole('all');
        setPerPage(5);
    };

    const { auth } = usePage<any>().props;
    const hasPermission = (p: string) => auth.permissions.includes(p);

    const hasFilters = Boolean(search || role !== 'all' || perPage !== 5);

    // Preparar paginación para el componente
    const pagination: PaginationInfo | undefined =
        users.last_page > 1
            ? {
                  links: users.links,
                  total: users.total,
                  current_page: users.current_page,
                  last_page: users.last_page,
              }
            : undefined;

    // Columnas de la tabla
    const tableColumns = (
        <>
            <DataTableHead>
                <DataTableTH className="w-16">#</DataTableTH>
                <DataTableTH className="w-32">Cédula</DataTableTH>
                <DataTableTH>Nombre</DataTableTH>
                <DataTableTH>Email</DataTableTH>
                <DataTableTH className="w-40">Rol</DataTableTH>
                <DataTableTH className="w-24 text-right">Acciones</DataTableTH>
            </DataTableHead>
            <DataTableBody>
                {users.data.map((user, index) => (
                    <DataTableTR key={user.id}>
                        <DataTableTD className="font-mono text-xs text-neutral-400">
                            {(users.current_page - 1) * perPage + index + 1}
                        </DataTableTD>
                        <DataTableTD className="font-mono text-neutral-500">
                            {user.cedula}
                        </DataTableTD>
                        <DataTableTD>
                            <Link
                                href={userShow(user.id).url}
                                className="font-medium text-neutral-900 hover:text-blue-600 dark:text-neutral-100"
                            >
                                {user.name}
                            </Link>
                        </DataTableTD>
                        <DataTableTD className="text-neutral-500">
                            {user.email}
                        </DataTableTD>
                        <DataTableTD>
                            <div className="flex flex-wrap gap-1">
                                {user.roles?.map((r) => (
                                    <span
                                        key={r.name}
                                        className="inline-flex items-center rounded-full bg-neutral-100 px-2.5 py-0.5 text-xs font-medium text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300"
                                    >
                                        {r.name}
                                    </span>
                                ))}
                            </div>
                        </DataTableTD>
                        <DataTableTD className="text-right">
                            <div className="flex items-center justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="h-8 w-8 text-neutral-500 hover:text-blue-600"
                                    asChild
                                >
                                    <Link href={userShow(user.id).url}>
                                        <Eye className="h-4 w-4" />
                                        <span className="sr-only">Ver</span>
                                    </Link>
                                </Button>
                                {(hasPermission('users.edit') ||
                                    auth.user.id === user.id) && (
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="h-8 w-8"
                                        asChild
                                    >
                                        <Link href={userEdit(user.id).url}>
                                            <Edit className="h-4 w-4" />
                                            <span className="sr-only">
                                                Editar
                                            </span>
                                        </Link>
                                    </Button>
                                )}
                                {hasPermission('users.delete') && (
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="h-8 w-8 text-red-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/30"
                                        onClick={() => handleDelete(user.id)}
                                    >
                                        <Trash2 className="h-4 w-4" />
                                        <span className="sr-only">
                                            Eliminar
                                        </span>
                                    </Button>
                                )}
                            </div>
                        </DataTableTD>
                    </DataTableTR>
                ))}
            </DataTableBody>
        </>
    );

    // Selector de filtro por rol
    const roleFilterSelect = (
        <Select value={role} onValueChange={handleRoleChange}>
            <SelectTrigger className="h-10 w-full sm:w-44">
                <SelectValue placeholder="Filtrar por rol" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="all">Todos los roles</SelectItem>
                {availableRoles.map((r) => (
                    <SelectItem key={r} value={r}>
                        {r.charAt(0).toUpperCase() + r.slice(1)}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );

    // Botón crear usuario
    const createButton = hasPermission('users.create') ? (
        <Button asChild className="sm:ml-auto">
            <Link href={userCreate().url}>
                <Plus className="mr-2 h-4 w-4" />
                Nuevo Usuario
            </Link>
        </Button>
    ) : undefined;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gestión de Usuarios" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 lg:p-8">
                {/* Título */}
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                        Usuarios
                    </h1>
                    <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        Administra las cuentas de usuario, roles y permisos del
                        sistema.
                    </p>
                </div>

                {/* Filtros usando componente reutilizable */}
                <TableFilters
                    searchValue={search}
                    onSearchChange={setSearch}
                    searchPlaceholder="Buscar por nombre, correo o cédula..."
                    searchLoading={isSearching}
                    filterSelect={roleFilterSelect}
                    hasFilters={hasFilters}
                    onClearFilters={handleClearFilters}
                    createButton={createButton}
                />

                {/* Tabla */}
                <DataTable
                    data={users.data}
                    columns={tableColumns}
                    pagination={pagination}
                    onPageChange={(page, url) => {
                        router.get(
                            url,
                            {
                                search: search || undefined,
                                role: role === 'all' ? undefined : role,
                                per_page: perPage,
                            },
                            {
                                preserveState: true,
                                replace: true,
                            },
                        );
                    }}
                    perPage={perPage}
                    onPerPageChange={setPerPage}
                    perPageOptions={[5, 15, 25, 50, 100]}
                    emptyMessage="No se encontraron usuarios que coincidan con los criterios de búsqueda."
                />
            </div>

            {/* Confirmation Dialog */}
            <AlertDialog
                open={confirmDialogOpen}
                onOpenChange={setConfirmDialogOpen}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            Confirmar Eliminación
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            ¿Estás seguro de que deseas eliminar este usuario?
                            Esta acción no se puede deshacer.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={confirmDelete}
                            data-test="confirm-delete-button"
                            className="bg-red-600 hover:bg-red-700"
                        >
                            Eliminar
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
