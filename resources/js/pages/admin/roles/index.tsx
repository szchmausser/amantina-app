import { Head, Link, usePage } from '@inertiajs/react';
import { ChevronDown, ChevronRight, Edit, Eye, Pencil, PlusCircle, Shield, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit as roleEdit, show as roleShow } from '@/routes/admin/roles';
import { show as permissionShow } from '@/routes/admin/permissions';
import type { BreadcrumbItem } from '@/types';

interface Permission {
    id: number;
    name: string;
}

interface Role {
    id: number;
    name: string;
    permissions: Permission[];
}

interface Props {
    roles: Role[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Roles', href: '/admin/roles' },
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

export default function RolesIndex({ roles }: Props) {
    const { auth } = usePage<any>().props;
    const hasPermission = (p: string) => auth.permissions.includes(p);
    const [openRoleIds, setOpenRoleIds] = useState<Set<number>>(new Set());

    const toggleRole = (roleId: number) => {
        setOpenRoleIds((prev) => {
            const next = new Set(prev);
            if (next.has(roleId)) {
                next.delete(roleId);
            } else {
                next.add(roleId);
            }
            return next;
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gestión de Roles" />

            <SettingsLayout>
                <div className="flex flex-col gap-6">
                    {/* Header */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                Roles del Sistema
                            </h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Visualiza los roles del sistema y gestiona sus
                                permisos asignados.
                            </p>
                        </div>
                        {hasPermission('permissions.view') && (
                            <Button variant="outline" asChild>
                                <Link href="/admin/permissions">
                                    Ver Permisos
                                </Link>
                            </Button>
                        )}
                    </div>

                    {/* Roles List */}
                    <div className="space-y-4">
                        {roles.map((role) => {
                            const groups = groupPermissions(role.permissions);
                            const isOpen = openRoleIds.has(role.id);

                            return (
                                <Card
                                    key={role.id}
                                    className="overflow-hidden p-0"
                                >
                                    {/* Header estilo tabla - clickable */}
                                    <button
                                        type="button"
                                        onClick={() => toggleRole(role.id)}
                                        className="flex w-full items-center justify-between border-b bg-neutral-50/50 px-4 py-3 transition-colors hover:bg-neutral-100/50 dark:border-neutral-800 dark:bg-neutral-800/30 dark:hover:bg-neutral-800/50"
                                    >
                                        <div className="flex items-center gap-2">
                                            <div className="text-neutral-400">
                                                {isOpen ? (
                                                    <ChevronDown className="h-4 w-4" />
                                                ) : (
                                                    <ChevronRight className="h-4 w-4" />
                                                )}
                                            </div>
                                            <Shield className="h-4 w-4 text-neutral-500" />
                                            <Link
                                                href={roleShow(role.id).url}
                                                onClick={(e) => e.stopPropagation()}
                                                className="text-sm font-semibold tracking-wider text-neutral-500 hover:text-blue-600 dark:text-neutral-400 dark:hover:text-blue-400 transition-colors"
                                            >
                                                {role.name.charAt(0).toUpperCase() + role.name.slice(1)}
                                            </Link>

                                            <Badge
                                                variant="outline"
                                                className="text-[10px]"
                                            >
                                                {role.permissions.length}{' '}
                                                permisos
                                            </Badge>
                                        </div>
                                        <div className="flex gap-1">
                                            {hasPermission('roles.view') && (
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-neutral-500 hover:text-blue-600"
                                                    asChild
                                                    title="Ver detalles"
                                                    onClick={(e) =>
                                                        e.stopPropagation()
                                                    }
                                                >
                                                    <Link
                                                        href={
                                                            roleShow(role.id)
                                                                .url
                                                        }
                                                    >
                                                        <Eye className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                            )}
                                            {hasPermission('roles.edit') && (
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-neutral-500 hover:text-blue-600"
                                                    asChild
                                                    title="Editar permisos"
                                                    onClick={(e) =>
                                                        e.stopPropagation()
                                                    }
                                                >
                                                    <Link
                                                        href={
                                                            roleEdit(role.id)
                                                                .url
                                                        }
                                                    >
                                                        <Edit className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                            )}
                                        </div>
                                    </button>

                                    {/* Collapsible content */}
                                    {isOpen && (
                                        <CardContent className="px-6 pb-6 pt-0">
                                            <div className="mb-4 flex items-center justify-between">
                                                <span className="text-sm font-semibold text-neutral-500 dark:text-neutral-400">
                                                    Permisos del rol
                                                </span>
                                                <span className="text-sm font-semibold text-neutral-500 dark:text-neutral-400">
                                                    Módulo
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
                                                                <div className="flex flex-wrap gap-1.5">
                                                                    {modulePerms.map(
                                                                        (perm) => {
                                                                            const ActionIcon = getActionIcon(perm.name);
                                                                            return (
                                                                                <Link
                                                                                    key={perm.id}
                                                                                    href={permissionShow(perm.id).url}
                                                                                    className="inline-flex items-center gap-1.5 rounded bg-neutral-100 px-2.5 py-1 text-sm font-medium text-neutral-700 transition-colors hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700"
                                                                                >
                                                                                    <ActionIcon className="h-3.5 w-3.5 text-neutral-400" />
                                                                                    {perm.name.split('.').pop()}
                                                                                </Link>
                                                                            );
                                                                        },
                                                                    )}
                                                                 </div>
                                                                 <span className="inline-flex shrink-0 items-center rounded-md border border-neutral-200 px-2 py-0.5 text-xs font-medium capitalize text-neutral-600 dark:border-neutral-700 dark:text-neutral-400">
                                                                     {formatModuleName(module)}
                                                                 </span>
                                                             </div>
                                                         ),
                                                     )}
                                                 </div>
                                            ) : (
                                                <div className="flex flex-col items-center justify-center py-6 text-neutral-400">
                                                    <Shield className="mb-2 h-8 w-8 opacity-10" />
                                                    <p className="text-sm italic">
                                                        Sin permisos asignados
                                                    </p>
                                                </div>
                                            )}
                                        </CardContent>
                                    )}
                                </Card>
                            );
                        })}
                    </div>
                </div>


            </SettingsLayout>
        </AppLayout>
    );
}
