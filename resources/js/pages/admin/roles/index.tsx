import { Head, Link, usePage } from '@inertiajs/react';
import { Edit, Eye, Shield, ShieldCheck } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gestión de Roles" />

            <SettingsLayout>
                <div className="space-y-6 text-neutral-900 dark:text-neutral-100">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">Roles</h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Visualiza los roles del sistema y gestiona sus permisos asignados.
                            </p>
                        </div>
                        {hasPermission('permissions.view') && (
                            <Button variant="outline" asChild>
                                <Link href="/admin/permissions">
                                    <ShieldCheck className="mr-2 h-4 w-4" />
                                    Ver Permisos
                                </Link>
                            </Button>
                        )}
                    </div>

                    <div className="space-y-4">
                        {roles.map((role) => {
                            const isProtected = PROTECTED_ROLES.includes(role.name);
                            const groups = groupPermissions(role.permissions);

                            return (
                                <div
                                    key={role.id}
                                    className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-white dark:bg-neutral-900/50 shadow-sm"
                                >
                                    <div className="flex items-center justify-between bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50 border-b">
                                        <div className="flex items-center gap-2">
                                            <Shield className="h-4 w-4 text-neutral-500" />
                                            <h2 className="text-sm font-semibold uppercase tracking-wide text-neutral-600 dark:text-neutral-300">
                                                {role.name}
                                            </h2>
                                            {isProtected && (
                                                <Badge variant="secondary" className="px-1.5 py-0.5 text-[10px] font-bold">
                                                    Rol base
                                                </Badge>
                                            )}
                                            <Badge variant="outline" className="text-[10px] h-5 border-primary/20">
                                                {role.permissions.length} permisos
                                            </Badge>
                                        </div>
                                        <div className="flex gap-1">
                                            {hasPermission('roles.view') && (
                                                <Button variant="ghost" size="icon" className="h-8 w-8 hover:bg-neutral-100 dark:hover:bg-neutral-800" asChild title="Ver detalles">
                                                    <Link href={roleShow(role.id).url}>
                                                        <Eye className="h-4 w-4 text-neutral-500" />
                                                        <span className="sr-only">Ver detalles</span>
                                                    </Link>
                                                </Button>
                                            )}
                                            {hasPermission('roles.edit') && (
                                                <Button variant="ghost" size="icon" className="h-8 w-8 hover:bg-blue-50 dark:hover:bg-blue-950/30 hover:text-blue-600" asChild title="Editar permisos">
                                                    <Link href={roleEdit(role.id).url}>
                                                        <Edit className="h-4 w-4" />
                                                        <span className="sr-only">Editar permisos</span>
                                                    </Link>
                                                </Button>
                                            )}
                                        </div>
                                    </div>

                                    <div className="p-6">
                                        {role.permissions.length > 0 ? (
                                            <div className="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
                                                {Object.entries(groups).map(([module, actions]) => (
                                                    <div key={module} className="space-y-2 p-3 rounded-lg bg-neutral-50/50 dark:bg-neutral-800/30 border border-neutral-100 dark:border-neutral-800">
                                                        <h3 className="text-[10px] font-bold uppercase text-neutral-500 dark:text-neutral-400 border-b border-neutral-200 dark:border-neutral-700 pb-1 flex justify-between items-center">
                                                            {module}
                                                            <span className="bg-primary/10 text-primary px-1 rounded text-[9px]">{actions.length}</span>
                                                        </h3>
                                                        <div className="flex flex-wrap gap-1">
                                                            {actions.map((action) => (
                                                                <span key={action} className="text-[10px] font-medium text-neutral-600 dark:text-neutral-400 bg-white dark:bg-neutral-900 px-1.5 py-0.5 rounded border border-neutral-200 dark:border-neutral-800 capitalize">
                                                                    {action}
                                                                </span>
                                                            ))}
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        ) : (
                                            <div className="flex flex-col items-center justify-center py-6 text-neutral-400">
                                                <Shield className="h-8 w-8 opacity-10 mb-2" />
                                                <p className="text-sm italic">Sin permisos asignados</p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
