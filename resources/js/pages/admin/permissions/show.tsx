import { Head, Link, router } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import { ShieldCheck, Users } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
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
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { index as permissionsIndex, users as permissionUsers } from '@/routes/admin/permissions';
import { show as roleShow } from '@/routes/admin/roles';
import { show as userShow } from '@/routes/admin/users';
import { useDebounce } from '@/hooks/use-debounce';
import type { BreadcrumbItem } from '@/types';

interface PermissionRole {
    id: number;
    name: string;
}

interface PermissionUser {
    id: number;
    name: string;
    cedula: string;
    email: string;
    roles: { name: string }[];
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedUsers {
    data: PermissionUser[];
    links: PaginationLink[];
    total: number;
    current_page: number;
    last_page: number;
}

interface Permission {
    id: number;
    name: string;
    roles: PermissionRole[];
}

interface Props {
    permission: Permission;
    users: PaginatedUsers;
    filters: {
        search?: string | null;
        role?: string | null;
        per_page?: number;
    };
    availableRoles: string[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Permisos', href: '/admin/permissions' },
    { title: 'Detalles del Permiso', href: '#' },
];

export default function Show({ permission, users, filters, availableRoles }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [role, setRole] = useState(filters.role || 'all');
    const [perPage, setPerPage] = useState(filters.per_page || 5);
    const [isSearching, setIsSearching] = useState(false);
    const isFirstRender = useRef(true);

    const debouncedSearch = useDebounce(search, 300);

    const parts = permission.name.split('.');
    const moduleKey = parts[0] || '';
    const actionKey = parts[1] || '';

    const actionDescriptions: Record<string, string> = {
        view: 'consultar y visualizar',
        create: 'crear nuevos registros de',
        edit: 'editar y modificar',
        delete: 'eliminar',
    };
    const actionDesc = actionDescriptions[actionKey] || actionKey;

    const moduleLabel = moduleKey
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase());

    // Effect that triggers search when debounced value, role, or perPage changes
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }

        router.get(
            permissionUsers(permission.id).url,
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
            permissionUsers(permission.id).url,
            {
                search,
                role: value === 'all' ? undefined : value,
                per_page: perPage,
            },
            { preserveState: true, replace: true },
        );
    };

    const handleClearFilters = () => {
        setSearch('');
        setRole('all');
        setPerPage(5);
    };

    const hasFilters = Boolean(search || role !== 'all' || perPage !== 5);

    // Prepare pagination for DataTable
    const pagination: PaginationInfo | undefined =
        users.last_page > 1
            ? {
                  links: users.links,
                  total: users.total,
                  current_page: users.current_page,
                  last_page: users.last_page,
              }
            : undefined;

    const tableColumns = (
        <>
            <DataTableHead>
                <DataTableTH className="w-16">#</DataTableTH>
                <DataTableTH className="w-32">Cédula</DataTableTH>
                <DataTableTH>Nombre</DataTableTH>
                <DataTableTH>Email</DataTableTH>
                <DataTableTH className="w-40">Rol</DataTableTH>
            </DataTableHead>
            <DataTableBody>
                {users.data.map((user, index) => (
                    <DataTableTR key={user.id}>
                        <DataTableTD className="font-mono text-xs text-neutral-400">
                            {(users.current_page - 1) * (users.per_page || perPage) + index + 1}
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
                    </DataTableTR>
                ))}
            </DataTableBody>
        </>
    );

    // Role filter select
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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Permiso: ${permission.name}`} />

            <SettingsLayout>
                <div className="px-4 py-4 space-y-6">
                    {/* Header */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                {actionKey.charAt(0).toUpperCase() + actionKey.slice(1)}
                            </h1>
                            <div className="flex items-center gap-2 mt-1">
                                <Badge variant="outline" className="text-[10px] text-neutral-500">
                                    {moduleLabel}
                                </Badge>
                                <Badge variant="outline" className="text-[10px] text-neutral-500">
                                    {permission.roles.length} Roles
                                </Badge>
                                <Badge variant="outline" className="text-[10px] text-neutral-500">
                                    {users.total} Usuarios
                                </Badge>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Button variant="outline" asChild>
                                <Link href={permissionsIndex().url}>
                                    Volver
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <div className="grid gap-6">
                        {/* Permission Info Card */}
                        <Card className="overflow-hidden rounded-xl border shadow-none">
                            <CardHeader className="border-b bg-neutral-50 px-6 py-4 dark:bg-neutral-800/50">
                                <CardTitle className="flex items-center gap-2 text-sm font-semibold">
                                    <ShieldCheck className="h-4 w-4 text-neutral-500" />
                                    Información del Permiso
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-6">
                                <div className="space-y-5">
                                    <div className="rounded-lg border bg-neutral-50 p-5 dark:bg-neutral-900/50">
                                        <p className="text-sm leading-relaxed text-neutral-700 dark:text-neutral-300">
                                            Este permiso permite al usuario <strong className="text-neutral-900 dark:text-neutral-100">puede {actionDesc}</strong> <strong className="text-neutral-900 dark:text-neutral-100">{moduleLabel.toLowerCase()}</strong> en el sistema.
                                        </p>
                                    </div>

                                    <div className="space-y-2">
                                        <h4 className="text-xs font-semibold tracking-wider text-neutral-500 uppercase">
                                            Código del Permiso
                                        </h4>
                                        <code className="block rounded-md border bg-neutral-50 px-3 py-2 text-xs font-mono text-neutral-600 dark:bg-neutral-900 dark:text-neutral-400">
                                            {permission.name}
                                        </code>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Roles with this permission */}
                        <Card className="overflow-hidden rounded-xl border shadow-none">
                            <CardHeader className="border-b bg-neutral-50 px-6 py-4 dark:bg-neutral-800/50">
                                <CardTitle className="flex items-center gap-2 text-sm font-semibold">
                                    <ShieldCheck className="h-4 w-4 text-neutral-500" />
                                    Roles que tienen este permiso
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-0">
                                {permission.roles.length > 0 ? (
                                    <div className="divide-y divide-border">
                                        {permission.roles.map((role) => (
                                            <Link
                                                key={role.id}
                                                href={roleShow(role.id).url}
                                                className="flex items-center justify-between px-6 py-3 transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-800/30"
                                            >
                                                <div className="flex items-center gap-3">
                                                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-neutral-100 dark:bg-neutral-800">
                                                        <ShieldCheck className="h-4 w-4 text-neutral-500" />
                                                    </div>
                                                    <p className="text-sm font-medium capitalize text-neutral-900 dark:text-neutral-100">
                                                        {role.name}
                                                    </p>
                                                </div>
                                            </Link>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="flex flex-col items-center justify-center py-12 text-center text-neutral-400">
                                        <ShieldCheck className="mb-2 h-10 w-10 opacity-20" />
                                        <p className="text-sm italic">
                                            Ningún rol tiene este permiso asignado.
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Users with this permission */}
                        <Card className="overflow-hidden rounded-xl border shadow-none">
                            <CardHeader className="border-b bg-neutral-50 px-6 py-4 dark:bg-neutral-800/50">
                                <CardTitle className="flex items-center gap-2 text-sm font-semibold">
                                    <Users className="h-4 w-4 text-neutral-500" />
                                    Usuarios con este permiso
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-0">
                                <div className="border-b px-4 py-3">
                                    <TableFilters
                                        searchValue={search}
                                        onSearchChange={setSearch}
                                        searchPlaceholder="Buscar por nombre, correo o cédula..."
                                        searchLoading={isSearching}
                                        filterSelect={roleFilterSelect}
                                        hasFilters={hasFilters}
                                        onClearFilters={handleClearFilters}
                                    />
                                </div>

                                <DataTable
                                    data={users.data}
                                    columns={tableColumns}
                                    pagination={pagination}
                                    onPageChange={(page, url) => {
                                        router.visit(url, {
                                            preserveState: true,
                                            replace: true,
                                        });
                                    }}
                                    perPage={perPage}
                                    onPerPageChange={setPerPage}
                                    perPageOptions={[5, 15, 25, 50, 100]}
                                    emptyMessage="No se encontraron usuarios que coincidan con los criterios de búsqueda."
                                />
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
