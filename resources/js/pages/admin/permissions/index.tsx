import { Head, Link, usePage } from '@inertiajs/react';
import { Shield, ShieldCheck } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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

export default function PermissionsIndex({ permissions }: Props) {
    const { auth } = usePage<any>().props;
    const hasPermission = (p: string) => auth.permissions?.includes(p);
    
    const groups = groupPermissions(permissions);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Permisos del Sistema" />

            <SettingsLayout>
                <div className="space-y-6 text-neutral-900 dark:text-neutral-100">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">Permisos</h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Lista de todos los permisos registrados en el sistema y los roles que los poseen.
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

                    <div className="grid gap-6 sm:grid-cols-1 lg:grid-cols-2">
                        {Object.entries(groups).map(([module, modulePerms]) => (
                            <div
                                key={module}
                                className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-white dark:bg-neutral-900/50 shadow-sm"
                            >
                                <div className="bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50 border-b">
                                    <h2 className="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-neutral-600 dark:text-neutral-300">
                                        <ShieldCheck className="h-4 w-4" />
                                        {module}
                                        <Badge variant="secondary" className="text-xs">
                                            {modulePerms.length}
                                        </Badge>
                                    </h2>
                                </div>
                                <div className="divide-y divide-sidebar-border/70">
                                    {modulePerms.map((perm) => (
                                        <div
                                            key={perm.id}
                                            className="flex items-center justify-between px-6 py-3 hover:bg-neutral-50 dark:hover:bg-neutral-800/30 transition-colors"
                                        >
                                            <code className="text-xs font-medium bg-neutral-100 dark:bg-neutral-800 px-1.5 py-0.5 rounded text-primary">
                                                {perm.name}
                                            </code>
                                            <div className="flex flex-wrap gap-1.5 justify-end max-w-[50%]">
                                                {perm.roles.length > 0 ? (
                                                    perm.roles.map((role) => (
                                                        <Badge key={role.id} variant="outline" className="text-[10px] uppercase font-bold py-0 h-5 border-primary/20">
                                                            {role.name}
                                                        </Badge>
                                                    ))
                                                ) : (
                                                    <span className="text-[10px] text-neutral-400">Sin roles</span>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
