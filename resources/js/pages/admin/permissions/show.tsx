import { Head, Link, router } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import { ArrowLeft, ShieldCheck, Users } from 'lucide-react';
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

    const actionData: Record<string, { label: string, color: string, bg: string, desc: string }> = {
        view: { label: 'Visualización', color: 'text-primary', bg: 'bg-primary/10', desc: 'Consultar y visualizar registros' },
        read: { label: 'Visualización', color: 'text-primary', bg: 'bg-primary/10', desc: 'Consultar y visualizar registros' },
        create: { label: 'Creación', color: 'text-green-600', bg: 'bg-green-500/10', desc: 'Generar nuevos registros' },
        edit: { label: 'Edición', color: 'text-amber-600', bg: 'bg-amber-500/10', desc: 'Modificar datos existentes' },
        update: { label: 'Edición', color: 'text-amber-600', bg: 'bg-amber-500/10', desc: 'Modificar datos existentes' },
        delete: { label: 'Eliminación', color: 'text-red-600', bg: 'bg-red-500/10', desc: 'Eliminar registros del sistema' },
    };

    const currentAction = actionData[actionKey] || { label: 'Acción', color: 'text-neutral-600', bg: 'bg-neutral-100', desc: 'Realizar operaciones' };

    const moduleLabel = moduleKey
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase());

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
    };

    const handleClearFilters = () => {
        setSearch('');
        setRole('all');
        setPerPage(5);
    };

    const hasFilters = Boolean(search || role !== 'all' || perPage !== 5);

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
                <DataTableTH className="w-40 text-right">Roles</DataTableTH>
            </DataTableHead>
            <DataTableBody>
                {users.data.map((user, index) => (
                    <DataTableTR key={user.id}>
                        <DataTableTD className="font-mono text-xs text-neutral-400 dark:text-neutral-500">
                            {(users.current_page - 1) * (users.per_page || perPage) + index + 1}
                        </DataTableTD>
                        <DataTableTD className="font-mono text-neutral-500 dark:text-neutral-400">
                            {user.cedula}
                        </DataTableTD>
                        <DataTableTD>
                            <Link
                                href={userShow(user.id).url}
                                className="font-bold text-neutral-900 hover:text-primary dark:text-neutral-100"
                            >
                                {user.name}
                            </Link>
                        </DataTableTD>
                        <DataTableTD className="text-neutral-500 dark:text-neutral-400">
                            {user.email}
                        </DataTableTD>
                        <DataTableTD className="text-right">
                            <div className="flex flex-wrap gap-1 justify-end">
                                {user.roles?.map((r) => (
                                    <span
                                        key={r.name}
                                        className="inline-flex items-center rounded-lg bg-neutral-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400"
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

    const roleFilterSelect = (
        <Select value={role} onValueChange={handleRoleChange}>
            <SelectTrigger className="h-10 w-full sm:w-44 rounded-xl">
                <SelectValue placeholder="Filtrar por rol" />
            </SelectTrigger>
            <SelectContent className="rounded-xl">
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
                                {moduleLabel}: {actionKey.charAt(0).toUpperCase() + actionKey.slice(1)}
                            </h1>
                            <div className="flex items-center gap-2 mt-1">
                                <Badge variant="outline" className="text-[10px] text-neutral-500 border-neutral-200 dark:text-neutral-400 dark:border-neutral-700">
                                    {moduleLabel}
                                </Badge>
                                <Badge variant="outline" className="text-[10px] text-neutral-500 border-neutral-200 dark:text-neutral-400 dark:border-neutral-700">
                                    {permission.roles.length} Roles
                                </Badge>
                                <Badge variant="outline" className="text-[10px] text-neutral-500 border-neutral-200 dark:text-neutral-400 dark:border-neutral-700">
                                    {users.total} Usuarios
                                </Badge>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Button variant="outline" size="sm" onClick={() => window.history.back()}>
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver
                            </Button>
                        </div>
                    </div>

                    <div className="grid gap-6">
                        {/* Information Card - Vertical Stack matching Roles */}
                        <Card className="overflow-hidden rounded-xl border shadow-none p-0 gap-0">
                            <CardHeader className="border-b bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                <div className="flex items-center gap-2">
                                    <ShieldCheck className="h-4 w-4 text-neutral-500 dark:text-neutral-400" />
                                    <h2 className="text-sm font-semibold tracking-wide text-neutral-600 uppercase dark:text-neutral-300 dark:text-neutral-400">
                                        Información del Permiso
                                    </h2>
                                </div>
                            </CardHeader>
                            <CardContent className="p-6">
                                <div className="space-y-6">
                                    <div className="flex items-start gap-5">
                                        <div className={`rounded-xl p-3 ${currentAction.bg} dark:bg-neutral-800`}>
                                            <ShieldCheck className={`h-8 w-8 ${currentAction.color}`} />
                                        </div>
                                        <div className="space-y-1.5 pt-1">
                                            <h4 className="text-lg font-bold text-neutral-900 dark:text-neutral-100">
                                                Acción de {currentAction.label}
                                            </h4>
                                            <p className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                                {currentAction.desc} en el módulo de {moduleLabel.toLowerCase()}.
                                            </p>
                                        </div>
                                    </div>

                                    <div className="rounded-xl border border-neutral-100 bg-neutral-50/50 p-6 dark:border-neutral-800 dark:bg-neutral-800/30">
                                        <p className="text-base leading-relaxed text-neutral-600 dark:text-neutral-300 dark:text-neutral-400">
                                            Este permiso garantiza que los usuarios asignados puedan <span className="font-bold text-neutral-950 dark:text-neutral-50 underline decoration-primary/40 underline-offset-4 decoration-2">{currentAction.desc.toLowerCase()}</span> dentro de este componente. Es una capacidad clave para la gestión de datos en el sistema.
                                        </p>
                                    </div>

                                    <div className="space-y-4">
                                        <p className="text-xs font-bold uppercase tracking-widest text-neutral-400 pl-1 dark:text-neutral-500">
                                            Identificador del Sistema
                                        </p>
                                        <div className="flex items-center justify-between rounded-xl bg-neutral-100 px-4 py-3 dark:bg-neutral-800 border border-neutral-200/50 dark:border-neutral-700/50">
                                            <code className="text-sm font-mono font-bold text-neutral-600 dark:text-neutral-400">
                                                {permission.name}
                                            </code>
                                            <div className="flex gap-1">
                                                <div className="h-2 w-2 rounded-full bg-primary animate-pulse" />
                                                <div className="h-2 w-2 rounded-full bg-primary/40" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Roles Card - Vertical Stack matching Roles */}
                        <Card className="overflow-hidden rounded-xl border shadow-none p-0 gap-0">
                            <CardHeader className="border-b bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                <div className="flex items-center gap-2">
                                    <ShieldCheck className="h-4 w-4 text-neutral-500 dark:text-neutral-400" />
                                    <h2 className="text-sm font-semibold tracking-wide text-neutral-600 uppercase dark:text-neutral-300 dark:text-neutral-400">
                                        Roles con este permiso
                                    </h2>
                                </div>
                            </CardHeader>
                            <CardContent className="p-0">
                                {permission.roles.length > 0 ? (
                                    <div className="divide-y divide-neutral-100 dark:divide-neutral-800">
                                        {permission.roles.map((role) => (
                                            <Link
                                                key={role.id}
                                                href={roleShow(role.id).url}
                                                className="flex items-center justify-between px-6 py-4 transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-800/50"
                                            >
                                                <div className="flex items-center gap-3">
                                                    <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-neutral-100 dark:bg-neutral-800">
                                                        <ShieldCheck className="h-5 w-5 text-neutral-400 dark:text-neutral-500" />
                                                    </div>
                                                    <p className="text-sm font-bold capitalize text-neutral-700 dark:text-neutral-200 dark:text-neutral-300">
                                                        {role.name}
                                                    </p>
                                                </div>
                                                <div className="h-1.5 w-1.5 rounded-full bg-neutral-200" />
                                            </Link>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="flex flex-col items-center justify-center py-12 text-center text-neutral-400 dark:text-neutral-500">
                                        <ShieldCheck className="mb-3 h-12 w-12 opacity-10" />
                                        <p className="text-xs font-medium italic px-6">
                                            Ningún rol tiene este permiso asignado.
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Users Card - Vertical Stack matching Roles */}
                        <Card className="overflow-hidden rounded-xl border shadow-none p-0 gap-0">
                            <CardHeader className="border-b bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                <div className="flex items-center gap-2">
                                    <Users className="h-4 w-4 text-neutral-500 dark:text-neutral-400" />
                                    <h2 className="text-sm font-semibold tracking-wide text-neutral-600 uppercase dark:text-neutral-300 dark:text-neutral-400">
                                        Usuarios con este permiso
                                    </h2>
                                </div>
                            </CardHeader>
                            <CardContent className="p-0">
                                <div className="border-b bg-white dark:bg-neutral-900/50 px-6 py-4">
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
