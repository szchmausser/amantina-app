import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Check, Shield } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
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
    allPermissions: Permission[];
}

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

export default function RolesEdit({ role, allPermissions }: Props) {
    const currentPermissionNames = role.permissions.map((p) => p.name);

    const { data, setData, put, processing, recentlySuccessful } = useForm({
        permissions: currentPermissionNames,
    });

    const togglePermission = (permName: string) => {
        setData(
            'permissions',
            data.permissions.includes(permName)
                ? data.permissions.filter((p) => p !== permName)
                : [...data.permissions, permName],
        );
    };

    const toggleModule = (moduleName: string, modulePerms: Permission[]) => {
        const modulePermNames = modulePerms.map((p) => p.name);
        const allSelected = modulePermNames.every((n) => data.permissions.includes(n));

        if (allSelected) {
            setData(
                'permissions',
                data.permissions.filter((p) => !modulePermNames.includes(p)),
            );
        } else {
            const newPerms = new Set([...data.permissions, ...modulePermNames]);
            setData('permissions', Array.from(newPerms));
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/admin/roles/${role.id}`);
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Gestión de Roles', href: '/admin/roles' },
        { title: `Editar: ${role.name}`, href: `/admin/roles/${role.id}/edit` },
    ];

    const groups = groupPermissions(allPermissions);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar Rol: ${role.name}`} />

            <SettingsLayout>
                <div className="space-y-6 text-neutral-900 dark:text-neutral-100">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href="/admin/roles">
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div>
                            <div className="flex items-center gap-2">
                                <Shield className="h-5 w-5 text-neutral-500" />
                                <h1 className="text-2xl font-bold capitalize tracking-tight">{role.name}</h1>
                            </div>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Gestiona los permisos asignados a este rol.
                            </p>
                        </div>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="grid gap-6 sm:grid-cols-1 lg:grid-cols-2">
                            {Object.entries(groups).map(([module, modulePerms]) => {
                                const modulePermNames = modulePerms.map((p) => p.name);
                                const allSelected = modulePermNames.every((n) => data.permissions.includes(n));
                                const someSelected =
                                    !allSelected && modulePermNames.some((n) => data.permissions.includes(n));

                                return (
                                    <div
                                        key={module}
                                        className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border bg-white dark:bg-neutral-900/50 shadow-sm"
                                    >
                                        <div className="mb-4 flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-2">
                                            <div className="flex items-center gap-2">
                                                <Checkbox
                                                    id={`module-${module}`}
                                                    checked={allSelected}
                                                    ref={(el) => {
                                                        if (el) {
                                                            (el as unknown as HTMLInputElement).indeterminate = someSelected;
                                                        }
                                                    }}
                                                    onCheckedChange={() => toggleModule(module, modulePerms)}
                                                />
                                                <Label
                                                    htmlFor={`module-${module}`}
                                                    className="text-sm font-bold uppercase tracking-wide cursor-pointer"
                                                >
                                                    {module}
                                                </Label>
                                            </div>
                                            <Badge variant="secondary" className="text-[10px] h-5">
                                                {modulePermNames.filter((n) => data.permissions.includes(n)).length} / {modulePerms.length}
                                            </Badge>
                                        </div>

                                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                            {modulePerms.map((perm) => {
                                                const action = perm.name.split('.')[1];
                                                const isSelected = data.permissions.includes(perm.name);
                                                return (
                                                    <div 
                                                        key={perm.id} 
                                                        className={`flex items-center gap-2 p-2 rounded-lg border transition-colors cursor-pointer ${
                                                            isSelected 
                                                                ? 'bg-primary/5 border-primary/20' 
                                                                : 'border-transparent hover:bg-neutral-50 dark:hover:bg-neutral-800'
                                                        }`}
                                                        onClick={() => togglePermission(perm.name)}
                                                    >
                                                        <Checkbox
                                                            id={`perm-${perm.id}`}
                                                            checked={isSelected}
                                                            onCheckedChange={() => togglePermission(perm.name)}
                                                            className="pointer-events-none"
                                                        />
                                                        <Label htmlFor={`perm-${perm.id}`} className="text-xs capitalize flex-1 cursor-pointer">
                                                            {action}
                                                        </Label>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>

                        <div className="flex items-center gap-4 pt-4 border-t border-neutral-100 dark:border-neutral-800">
                            <Button type="submit" disabled={processing} size="lg">
                                {processing ? 'Guardando...' : 'Guardar Permisos'}
                            </Button>

                            {recentlySuccessful && (
                                <p className="text-green-600 flex items-center gap-1.5 text-sm font-medium animate-in fade-in slide-in-from-left-2">
                                    <Check className="h-4 w-4" />
                                    Permisos actualizados correctamente
                                </p>
                            )}
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
