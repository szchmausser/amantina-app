import { Head, Link, usePage } from '@inertiajs/react';
import { Edit, Eye, Shield } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
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
    permissions_count: number;
}

interface Props {
    roles: Role[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Roles', href: '/admin/roles' },
];

export default function RolesIndex({ roles }: Props) {
    const { auth } = usePage<any>().props;
    const hasPermission = (p: string) => auth.permissions.includes(p);

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
                            return (
                                <Card
                                    key={role.id}
                                    className="overflow-hidden p-0"
                                >
                                    <div className="flex w-full items-center justify-between bg-neutral-50/50 px-4 py-3 dark:bg-neutral-800/30">
                                        <div className="flex items-center gap-3 pl-1">
                                            <Shield className="h-4 w-4 text-neutral-500" />
                                            <Link
                                                href={roleShow({ role: role.id }).url}
                                                className="text-sm font-semibold tracking-wider text-neutral-600 hover:text-blue-600 dark:text-neutral-300 dark:hover:text-blue-400 transition-colors"
                                            >
                                                {role.name.charAt(0).toUpperCase() + role.name.slice(1)}
                                            </Link>

                                            <Badge
                                                variant="outline"
                                                className="text-[10px] bg-white/50 dark:bg-neutral-900/30"
                                            >
                                                {role.permissions_count}{' '}
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
                                                >
                                                    <Link href={roleShow({ role: role.id }).url}>
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
                                                >
                                                    <Link href={roleEdit({ role: role.id }).url}>
                                                        <Edit className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                            )}
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
