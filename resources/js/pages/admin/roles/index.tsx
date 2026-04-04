import { Head, Link, usePage } from '@inertiajs/react';
import { ChevronDown, ChevronRight, Edit, Eye, Shield } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit as roleEdit, show as roleShow } from '@/routes/admin/roles';
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

const PROTECTED_ROLES = ['admin', 'profesor', 'alumno', 'representante'];

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Roles', href: '/admin/roles' },
];

function groupPermissions(permissions: Permission[]): Record<string, string[]> {
    const groups: Record<string, string[]> = {};
    permissions.forEach((p) => {
        const [module, action] = p.name.split('.');
        if (!groups[module]) {
            groups[module] = [];
        }
        groups[module].push(action);
    });
    return groups;
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
                            const isProtected = PROTECTED_ROLES.includes(
                                role.name,
                            );
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
                                            <span className="text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                                {role.name}
                                            </span>
                                            {isProtected && (
                                                <Badge
                                                    variant="secondary"
                                                    className="text-[10px]"
                                                >
                                                    Rol base
                                                </Badge>
                                            )}
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
                                        <CardContent className="p-6">
                                            {role.permissions.length > 0 ? (
                                                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                                    {Object.entries(groups).map(
                                                        ([module, actions]) => (
                                                            <div
                                                                key={module}
                                                                className="space-y-2 rounded-lg border bg-neutral-50/50 p-3 dark:border-neutral-800 dark:bg-neutral-800/30"
                                                            >
                                                                <h3 className="flex items-center justify-between text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                                                    {module}
                                                                    <span className="rounded bg-primary/10 px-1.5 py-0.5 text-[10px] font-bold text-primary">
                                                                        {
                                                                            actions.length
                                                                        }
                                                                    </span>
                                                                </h3>
                                                                <div className="flex flex-wrap gap-1">
                                                                    {actions.map(
                                                                        (
                                                                            action,
                                                                        ) => (
                                                                            <span
                                                                                key={
                                                                                    action
                                                                                }
                                                                                className="rounded border bg-white px-1.5 py-0.5 text-xs text-neutral-600 capitalize dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-400"
                                                                            >
                                                                                {
                                                                                    action
                                                                                }
                                                                            </span>
                                                                        ),
                                                                    )}
                                                                </div>
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
