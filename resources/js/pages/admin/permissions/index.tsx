import { Head, Link, usePage } from '@inertiajs/react';
import { ChevronDown, ChevronRight, Shield, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
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

                    {/* Permissions Grid */}
                    <div className="grid items-start gap-4 sm:grid-cols-1 lg:grid-cols-2">
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
                                        className="flex w-full items-center justify-between border-b bg-neutral-50/50 px-4 py-3 transition-colors hover:bg-neutral-100/50 dark:border-neutral-800 dark:bg-neutral-800/30 dark:hover:bg-neutral-800/50"
                                    >
                                        <div className="flex items-center gap-3">
                                            <div className="text-neutral-400">
                                                {isOpen ? (
                                                    <ChevronDown className="h-4 w-4" />
                                                ) : (
                                                    <ChevronRight className="h-4 w-4" />
                                                )}
                                            </div>
                                            <ShieldCheck className="h-3.5 w-3.5 text-neutral-400" />
                                            <span className="text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                                {module}
                                            </span>
                                            <Badge
                                                variant="secondary"
                                                className="text-[10px]"
                                            >
                                                {modulePerms.length}
                                            </Badge>
                                        </div>
                                    </button>

                                    {/* Collapsible content */}
                                    {isOpen && (
                                        <CardContent className="p-0">
                                            <div className="divide-y">
                                                {modulePerms.map((perm) => (
                                                    <div
                                                        key={perm.id}
                                                        className="flex items-center justify-between px-6 py-3 transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-800/30"
                                                    >
                                                        <code className="rounded bg-neutral-100 px-2 py-0.5 text-xs font-medium text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300">
                                                            {perm.name}
                                                        </code>
                                                        <div className="flex max-w-[50%] flex-wrap items-center justify-end gap-1.5">
                                                            {perm.roles.length >
                                                            0 ? (
                                                                perm.roles.map(
                                                                    (role) => (
                                                                        <Badge
                                                                            key={
                                                                                role.id
                                                                            }
                                                                            variant="outline"
                                                                            className="text-[10px] font-medium capitalize"
                                                                        >
                                                                            {
                                                                                role.name
                                                                            }
                                                                        </Badge>
                                                                    ),
                                                                )
                                                            ) : (
                                                                <span className="text-xs text-neutral-400">
                                                                    Sin roles
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>
                                                ))}
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
