import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Edit, ShieldCheck } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { index as roleIndex, edit as roleEdit } from '@/routes/admin/roles';
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
    role: Role;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Gestión de Roles',
        href: '/admin/roles',
    },
    {
        title: 'Detalles del Rol',
        href: '#',
    },
];

export default function Show({ role }: Props) {
    const isProtected = ['admin', 'profesor', 'alumno', 'representante'].includes(role.name);

    // Group permissions by module
    const groupedPermissions: Record<string, string[]> = {};
    role.permissions.forEach((p) => {
        const module = p.name.split('.')[0];
        if (!groupedPermissions[module]) {
            groupedPermissions[module] = [];
        }
        groupedPermissions[module].push(p.name);
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Rol: ${role.name}`} />

            <div className="mx-auto max-w-4xl p-4 lg:p-8">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <Button variant="ghost" size="sm" asChild className="-ml-2 mb-2">
                            <Link href={roleIndex().url}>
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver al listado
                            </Link>
                        </Button>
                        <div className="flex items-center gap-3">
                            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 dark:bg-neutral-800">
                                <ShieldCheck className="h-6 w-6 text-neutral-500" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                    {role.name.charAt(0).toUpperCase() + role.name.slice(1)}
                                </h1>
                                <div className="flex items-center gap-2">
                                    {isProtected ? (
                                        <Badge variant="secondary">Rol del Sistema</Badge>
                                    ) : (
                                        <Badge variant="outline">Rol Personalizado</Badge>
                                    )}
                                    <Badge variant="outline" className="text-neutral-500">
                                        {role.permissions.length} Permisos
                                    </Badge>
                                </div>
                            </div>
                        </div>
                    </div>
                    <Button asChild>
                        <Link href={roleEdit(role.id).url}>
                            <Edit className="mr-2 h-4 w-4" />
                            Editar Permisos
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardHeader className="border-b pb-4">
                        <CardTitle className="text-lg">Capacidades Asignadas</CardTitle>
                    </CardHeader>
                    <CardContent className="pt-6">
                        <div className="space-y-8">
                            {Object.keys(groupedPermissions).length > 0 ? (
                                Object.entries(groupedPermissions).map(([module, perms]) => (
                                    <div key={module}>
                                        <h3 className="mb-4 text-xs font-bold uppercase tracking-widest text-neutral-400">
                                            {module}
                                        </h3>
                                        <div className="flex flex-wrap gap-2">
                                            {perms.map((perm) => (
                                                <Badge
                                                    key={perm}
                                                    variant="outline"
                                                    className="bg-neutral-50 px-3 py-1 text-neutral-700 dark:bg-neutral-900 dark:text-neutral-300"
                                                >
                                                    {perm.split('.')[1]}
                                                </Badge>
                                            ))}
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <div className="flex flex-col items-center justify-center py-12 text-center text-neutral-500">
                                    <ShieldCheck className="mb-4 h-12 w-12 opacity-20" />
                                    <p>Este rol no tiene permisos asignados todavía.</p>
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>
                
                {isProtected && (
                    <div className="mt-6 rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-900/50 dark:bg-yellow-950/20">
                        <p className="text-sm text-yellow-800 dark:text-yellow-400">
                            <strong>Nota del Sistema:</strong> Este es un rol base protegido. Su nombre no puede ser modificado, pero sus permisos pueden ser ajustados por un administrador.
                        </p>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
