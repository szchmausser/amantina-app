import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Edit, ShieldCheck, User as UserIcon, BookOpen } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { index as userIndex, edit as userEdit } from '@/routes/admin/users';
import type { BreadcrumbItem, User } from '@/types';

interface Props {
    user: User & { roles: any[]; permissions: any[] };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Usuarios', href: '/admin/users' },
    { title: 'Detalles', href: '#' },
];

export default function Show({ user }: Props) {
    const { auth } = usePage<any>().props;
    const hasPermission = (p: string) => auth.permissions?.includes(p);

    const roles = user.roles ? user.roles.map((r: any) => r.name) : [];
    const directPermissions = user.permissions ? user.permissions.map((p: any) => p.name) : [];
    
    // Collect all permissions from all roles
    const rolePermissions = new Set<string>();
    user.roles?.forEach((r: any) => {
        r.permissions?.forEach((p: any) => rolePermissions.add(p.name));
    });

    // Group all unique permissions by module
    const groupedPermissions: Record<string, string[]> = {};
    const allUserPermissions = Array.from(new Set([...Array.from(rolePermissions), ...directPermissions]));

    allUserPermissions.sort().forEach((p) => {
        const module = p.split('.')[0];
        if (!groupedPermissions[module]) {
            groupedPermissions[module] = [];
        }
        groupedPermissions[module].push(p);
    });

    const isAlumno = roles.includes('alumno');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Usuario: ${user.name}`} />

            <div className="mx-auto max-w-4xl p-4 lg:p-8">
                <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <Button variant="ghost" size="sm" asChild className="-ml-2 mb-2 h-8">
                            <Link href={userIndex().url}>
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver al listado
                            </Link>
                        </Button>
                        <div className="flex items-center gap-3">
                            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 dark:bg-neutral-800">
                                <UserIcon className="h-6 w-6 text-neutral-500" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">{user.name}</h1>
                                <div className="flex flex-wrap items-center gap-2">
                                    {roles.map((role) => (
                                        <Badge key={role} variant="secondary" className="capitalize">
                                            {role}
                                        </Badge>
                                    ))}
                                    {user.is_active ? (
                                        <Badge className="bg-green-500/10 text-green-500 hover:bg-green-500/20">Activo</Badge>
                                    ) : (
                                        <Badge variant="destructive">Inactivo</Badge>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                    {(hasPermission('users.edit') || auth.user.id === user.id) && (
                        <Button asChild>
                            <Link href={userEdit(user.id).url}>
                                <Edit className="mr-2 h-4 w-4" />
                                Editar Perfil
                            </Link>
                        </Button>
                    )}
                </div>

                <div className="space-y-6">
                    {/* Información Personal */}
                    <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <div className="flex items-center gap-2 bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                            <UserIcon className="h-4 w-4 text-neutral-500" />
                            <h2 className="text-sm font-semibold uppercase tracking-wide text-neutral-600 dark:text-neutral-300">
                                Información Personal
                            </h2>
                        </div>
                        <div className="grid gap-6 p-6 md:grid-cols-2">
                            <div className="space-y-1">
                                <p className="text-[10px] font-bold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Cédula</p>
                                <p className="text-sm font-medium">{user.cedula || '—'}</p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-[10px] font-bold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Correo Electrónico</p>
                                <p className="text-sm font-medium">{user.email}</p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-[10px] font-bold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Teléfono</p>
                                <p className="text-sm font-medium">{user.phone || '—'}</p>
                            </div>
                            <div className="space-y-1">
                                <p className="text-[10px] font-bold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Dirección</p>
                                <p className="text-sm font-medium">{user.address || '—'}</p>
                            </div>
                        </div>
                    </div>

                    {/* Información Académica (Solo si es alumno) */}
                    {isAlumno && (
                        <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                            <div className="flex items-center gap-2 bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                <BookOpen className="h-4 w-4 text-neutral-500" />
                                <h2 className="text-sm font-semibold uppercase tracking-wide text-neutral-600 dark:text-neutral-300">
                                    Información Académica
                                </h2>
                            </div>
                            <div className="grid gap-6 p-6 md:grid-cols-2">
                                <div className="space-y-1">
                                    <p className="text-[10px] font-bold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Tipo de Ingreso</p>
                                    <Badge variant="outline" className="font-medium">
                                        {user.is_transfer ? 'Transferido' : 'Regular'}
                                    </Badge>
                                </div>
                                {user.is_transfer && (
                                    <div className="space-y-1">
                                        <p className="text-[10px] font-bold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Institución de Procedencia</p>
                                        <p className="text-sm font-medium">{user.institution_origin || 'No especificada'}</p>
                                    </div>
                                )}
                                <div className="col-span-full rounded-lg bg-neutral-50 p-3 dark:bg-neutral-800/50">
                                    <p className="text-xs italic text-neutral-500">
                                        * El grado y sección se gestionan en el módulo de Inscripciones.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Matriz de Permisos */}
                    <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <div className="flex items-center justify-between bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                            <div className="flex items-center gap-2">
                                <ShieldCheck className="h-4 w-4 text-neutral-500" />
                                <h2 className="text-sm font-semibold uppercase tracking-wide text-neutral-600 dark:text-neutral-300">
                                    Capacidades y Permisos
                                </h2>
                            </div>
                            <div className="flex gap-3">
                                <div className="flex items-center gap-1.5">
                                    <div className="h-2 w-2 rounded-full border border-neutral-300 bg-white dark:border-neutral-600 dark:bg-neutral-900"></div>
                                    <span className="text-[10px] font-bold uppercase tracking-tighter text-neutral-400">Heredado</span>
                                </div>
                                <div className="flex items-center gap-1.5">
                                    <div className="h-2 w-2 rounded-full bg-blue-500"></div>
                                    <span className="text-[10px] font-bold uppercase tracking-tighter text-neutral-400">Directo</span>
                                </div>
                            </div>
                        </div>
                        <div className="divide-y divide-sidebar-border/70 p-6">
                            {Object.keys(groupedPermissions).length > 0 ? (
                                <div className="grid gap-6 sm:grid-cols-2">
                                    {Object.entries(groupedPermissions).map(([module, perms]) => (
                                        <div key={module} className="space-y-2">
                                            <h3 className="text-[10px] font-black uppercase tracking-widest text-neutral-400 dark:text-neutral-500">
                                                {module}
                                            </h3>
                                            <div className="flex flex-wrap gap-1.5">
                                                {perms.map((perm) => {
                                                    const action = perm.split('.')[1];
                                                    const isDirect = directPermissions.includes(perm);
                                                    const isInherited = rolePermissions.has(perm);

                                                    return (
                                                        <Badge
                                                            key={perm}
                                                            variant="outline"
                                                            className={`px-2 py-0.5 text-[10px] font-normal ${
                                                                isDirect
                                                                    ? 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/50 dark:bg-blue-950/30 dark:text-blue-400'
                                                                    : 'text-neutral-500 dark:text-neutral-400'
                                                            }`}
                                                        >
                                                            {action}
                                                            {isDirect && isInherited && (
                                                                <span className="ml-1 text-[8px] opacity-60">(ambos)</span>
                                                            )}
                                                        </Badge>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="flex flex-col items-center justify-center py-8 text-center text-neutral-400">
                                    <ShieldCheck className="mb-2 h-10 w-10 opacity-20" />
                                    <p className="text-sm italic">Sin permisos asignados.</p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
