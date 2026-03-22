import { Head, Link, router, usePage } from '@inertiajs/react';
import { Edit, Eye, Plus, Search, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { index as userIndex, destroy as userDestroy, create as userCreate, edit as userEdit, show as userShow } from '@/routes/admin/users';
import type { BreadcrumbItem, User } from '@/types';

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

    const handleSearch = () => {
        router.get(
            userIndex().url,
            { search, role: role === 'all' ? undefined : role },
            { preserveState: true, replace: true }
        );
    };

    const handleRoleChange = (value: string) => {
        setRole(value);
        router.get(
            userIndex().url,
            { search, role: value === 'all' ? undefined : value },
            { preserveState: true, replace: true }
        );
    };

    const handleDelete = (id: number) => {
        if (confirm('¿Estás seguro de que deseas eliminar este usuario?')) {
            router.delete(userDestroy(id).url);
        }
    };

    const { auth } = usePage<any>().props;
    const hasPermission = (p: string) => auth.permissions.includes(p);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gestión de Usuarios" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Usuarios</h1>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                            Administra las cuentas de usuario, roles y permisos del sistema.
                        </p>
                    </div>
                    {hasPermission('users.create') && (
                        <Button asChild>
                            <Link href={userCreate().url}>
                                <Plus className="mr-2 h-4 w-4" />
                                Nuevo Usuario
                            </Link>
                        </Button>
                    )}
                </div>

                <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div className="relative flex-1">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-500" />
                        <Input
                            placeholder="Buscar por nombre, correo o cédula..."
                            className="pl-10"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                        />
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" onClick={handleSearch}>
                            Filtrar
                        </Button>
                        <Button
                            variant="ghost"
                            onClick={() => {
                                setSearch('');
                                setRole('all');
                                router.get(userIndex().url, {}, { preserveState: false, replace: true });
                            }}
                        >
                            Limpiar
                        </Button>
                    </div>
                    <div className="w-full sm:w-48">
                        <Select value={role} onValueChange={handleRoleChange}>
                            <SelectTrigger>
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
                    </div>
                </div>

                <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-neutral-50 text-xs uppercase text-neutral-500 dark:bg-neutral-800/50 dark:text-neutral-400">
                                <tr>
                                    <th className="px-6 py-4 font-medium">Cédula</th>
                                    <th className="px-6 py-4 font-medium">Nombre</th>
                                    <th className="px-6 py-4 font-medium">Email</th>
                                    <th className="px-6 py-4 font-medium">Rol</th>
                                    <th className="px-6 py-4 font-medium text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-sidebar-border/70">
                                {users.data.length > 0 ? (
                                    users.data.map((user) => (
                                        <tr key={user.id} className="hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30">
                                            <td className="whitespace-nowrap px-6 py-4 font-mono">{user.cedula}</td>
                                            <td className="whitespace-nowrap px-6 py-4 font-medium decoration-neutral-100 hover:underline">
                                                <Link href={userShow(user.id).url}>{user.name}</Link>
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-neutral-500 dark:text-neutral-400">{user.email}</td>
                                             <td className="whitespace-nowrap px-6 py-4">
                                                {user.roles && user.roles.map((r) => (
                                                    <span key={r.name} className="inline-flex items-center rounded-full bg-neutral-100 px-2.5 py-0.5 text-xs font-medium text-neutral-800 dark:bg-neutral-800 dark:text-neutral-200">
                                                        {r.name}
                                                    </span>
                                                ))}
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button variant="ghost" size="icon" asChild title="Ver detalles">
                                                        <Link href={userShow(user.id).url}>
                                                            <Eye className="h-4 w-4 text-neutral-500" />
                                                            <span className="sr-only">Ver detalles</span>
                                                        </Link>
                                                    </Button>
                                                    {(hasPermission('users.edit') || auth.user.id === user.id) && (
                                                        <Button variant="ghost" size="icon" asChild title="Editar">
                                                            <Link href={userEdit(user.id).url}>
                                                                <Edit className="h-4 w-4" />
                                                                <span className="sr-only">Editar</span>
                                                            </Link>
                                                        </Button>
                                                    )}
                                                    {hasPermission('users.delete') && (
                                                        <Button variant="ghost" size="icon" className="text-red-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/30" onClick={() => handleDelete(user.id)} title="Eliminar">
                                                            <Trash2 className="h-4 w-4" />
                                                            <span className="sr-only">Eliminar</span>
                                                        </Button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={5} className="px-6 py-12 text-center text-neutral-500 dark:text-neutral-400">
                                            No se encontraron usuarios que coincidan con los criterios.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {users.last_page > 1 && (
                    <div className="flex items-center justify-between px-2">
                        <div className="text-sm text-neutral-500">
                            Mostrando página {users.current_page} de {users.last_page} ({users.total} usuarios)
                        </div>
                        <div className="flex gap-2">
                            {users.links.map((link, i) => (
                                <Button
                                    key={i}
                                    variant={link.active ? 'default' : 'outline'}
                                    size="sm"
                                    disabled={!link.url}
                                    asChild={!!link.url}
                                >
                                    {link.url ? (
                                        <Link href={link.url} dangerouslySetInnerHTML={{ __html: link.label }} />
                                    ) : (
                                        <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                    )}
                                </Button>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
