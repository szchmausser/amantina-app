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

export default function PermissionsIndex({ permissions }: Props) {
    const { auth } = usePage<any>().props;
    const hasPermission = (p: string) => auth.permissions?.includes(p);

    const groups = groupPermissions(permissions);
    const [openModules, setOpenModules] = useState<Set<string>>(new Set());

    const toggleModule = (module: string) => {
        setOpenModules((prev) => {
            const next = new Set(prev);
            if (next.has(module)) {
                next.delete(module);
            } else {
                next.add(module);
            }
            return next;
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Permisos del Sistema" />

            <SettingsLayout>
                <div className="flex flex-col gap-6">
                    {/* Header */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                Permisos del Sistema
                            </h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Lista de todos los permisos registrados en el
                                sistema y los roles que los poseen.
                            </p>
                        </div>
                        {hasPermission('roles.view') && (
                            <Button variant="outline" asChild>
                                <Link href="/admin/roles">Ver Roles</Link>
                            </Button>
                        )}
                    </div>

                    {/* Permissions List */}
                    <div className="space-y-4">
                        {Object.entries(groups).map(([module, modulePerms]) => {
                            const isOpen = openModules.has(module);

                            return (
                                <Card
                                    key={module}
                                    className="overflow-hidden p-0"
                                >
                                    {/* Header estilo tabla - clickable */}
                                    <button
                                        type="button"
                                        onClick={() => toggleModule(module)}
                                        className="flex w-full items-center justify-between border-b bg-neutral-50/50 px-4 py-5 transition-colors hover:bg-neutral-100/50 dark:border-neutral-800 dark:bg-neutral-800/30 dark:hover:bg-neutral-800/50"
                                    >
                                        <div className="flex items-center gap-3">
                                            <div className="text-neutral-400">
                                                {isOpen ? (
                                                    <ChevronDown className="h-4 w-4" />
                                                ) : (
                                                    <ChevronRight className="h-4 w-4" />
                                                )}
                                            </div>
                                            <Shield className="h-3.5 w-3.5 text-neutral-400" />
                                            <span className="text-sm font-semibold tracking-wider text-neutral-500 dark:text-neutral-400">
                                                {formatModuleName(module)}
                                            </span>
                                            <Badge
                                                variant="outline"
                                                className="text-[10px]"
                                            >
                                                {modulePerms.length} permisos
                                            </Badge>
                                        </div>
                                    </button>

                                    {/* Collapsible content */}
                                    {isOpen && (
                                        <CardContent className="px-6 pb-6 pt-0">
                                            <div className="mb-4 flex items-center justify-between">
                                                <span className="text-sm font-semibold text-neutral-500 dark:text-neutral-400">
                                                    Permisos
                                                </span>
                                                <span className="text-sm font-semibold text-neutral-500 dark:text-neutral-400">
                                                    Módulo
                                                </span>
                                            </div>
                                            <div className="flex items-center justify-between gap-3 rounded-lg border border-neutral-200 bg-neutral-50/50 px-3 py-2 dark:border-neutral-700/50 dark:bg-neutral-800/30">
                                                <div className="flex flex-wrap gap-1.5">
                                                    {modulePerms.map((perm) => {
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
                                                    })}
                                                </div>
                                                <span className="inline-flex shrink-0 items-center rounded-md border border-neutral-200 px-2 py-0.5 text-xs font-medium capitalize text-neutral-600 dark:border-neutral-700 dark:text-neutral-400">
                                                    {formatModuleName(module)}
                                                </span>
                                            </div>
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
