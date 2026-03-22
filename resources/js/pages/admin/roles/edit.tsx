import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Check, Shield } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
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

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
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

                <form onSubmit={handleSubmit} className="max-w-2xl space-y-6">
                    {Object.entries(groups).map(([module, modulePerms]) => {
                        const modulePermNames = modulePerms.map((p) => p.name);
                        const allSelected = modulePermNames.every((n) => data.permissions.includes(n));
                        const someSelected =
                            !allSelected && modulePermNames.some((n) => data.permissions.includes(n));

                        return (
                            <div
                                key={module}
                                className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                            >
                                <div className="mb-3 flex items-center gap-2">
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
                                        className="text-sm font-semibold uppercase tracking-wide"
                                    >
                                        {module}
                                    </Label>
                                    <Badge variant="secondary" className="text-xs">
                                        {modulePermNames.filter((n) => data.permissions.includes(n)).length}/
                                        {modulePerms.length}
                                    </Badge>
                                </div>

                                <div className="ml-6 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                    {modulePerms.map((perm) => {
                                        const action = perm.name.split('.')[1];
                                        return (
                                            <div key={perm.id} className="flex items-center gap-2">
                                                <Checkbox
                                                    id={`perm-${perm.id}`}
                                                    checked={data.permissions.includes(perm.name)}
                                                    onCheckedChange={() => togglePermission(perm.name)}
                                                />
                                                <Label htmlFor={`perm-${perm.id}`} className="text-sm">
                                                    {action}
                                                </Label>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        );
                    })}

                    <div className="flex items-center gap-4">
                        <Button type="submit" disabled={processing}>
                            Guardar Permisos
                        </Button>

                        {recentlySuccessful && (
                            <p className="text-muted-foreground flex items-center gap-1.5 text-sm">
                                <Check className="h-4 w-4" />
                                Guardado correctamente
                            </p>
                        )}
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
