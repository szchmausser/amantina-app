import { Head, Link, router } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import { Edit, Eye, Info, Pencil, PlusCircle, Shield, ShieldCheck, Trash2, Users } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import { Card, CardHeader, CardContent } from '@/components/ui/card';
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
import { index as roleIndex, edit as roleEdit, users as roleUsers } from '@/routes/admin/roles';
import { show as userShow } from '@/routes/admin/users';
import { show as permissionShow } from '@/routes/admin/permissions';
import { useDebounce } from '@/hooks/use-debounce';
import type { BreadcrumbItem } from '@/types';

interface Permission {
    id: number;
    name: string;
}

interface RoleUser {
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
    data: RoleUser[];
    links: PaginationLink[];
    total: number;
    current_page: number;
    last_page: number;
}

interface Role {
    id: number;
    name: string;
    permissions: Permission[];
}

interface Props {
    role: Role;
    users: PaginatedUsers;
    filters: {
        search?: string | null;
        per_page?: number;
    };
}

const ROLE_DESCRIPTIONS: Record<string, string> = {
    admin: 'Acceso total al sistema. Responsable de la configuración global, gestión de usuarios, roles y parámetros institucionales.',
    profesor: 'Encargado de la gestión académica de la asignatura Socioproductiva. Registra actividades, controla asistencias y evalúa el desempeño de los estudiantes.',
    alumno: 'Estudiante inscrito en el sistema. Puede consultar su progreso, horas acumuladas y detalles de las actividades en las que participa.',
    representante: 'Padre o tutor legal. Tiene acceso para supervisar el cumplimiento de las horas y el progreso académico de su representado.',
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Roles', href: '/admin/roles' },
    { title: 'Detalles del Rol', href: '#' },
];

function groupPermissions(permissions: Permission[]): Record<string, Permission[]> {
    const groups: Record<string, Permission[]> = {};
    permissions.forEach((p) => {
        const module = p.name.split('.')[0];
        if (!groups[module]) {
            groups[module] = [];
        }
        groups[module].push(p);
    });
    return groups;
}

const ACTION_ICONS: Record<string, React.ElementType> = {
    create: PlusCircle,
    edit: Pencil,
    delete: Trash2,
    view: Eye,
    read: Eye,
    update: Pencil,
};

function getActionIcon(permName: string): React.ElementType {
    const action = permName.split('.').pop() ?? '';
    return ACTION_ICONS[action] ?? Shield;
}

function formatModuleName(name: string): string {
    return name
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

export default function Show({ role, users, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [perPage, setPerPage] = useState(filters.per_page || 5);
    const [isSearching, setIsSearching] = useState(false);
    const [selectedPerm, setSelectedPerm] = useState<string | null>(null);
    const isFirstRender = useRef(true);

    const debouncedSearch = useDebounce(search, 300);

    const isProtected = ['admin', 'profesor', 'alumno', 'representante'].includes(role.name);

    const groups = groupPermissions(role.permissions);

    // Effect that triggers search when debounced value or perPage changes
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }

        router.get(
            roleUsers(role.id).url,
            {
                search: debouncedSearch || undefined,
                per_page: perPage,
            },
            {
                preserveState: true,
                replace: true,
                onFinish: () => setIsSearching(false),
            },
        );
        setIsSearching(true);
    }, [debouncedSearch, perPage]);

    const handleClearFilters = () => {
        setSearch('');
        setPerPage(5);
    };

    const hasFilters = Boolean(search || perPage !== 5);

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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Rol: ${role.name}`} />

            <SettingsLayout>
                <div className="px-4 py-4 space-y-6">
                    {/* Header */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                {role.name.charAt(0).toUpperCase() + role.name.slice(1)}
                            </h1>
                            <div className="flex items-center gap-2 mt-1">
                                {isProtected ? (
                                    <Badge variant="secondary" className="bg-primary/10 text-primary hover:bg-primary/20 border-transparent text-[10px] font-bold">
                                        Rol del Sistema
                                    </Badge>
                                ) : (
                                    <Badge variant="outline" className="text-[10px]">
                                        Rol Personalizado
                                    </Badge>
                                )}
                                <Badge variant="outline" className="text-[10px] text-neutral-500 border-neutral-200">
                                    {role.permissions.length} Permisos
                                </Badge>
                                <Badge variant="outline" className="text-[10px] text-neutral-500 border-neutral-200">
                                    {users.total} Usuarios
                                </Badge>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Button variant="outline" asChild>
                                <Link href={roleIndex().url}>
                                    Volver
                                </Link>
                            </Button>
                            <Button asChild>
                                <Link href={roleEdit({ role: role.id }).url}>
                                    <Edit className="mr-2 h-4 w-4" />
                                    Editar Permisos
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <div className="grid gap-6">
                        {/* Information Card - Vertical Stack matching Permissions */}
                        <Card className="overflow-hidden rounded-xl border shadow-none p-0 gap-0">
                            <CardHeader className="border-b bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                <div className="flex items-center gap-2">
                                    <Info className="h-4 w-4 text-neutral-500" />
                                    <h2 className="text-sm font-semibold tracking-wide text-neutral-600 uppercase dark:text-neutral-300">
                                        Información del Rol
                                    </h2>
                                </div>
                            </CardHeader>
                            <CardContent className="p-6">
                                <div className="space-y-6">
                                    <div className="flex items-start gap-5">
                                        <div className={`rounded-xl p-3 ${isProtected ? 'bg-primary/10' : 'bg-neutral-100'} dark:bg-neutral-800`}>
                                            <Shield className={`h-8 w-8 ${isProtected ? 'text-primary' : 'text-neutral-500'}`} />
                                        </div>
                                        <div className="space-y-1.5 pt-1">
                                            <h4 className="text-lg font-bold text-neutral-900 dark:text-neutral-100">
                                                {isProtected ? 'Rol del Sistema' : 'Rol Personalizado'}
                                            </h4>
                                            <p className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                                {ROLE_DESCRIPTIONS[role.name.toLowerCase()] || 'Gestión de permisos y accesos para este rol personalizado.'}
                                            </p>
                                        </div>
                                    </div>

                                    <div className={`rounded-xl border p-6 transition-all ${isProtected ? 'border-blue-100 bg-blue-50/30 dark:border-blue-900/20 dark:bg-blue-950/10' : 'border-neutral-100 bg-neutral-50/50 dark:border-neutral-800 dark:bg-neutral-800/30'}`}>
                                        <p className="text-base leading-relaxed text-neutral-600 dark:text-neutral-300">
                                            {isProtected 
                                                ? `Este es un rol fundamental del sistema. Su propósito está protegido para garantizar la integridad operativa, permitiendo una gestión precisa de las capacidades de ${role.name.toLowerCase()}.`
                                                : `Este es un rol personalizado creado para extender las capacidades del sistema, permitiendo definir un conjunto específico de permisos para los usuarios asignados.`
                                            }
                                        </p>
                                    </div>

                                    <div className="space-y-4">
                                        <p className="text-xs font-bold uppercase tracking-widest text-neutral-400 pl-1">
                                            Identificador del Sistema
                                        </p>
                                        <div className="flex items-center justify-between rounded-xl bg-neutral-100 px-4 py-3 dark:bg-neutral-800 border border-neutral-200/50 dark:border-neutral-700/50">
                                            <code className="text-sm font-mono font-bold text-neutral-600 dark:text-neutral-400">
                                                {role.name}
                                            </code>
                                            <div className="flex gap-1">
                                                <div className={`h-2 w-2 rounded-full ${isProtected ? 'bg-primary animate-pulse' : 'bg-neutral-400'}`} />
                                                <div className={`h-2 w-2 rounded-full ${isProtected ? 'bg-primary/40' : 'bg-neutral-300'}`} />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                        {/* Permissions Card - Vertical Stack matching Permissions */}
                        <Card className="overflow-hidden rounded-xl border shadow-none p-0 gap-0">
                            <CardHeader className="border-b bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                <div className="flex items-center gap-2">
                                    <ShieldCheck className="h-4 w-4 text-neutral-500" />
                                    <h2 className="text-sm font-semibold tracking-wide text-neutral-600 uppercase dark:text-neutral-300">
                                        Permisos de este Rol
                                    </h2>
                                </div>
                            </CardHeader>
                            <CardContent className="p-6">
                                <div className="mb-4 flex items-center justify-between px-1">
                                    <span className="text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                        Módulo
                                    </span>
                                    <span className="text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                        Permisos
                                    </span>
                                </div>
                                {role.permissions.length > 0 ? (
                                    <div className="grid gap-4 grid-cols-1">
                                        {Object.entries(groups).map(
                                            ([module, modulePerms]) => (
                                                <div
                                                    key={module}
                                                    className="flex items-center justify-between gap-3 rounded-lg border border-neutral-200 bg-neutral-50/50 px-3 py-2 dark:border-neutral-700/50 dark:bg-neutral-800/30"
                                                >
                                                     <span className="inline-flex shrink-0 items-center rounded-md border border-neutral-200 px-2 py-0.5 text-xs font-medium capitalize text-neutral-600 dark:border-neutral-700 dark:text-neutral-400">
                                                         {formatModuleName(module)}
                                                     </span>
                                                     <div className="flex flex-wrap gap-1.5 justify-end">
                                                        {modulePerms.map(
                                                            (perm) => {
                                                                const ActionIcon = getActionIcon(perm.name);
                                                                return (
                                                                    <button
                                                                        key={perm.id}
                                                                        type="button"
                                                                        onClick={() => setSelectedPerm(perm.name)}
                                                                        className="inline-flex items-center gap-1.5 rounded-xl border border-neutral-200 bg-white px-3 py-1.5 text-xs font-semibold text-neutral-700 transition-all hover:border-primary/30 hover:bg-primary/5 hover:text-primary dark:border-neutral-700 dark:bg-neutral-800/50 dark:text-neutral-300 dark:hover:border-primary/50 dark:hover:bg-primary/10"
                                                                    >
                                                                        <ActionIcon className="h-3.5 w-3.5 opacity-60" />
                                                                        {perm.name.split('.').pop()}
                                                                    </button>
                                                                );
                                                            },
                                                        )}
                                                      </div>
                                                 </div>
                                             ),
                                         )}
                                     </div>
                                ) : (
                                    <div className="flex flex-col items-center justify-center py-12 text-center text-neutral-500">
                                        <div className="bg-neutral-100 rounded-full p-4 mb-4 dark:bg-neutral-800">
                                            <ShieldCheck className="h-12 w-12 opacity-20" />
                                        </div>
                                        <p className="font-medium">Sin permisos asignados</p>
                                        <p className="text-sm opacity-70">Este rol no tiene permisos configurados todavía.</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Users Card - Vertical Stack matching Permissions */}
                        <Card className="overflow-hidden rounded-xl border shadow-none p-0 gap-0">
                            <CardHeader className="border-b bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                <div className="flex items-center gap-2">
                                    <Users className="h-4 w-4 text-neutral-500" />
                                    <h2 className="text-sm font-semibold tracking-wide text-neutral-600 uppercase dark:text-neutral-300">
                                        Usuarios con este rol
                                    </h2>
                                </div>
                            </CardHeader>
                            <CardContent className="p-0">
                                <div className="border-b px-6 py-4">
                                    <TableFilters
                                        searchValue={search}
                                        onSearchChange={setSearch}
                                        searchPlaceholder="Buscar por nombre, correo o cédula..."
                                        searchLoading={isSearching}
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
                       {/* Permission Detail Dialog - Refined, Wider and More Legible */}
                <Dialog open={selectedPerm !== null} onOpenChange={(open) => { if (!open) setSelectedPerm(null); }}>
                    <DialogContent className="sm:max-w-lg border-none p-0 overflow-hidden rounded-3xl shadow-2xl">
                        {selectedPerm && (() => {
                            const parts = selectedPerm.split('.');
                            const moduleKey = parts[0] || '';
                            const actionKey = parts[1] || '';

                            const actionData: Record<string, { label: string, icon: any, color: string, bg: string, desc: string }> = {
                                view: { label: 'Visualización', icon: Eye, color: 'text-primary', bg: 'bg-primary/10', desc: 'Consultar y leer registros' },
                                read: { label: 'Visualización', icon: Eye, color: 'text-primary', bg: 'bg-primary/10', desc: 'Consultar y leer registros' },
                                create: { label: 'Creación', icon: PlusCircle, color: 'text-green-600', bg: 'bg-green-500/10', desc: 'Generar nuevos registros' },
                                edit: { label: 'Edición', icon: Pencil, color: 'text-amber-600', bg: 'bg-amber-500/10', desc: 'Modificar datos existentes' },
                                update: { label: 'Edición', icon: Pencil, color: 'text-amber-600', bg: 'bg-amber-500/10', desc: 'Modificar datos existentes' },
                                delete: { label: 'Eliminación', icon: Trash2, color: 'text-red-600', bg: 'bg-red-500/10', desc: 'Remover registros del sistema' },
                            };

                            const data = actionData[actionKey] || { label: 'Acción', icon: ShieldCheck, color: 'text-primary', bg: 'bg-primary/10', desc: 'Realizar operaciones' };
                            const Icon = data.icon;

                            const moduleLabel = moduleKey
                                .replace(/_/g, ' ')
                                .replace(/\b\w/g, (c) => c.toUpperCase());

                            return (
                                <div className="flex flex-col">
                                    {/* Modal Header with Primary Gradient */}
                                    <div className="relative h-32 bg-gradient-to-br from-primary via-primary/90 to-indigo-800 p-8 flex items-end">
                                        <div className="absolute top-6 right-6 rounded-full bg-white/10 p-2.5 backdrop-blur-md">
                                            <ShieldCheck className="h-6 w-6 text-white/90" />
                                        </div>
                                        <div className="space-y-1">
                                            <p className="text-xs font-bold uppercase tracking-[0.25em] text-white/70">
                                                Detalle del Permiso
                                            </p>
                                            <h3 className="text-3xl font-black text-white tracking-tight">
                                                {moduleLabel}
                                            </h3>
                                        </div>
                                    </div>

                                    <div className="p-8 space-y-8 bg-white dark:bg-neutral-900">
                                        <div className="flex items-start gap-5">
                                            <div className={`rounded-2xl p-4 shadow-sm ${data.bg} dark:bg-neutral-800`}>
                                                <Icon className={`h-8 w-8 ${data.color}`} />
                                            </div>
                                            <div className="space-y-1.5 pt-1">
                                                <h4 className="text-lg font-bold text-neutral-900 dark:text-neutral-100">
                                                    Acción de {data.label}
                                                </h4>
                                                <p className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                                    {data.desc} en el módulo de {moduleLabel.toLowerCase()}.
                                                </p>
                                            </div>
                                        </div>

                                        <div className="rounded-2xl border border-neutral-100 bg-neutral-50/50 p-6 dark:border-neutral-800 dark:bg-neutral-800/30">
                                            <p className="text-base leading-relaxed text-neutral-600 dark:text-neutral-300">
                                                Este permiso garantiza que el usuario pueda <span className="font-bold text-neutral-950 dark:text-neutral-50 underline decoration-primary/40 underline-offset-4 decoration-2">{data.desc.toLowerCase()}</span> dentro de este componente. Es una capacidad clave para la gestión de datos en el sistema.
                                            </p>
                                        </div>

                                        <div className="space-y-4">
                                            <p className="text-xs font-bold uppercase tracking-widest text-neutral-400 pl-1">
                                                Identificador del Sistema
                                            </p>
                                            <div className="flex items-center justify-between rounded-xl bg-neutral-100 px-4 py-3 dark:bg-neutral-800 border border-neutral-200/50 dark:border-neutral-700/50">
                                                <code className="text-sm font-mono font-bold text-neutral-600 dark:text-neutral-400">
                                                    {selectedPerm}
                                                </code>
                                                <div className="flex gap-1">
                                                    <div className="h-2 w-2 rounded-full bg-primary animate-pulse" />
                                                    <div className="h-2 w-2 rounded-full bg-primary/40" />
                                                </div>
                                            </div>
                                        </div>

                                        <div className="pt-2 text-center">
                                            <p className="text-xs font-medium text-neutral-400">
                                                Presiona <kbd className="rounded bg-neutral-100 px-1.5 py-0.5 font-sans text-xs font-bold text-neutral-500 dark:bg-neutral-800">ESC</kbd> para cerrar
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            );
                        })()}
                    </DialogContent>
                </Dialog>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
