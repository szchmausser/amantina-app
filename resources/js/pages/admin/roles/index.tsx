import { Head, Link, router } from '@inertiajs/react';
import { Edit, Eye, Shield, ShieldCheck } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { index as roleIndex, edit as roleEdit, show as roleShow } from '@/routes/admin/roles';
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
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gestión de Roles" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Roles</h1>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                            Visualiza los roles del sistema y gestiona sus permisos asignados.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/admin/permissions">
                            <ShieldCheck className="mr-2 h-4 w-4" />
                            Ver Permisos
                        </Link>
                    </Button>
                </div>

                <div className="space-y-4">
                    {roles.map((role) => {
                        const isProtected = PROTECTED_ROLES.includes(role.name);
                        const groups = groupPermissions(role.permissions);

                        return (
                            <div
                                key={role.id}
                                className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                            >
                                <div className="flex items-center justify-between bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                    <div className="flex items-center gap-2">
                                        <Shield className="h-4 w-4 text-neutral-500" />
                                        <h2 className="text-sm font-semibold uppercase tracking-wide text-neutral-600 dark:text-neutral-300">
                                            {role.name}
                                        </h2>
                                        {isProtected && (
                                            <Badge variant="secondary" className="px-1 py-0 text-[10px]">
                                                Rol base
                                            </Badge>
                                        )}
                                        <Badge variant="outline" className="text-xs">
                                            {role.permissions.length} permisos
                                        </Badge>
                                    </div>
                                    <div className="flex gap-1">
                                        <Button variant="ghost" size="icon" className="h-8 w-8" asChild>
                                            <Link href={roleShow(role.id).url}>
                                                <Eye className="h-4 w-4 text-neutral-500" />
                                                <span className="sr-only">Ver detalles</span>
                                            </Link>
                                        </Button>
                                        <Button variant="ghost" size="icon" className="h-8 w-8" asChild>
                                            <Link href={roleEdit(role.id).url}>
                                                <Edit className="h-4 w-4 text-neutral-500" />
                                                <span className="sr-only">Editar permisos</span>
                                            </Link>
                                        </Button>
                                    </div>
                                </div>

                                <div className="divide-y divide-sidebar-border/70 p-6 pt-4">
                                    {role.permissions.length > 0 ? (
                                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                            {Object.entries(groups).map(([module, actions]) => (
                                                <div key={module} className="space-y-1.5">
                                                    <h3 className="text-xs font-bold uppercase text-neutral-400 dark:text-neutral-500">
                                                        {module}
                                                    </h3>
                                                    <div className="flex flex-wrap gap-1">
                                                        {actions.map((action) => (
                                                            <Badge key={action} variant="outline" className="text-[10px] font-normal">
                                                                {action}
                                                            </Badge>
                                                        ))}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-sm text-neutral-400 dark:text-neutral-500">
                                            Sin permisos asignados
                                        </p>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </AppLayout>
    );
}
