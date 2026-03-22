import { Head, Link } from '@inertiajs/react';
import { Shield, ShieldCheck } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
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
    const groups = groupPermissions(permissions);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Permisos del Sistema" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Permisos</h1>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                            Lista de todos los permisos registrados en el sistema y los roles que los poseen.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/admin/roles">
                            <Shield className="mr-2 h-4 w-4" />
                            Ver Roles
                        </Link>
                    </Button>
                </div>

                <div className="space-y-4">
                    {Object.entries(groups).map(([module, modulePerms]) => (
                        <div
                            key={module}
                            className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                        >
                            <div className="bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
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
                                        className="flex items-center justify-between px-6 py-3"
                                    >
                                        <code className="text-sm font-medium">{perm.name}</code>
                                        <div className="flex flex-wrap gap-1.5">
                                            {perm.roles.length > 0 ? (
                                                perm.roles.map((role) => (
                                                    <Badge key={role.id} variant="outline" className="text-xs capitalize">
                                                        {role.name}
                                                    </Badge>
                                                ))
                                            ) : (
                                                <span className="text-xs text-neutral-400">Sin roles</span>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
