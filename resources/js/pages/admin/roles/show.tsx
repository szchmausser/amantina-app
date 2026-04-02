import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Edit, ShieldCheck } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
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

            <SettingsLayout>
                <div className="space-y-6 text-neutral-900 dark:text-neutral-100">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <Button variant="ghost" size="sm" asChild className="-ml-2 mb-2">
                                <Link href={roleIndex().url}>
                                    <ArrowLeft className="mr-2 h-4 w-4" />
                                    Volver al listado
                                </Link>
                            </Button>
                            <div className="flex items-center gap-3">
                                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 border border-primary/20">
                                    <ShieldCheck className="h-6 w-6 text-primary" />
                                </div>
                                <div>
                                    <h1 className="text-2xl font-bold tracking-tight">
                                        {role.name.charAt(0).toUpperCase() + role.name.slice(1)}
                                    </h1>
                                    <div className="flex items-center gap-2 mt-1">
                                        {isProtected ? (
                                            <Badge variant="secondary" className="bg-primary/10 text-primary hover:bg-primary/20 border-transparent text-[10px] font-bold">Rol del Sistema</Badge>
                                        ) : (
                                            <Badge variant="outline" className="text-[10px]">Rol Personalizado</Badge>
                                        )}
                                        <Badge variant="outline" className="text-[10px] text-neutral-500">
                                            {role.permissions.length} Permisos
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <Button asChild size="lg">
                            <Link href={roleEdit(role.id).url}>
                                <Edit className="mr-2 h-4 w-4" />
                                Editar Permisos
                            </Link>
                        </Button>
                    </div>

                    <div className="grid gap-6">
                        <Card className="border-sidebar-border/70 dark:border-sidebar-border shadow-sm">
                            <CardHeader className="border-b border-sidebar-border/70 bg-neutral-50/50 dark:bg-neutral-800/20 py-4">
                                <CardTitle className="text-lg flex items-center gap-2">
                                    <ShieldCheck className="h-5 w-5 text-neutral-400" />
                                    Capacidades Asignadas
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-6">
                                <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                    {Object.keys(groupedPermissions).length > 0 ? (
                                        Object.entries(groupedPermissions).map(([module, perms]) => (
                                            <div key={module} className="space-y-3 p-4 rounded-xl border border-neutral-100 dark:border-neutral-800 bg-neutral-50/30 dark:bg-neutral-900/30">
                                                <h3 className="text-xs font-bold uppercase tracking-widest text-neutral-500 flex justify-between items-center border-b border-neutral-100 dark:border-neutral-800 pb-2">
                                                    {module}
                                                    <span className="bg-primary/10 text-primary px-1.5 rounded text-[10px]">{perms.length}</span>
                                                </h3>
                                                <div className="flex flex-wrap gap-1.5">
                                                    {perms.map((perm) => (
                                                        <Badge
                                                            key={perm}
                                                            variant="outline"
                                                            className="bg-white dark:bg-neutral-900 px-2 py-0.5 text-[10px] text-neutral-700 dark:text-neutral-300 font-medium capitalize"
                                                        >
                                                            {perm.split('.')[1]}
                                                        </Badge>
                                                    ))}
                                                </div>
                                            </div>
                                        ))
                                    ) : (
                                        <div className="col-span-full flex flex-col items-center justify-center py-12 text-center text-neutral-500">
                                            <div className="bg-neutral-100 dark:bg-neutral-800 rounded-full p-4 mb-4">
                                                <ShieldCheck className="h-12 w-12 opacity-20" />
                                            </div>
                                            <p className="font-medium">Sin permisos asignados</p>
                                            <p className="text-sm opacity-70">Este rol no tiene capacidades configuradas todavía.</p>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                        
                        {isProtected && (
                            <div className="rounded-xl border border-blue-100 bg-blue-50/50 p-4 dark:border-blue-900/30 dark:bg-blue-950/20">
                                <div className="flex gap-3">
                                    <ShieldCheck className="h-5 w-5 text-blue-500 shrink-0" />
                                    <div className="space-y-1">
                                        <p className="text-sm font-semibold text-blue-900 dark:text-blue-300">Rol Protegido del Sistema</p>
                                        <p className="text-xs text-blue-700 dark:text-blue-400 leading-relaxed">
                                            Este es un rol fundamental para el funcionamiento de la aplicación. Su nombre y propósito están protegidos, pero sus permisos específicos pueden ser ajustados por un administrador según las necesidades de la institución.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
