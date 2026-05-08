import { Head, Link } from '@inertiajs/react';
import { ShieldCheck, Users } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { index as permissionsIndex } from '@/routes/admin/permissions';
import { show as roleShow } from '@/routes/admin/roles';
import { show as userShow } from '@/routes/admin/users';
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
}

interface Permission {
    id: number;
    name: string;
    roles: PermissionRole[];
}

interface Props {
    permission: Permission;
    users: PermissionUser[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Permisos', href: '/admin/permissions' },
    { title: 'Detalles del Permiso', href: '#' },
];

export default function Show({ permission, users }: Props) {
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
                                    {users.length} Usuarios
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
                                {users.length > 0 ? (
                                    <div className="divide-y divide-border">
                                        {users.map((user) => (
                                            <Link
                                                key={user.id}
                                                href={userShow(user.id).url}
                                                className="flex items-center justify-between px-6 py-3 transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-800/30"
                                            >
                                                <div className="flex items-center gap-3">
                                                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-neutral-100 dark:bg-neutral-800">
                                                        <span className="text-xs font-semibold text-neutral-500">
                                                            {user.name.charAt(0).toUpperCase()}
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <p className="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                                            {user.name}
                                                        </p>
                                                        <p className="text-xs text-neutral-500">
                                                            {user.cedula || '—'}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div className="text-xs text-neutral-400">
                                                    {user.email}
                                                </div>
                                            </Link>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="flex flex-col items-center justify-center py-12 text-center text-neutral-400">
                                        <Users className="mb-2 h-10 w-10 opacity-20" />
                                        <p className="text-sm italic">
                                            Ningún usuario tiene este permiso asignado.
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
