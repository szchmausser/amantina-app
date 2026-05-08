import { Head, Link } from '@inertiajs/react';
import { Edit, ShieldCheck, Users } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { index as roleIndex, edit as roleEdit } from '@/routes/admin/roles';
import { show as userShow } from '@/routes/admin/users';
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
}

interface Role {
    id: number;
    name: string;
    permissions: Permission[];
}

interface Props {
    role: Role;
    users: RoleUser[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Roles', href: '/admin/roles' },
    { title: 'Detalles del Rol', href: '#' },
];

export default function Show({ role, users }: Props) {
    const [selectedPerm, setSelectedPerm] = useState<string | null>(null);
    const isProtected = ['admin', 'profesor', 'alumno', 'representante'].includes(role.name);

    // Group permissions by module
    const groupedPermissions: Record<string, string[]> = {};
    role.permissions.forEach((p) => {
        const module = p.name.split('.')[0];
        if (!groupedPermissions[module]) {
            groupedPermissions[module] = [];
        }
        groupedPermissions[module].push(p.name);
    });

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
                                <Badge variant="outline" className="text-[10px] text-neutral-500">
                                    {role.permissions.length} Permisos
                                </Badge>
                                <Badge variant="outline" className="text-[10px] text-neutral-500">
                                    {users.length} Usuarios
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
                                <Link href={roleEdit(role.id).url}>
                                    <Edit className="mr-2 h-4 w-4" />
                                    Editar Permisos
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <div className="grid gap-6">
                        {/* Permissions Card */}
                        <Card className="overflow-hidden rounded-xl border shadow-none">
                            <CardHeader className="border-b bg-neutral-50 px-6 py-4 dark:bg-neutral-800/50">
                                <CardTitle className="flex items-center gap-2 text-sm font-semibold">
                                    <ShieldCheck className="h-4 w-4 text-neutral-500" />
                                    Capacidades Asignadas
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-6">
                                {Object.keys(groupedPermissions).length > 0 ? (
                                    <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                                        {Object.entries(groupedPermissions).map(([module, perms]) => (
                                            <div key={module} className="space-y-3 rounded-lg border-l-4 border-l-neutral-200 bg-neutral-50 p-4 dark:border-l-neutral-700 dark:bg-neutral-900/20">
                                                <div className="flex items-center justify-between border-b border-neutral-100 pb-2 dark:border-neutral-800">
                                                    <h3 className="text-xs font-bold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                                        {module}
                                                    </h3>
                                                    <span className="rounded bg-neutral-200/50 px-1.5 py-0.5 text-[10px] font-bold text-neutral-600 dark:bg-neutral-700 dark:text-neutral-400">
                                                        {perms.length}
                                                    </span>
                                                </div>
                                                <div className="flex flex-wrap gap-1.5">
                                                    {perms.map((perm) => (
                                                        <button
                                                            key={perm}
                                                            type="button"
                                                            onClick={() => setSelectedPerm(perm)}
                                                            className="inline-flex cursor-pointer items-center rounded-md border border-neutral-200 bg-white px-2 py-0.5 text-[10px] font-medium capitalize text-neutral-700 transition-colors hover:bg-neutral-100 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300 dark:hover:bg-neutral-800"
                                                        >
                                                            {perm.split('.')[1]}
                                                        </button>
                                                    ))}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="flex flex-col items-center justify-center py-12 text-center text-neutral-500">
                                        <div className="bg-neutral-100 rounded-full p-4 mb-4 dark:bg-neutral-800">
                                            <ShieldCheck className="h-12 w-12 opacity-20" />
                                        </div>
                                        <p className="font-medium">Sin permisos asignados</p>
                                        <p className="text-sm opacity-70">Este rol no tiene capacidades configuradas todavía.</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Users with this role */}
                        <Card className="overflow-hidden rounded-xl border shadow-none">
                            <CardHeader className="border-b bg-neutral-50 px-6 py-4 dark:bg-neutral-800/50">
                                <CardTitle className="flex items-center gap-2 text-sm font-semibold">
                                    <Users className="h-4 w-4 text-neutral-500" />
                                    Usuarios con este rol
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
                                            Ningún usuario tiene este rol asignado.
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Protected role info */}
                        {isProtected && (
                            <div className="rounded-xl border border-yellow-200 bg-yellow-50/50 p-4 dark:border-yellow-900/30 dark:bg-yellow-950/20">
                                <div className="flex gap-3">
                                    <ShieldCheck className="h-5 w-5 shrink-0 text-yellow-600 dark:text-yellow-500" />
                                    <div className="space-y-1">
                                        <p className="text-sm font-semibold text-yellow-800 dark:text-yellow-300">
                                            Rol Protegido del Sistema
                                        </p>
                                        <p className="text-xs text-yellow-700 leading-relaxed dark:text-yellow-400">
                                            Este es un rol fundamental para el funcionamiento de la aplicación.
                                            Su nombre y propósito están protegidos, pero sus permisos específicos
                                            pueden ser ajustados por un administrador según las necesidades de la institución.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Permission Detail Dialog */}
                <Dialog open={selectedPerm !== null} onOpenChange={(open) => { if (!open) setSelectedPerm(null); }}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2">
                                <ShieldCheck className="h-5 w-5 text-neutral-500" />
                                Capacidad del Rol
                            </DialogTitle>
                            <DialogDescription>
                                Permiso que este rol concede al usuario.
                            </DialogDescription>
                        </DialogHeader>
                        {selectedPerm && (() => {
                            const parts = selectedPerm.split('.');
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
                                <div className="space-y-5">
                                    <div className="rounded-lg border bg-neutral-50 p-5 dark:bg-neutral-900/50">
                                        <p className="text-sm leading-relaxed text-neutral-700 dark:text-neutral-300">
                                            Este rol permite al usuario <strong className="text-neutral-900 dark:text-neutral-100">puede {actionDesc}</strong> <strong className="text-neutral-900 dark:text-neutral-100">{moduleLabel.toLowerCase()}</strong> en el sistema.
                                        </p>
                                    </div>

                                    <div className="space-y-2">
                                        <h4 className="text-xs font-semibold tracking-wider text-neutral-500 uppercase">
                                            Permiso
                                        </h4>
                                        <code className="block rounded-md border bg-neutral-50 px-3 py-2 text-xs font-mono text-neutral-600 dark:bg-neutral-900 dark:text-neutral-400">
                                            {selectedPerm}
                                        </code>
                                    </div>

                                    <div className="text-xs text-neutral-400 italic">
                                        Presiona ESC o haz clic fuera para cerrar.
                                    </div>
                                </div>
                            );
                        })()}
                    </DialogContent>
                </Dialog>
            </SettingsLayout>
        </AppLayout>
    );
}
