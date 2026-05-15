import { Head, Link, usePage } from '@inertiajs/react';
import { ChevronDown, ChevronRight, Eye, Pencil, PlusCircle, Shield, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { show as permissionShow } from '@/routes/admin/permissions';
import type { BreadcrumbItem } from '@/types';

interface Role {
    id: number;
    name: string;
}

interface Permission {
    id: number;
    name: string;
    roles: Role[];
}

interface Props {
    permissions: Permission[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Permisos', href: '/admin/permissions' },
];

function groupPermissions(
    permissions: Permission[],
): Record<string, Permission[]> {
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

export default function PermissionsIndex({ permissions }: any) {
    const { auth } = usePage<any>().props;
    const hasPermission = (p: string) => auth.permissions?.includes(p);

    const groups = groupPermissions(permissions);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gestión de Permisos" />

            <SettingsLayout>
                <div className="flex flex-col gap-6">
                    {/* Header - Matching Roles Index */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                Permisos del Sistema
                            </h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Gestiona los permisos granulares que definen las capacidades de los roles en la plataforma.
                            </p>
                        </div>
                        {hasPermission('roles.view') && (
                            <Button variant="outline" asChild>
                                <Link href="/admin/roles">
                                    <Shield className="mr-2 h-4 w-4" />
                                    Ver Roles
                                </Link>
                            </Button>
                        )}
                    </div>

                    {/* Permissions List - Matching Roles Visual Form */}
                    <div className="space-y-4">
                        {Object.entries(groups).map(([module, modulePerms]) => {
                            return (
                                <Card
                                    key={module}
                                    className="overflow-hidden p-0"
                                >
                                    <div className="flex w-full items-center justify-between bg-neutral-50/50 px-4 py-3 dark:bg-neutral-800/30">
                                        <div className="flex items-center gap-3 pl-1">
                                            <Shield className="h-4 w-4 text-neutral-500 dark:text-neutral-400" />
                                            <span className="text-sm font-semibold tracking-wider text-neutral-600 dark:text-neutral-300 dark:text-neutral-400">
                                                {formatModuleName(module)}
                                            </span>

                                            <Badge
                                                variant="outline"
                                                className="text-[10px] bg-white/50 dark:bg-neutral-900/30"
                                            >
                                                {modulePerms.length} permisos
                                            </Badge>
                                        </div>
                                            <div className="flex flex-wrap gap-2 sm:justify-end flex-1 max-w-2xl">
                                                {modulePerms.map((perm: any) => {
                                                    const ActionIcon = getActionIcon(perm.name);
                                                    const actionLabel = perm.name.split('.').pop();
                                                    
                                                    return (
                                                        <Link
                                                            key={perm.id}
                                                            href={permissionShow(perm.id).url}
                                                            className="inline-flex items-center gap-1.5 rounded-xl border border-neutral-200 bg-white px-3 py-1.5 text-xs font-semibold text-neutral-700 transition-all hover:border-primary/30 hover:bg-primary/5 hover:text-primary dark:border-neutral-700 dark:bg-neutral-800/50 dark:text-neutral-300 dark:hover:border-primary/50 dark:hover:bg-primary/10"
                                                        >
                                                            <ActionIcon className="h-3.5 w-3.5 opacity-60" />
                                                            {actionLabel}
                                                        </Link>
                                                    );
                                                })}
                                            </div>
                                    </div>
                                </Card>
                            );
                        })}
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
