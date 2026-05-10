import { Head, Link, useForm } from '@inertiajs/react';
import {
    Save,
    ShieldCheck,
    User as UserIcon,
    BookOpen,
    Key,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { index as userIndex, update as userUpdate } from '@/routes/admin/users';
import type { BreadcrumbItem, User } from '@/types';

interface Props {
    user: User & { roles: any[]; permissions: any[] };
    roles: string[];
    allPermissions: string[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Usuarios', href: '/admin/users' },
    { title: 'Editar', href: '#' },
];

export default function Edit({ user, roles, allPermissions }: Props) {
    const currentRoles = user.roles ? user.roles.map((r: any) => r.name) : [];
    const currentDirectPermissions = user.permissions
        ? user.permissions.map((p: any) => p.name)
        : [];

    const { data, setData, put, processing, errors } = useForm({
        name: user.name || '',
        email: user.email || '',
        cedula: user.cedula || '',
        phone: user.phone || '',
        address: user.address || '',
        roles: currentRoles,
        password: '',
        password_confirmation: '',
        is_transfer: !!user.is_transfer,
        institution_origin: (user.institution_origin as string) || '',
        direct_permissions: currentDirectPermissions,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(userUpdate(user.id).url);
    };

    const isAlumno = data.roles.includes('alumno');

    const toggleRole = (roleName: string) => {
        setData(
            'roles',
            data.roles.includes(roleName)
                ? data.roles.filter((r: string) => r !== roleName)
                : [...data.roles, roleName],
        );
    };

    const toggleDirectPermission = (permName: string) => {
        setData(
            'direct_permissions',
            data.direct_permissions.includes(permName)
                ? data.direct_permissions.filter((p: string) => p !== permName)
                : [...data.direct_permissions, permName],
        );
    };

    const groupedPermissions: Record<string, string[]> = {};
    allPermissions.sort().forEach((p) => {
        const module = p.split('.')[0];
        if (!groupedPermissions[module]) {
            groupedPermissions[module] = [];
        }
        groupedPermissions[module].push(p);
    });

    const inheritedPermissions = new Set<string>();
    user.roles?.forEach((r: any) => {
        r.permissions?.forEach((p: any) => inheritedPermissions.add(p.name));
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar Usuario: ${user.name}`} />

            <SettingsLayout>
                <div className="px-4 py-4">
                    {/* Header */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                Editar Usuario
                            </h1>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                Modifica los datos del perfil y los accesos de{' '}
                                {user.name}.
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            <Button variant="outline" asChild>
                                <Link href={userIndex().url}>
                                    Volver
                                </Link>
                            </Button>
                            <Button type="submit" form="user-form" disabled={processing}>
                                <Save className="mr-2 h-4 w-4" />
                                Actualizar Usuario
                            </Button>
                        </div>
                    </div>

                    <form id="user-form" onSubmit={handleSubmit} className="space-y-6">
                    {/* Profile Card */}
                    <div className="overflow-hidden rounded-xl border">
                        <div className="flex items-center gap-2 border-b bg-neutral-50 px-6 py-4 dark:bg-neutral-800/50">
                            <UserIcon className="h-4 w-4 text-neutral-500" />
                            <h2 className="text-sm font-semibold">
                                Información del Perfil
                            </h2>
                        </div>
                        <div className="grid gap-6 p-6 sm:grid-cols-2">
                            <div className="col-span-full space-y-3">
                                <Label>Roles en el Sistema</Label>
                                <div className="flex flex-wrap gap-4 rounded-lg border border-dashed p-4">
                                    {roles.map((r) => (
                                        <div
                                            key={r}
                                            className="flex items-center space-x-2"
                                        >
                                            <Checkbox
                                                id={`role-${r}`}
                                                checked={data.roles.includes(r)}
                                                onCheckedChange={() =>
                                                    toggleRole(r)
                                                }
                                            />
                                            <Label
                                                htmlFor={`role-${r}`}
                                                className="cursor-pointer text-sm capitalize"
                                            >
                                                {r}
                                            </Label>
                                        </div>
                                    ))}
                                </div>
                                <InputError message={errors.roles} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="cedula">Cédula</Label>
                                <Input
                                    id="cedula"
                                    value={data.cedula || ''}
                                    onChange={(e) =>
                                        setData('cedula', e.target.value)
                                    }
                                    placeholder="V-12345678"
                                />
                                <InputError message={errors.cedula} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="name">Nombre Completo</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    placeholder="Nombre completo"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="email">
                                    Correo Electrónico
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) =>
                                        setData('email', e.target.value)
                                    }
                                    placeholder="correo@ejemplo.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="phone">Teléfono</Label>
                                <Input
                                    id="phone"
                                    value={data.phone || ''}
                                    onChange={(e) =>
                                        setData('phone', e.target.value)
                                    }
                                    placeholder="0412-0000000"
                                />
                                <InputError message={errors.phone} />
                            </div>

                            <div className="col-span-full space-y-2">
                                <Label htmlFor="address">Dirección</Label>
                                <Input
                                    id="address"
                                    value={data.address || ''}
                                    onChange={(e) =>
                                        setData('address', e.target.value)
                                    }
                                    placeholder="Dirección completa"
                                />
                                <InputError message={errors.address} />
                            </div>
                        </div>
                    </div>

                    {/* Academic Info Card (if alumno) */}
                    {isAlumno && (
                        <div className="overflow-hidden rounded-xl border">
                            <div className="flex items-center gap-2 border-b bg-neutral-50 px-6 py-4 dark:bg-neutral-800/50">
                                <BookOpen className="h-4 w-4 text-neutral-500" />
                                <h2 className="text-sm font-semibold">
                                    Información Académica
                                </h2>
                            </div>
                            <div className="space-y-4 p-6">
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="is_transfer"
                                        checked={data.is_transfer}
                                        onCheckedChange={(checked) =>
                                            setData(
                                                'is_transfer',
                                                checked as boolean,
                                            )
                                        }
                                    />
                                    <Label
                                        htmlFor="is_transfer"
                                        className="cursor-pointer text-sm"
                                    >
                                        ¿Es alumno transferido de otra
                                        institución?
                                    </Label>
                                </div>
                                {data.is_transfer && (
                                    <div className="space-y-2">
                                        <Label htmlFor="institution_origin">
                                            Institución de Procedencia
                                        </Label>
                                        <Input
                                            id="institution_origin"
                                            value={data.institution_origin}
                                            onChange={(e) =>
                                                setData(
                                                    'institution_origin',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Nombre del plantel anterior"
                                        />
                                        <InputError
                                            message={errors.institution_origin}
                                        />
                                    </div>
                                )}
                                <div className="rounded-lg bg-neutral-50 p-3 text-xs text-neutral-500 italic dark:bg-neutral-800/30">
                                    Los datos de grado y sección se asignan al
                                    momento de realizar la inscripción.
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Security Card */}
                    <div className="overflow-hidden rounded-xl border">
                        <div className="flex items-center gap-2 border-b bg-neutral-50 px-6 py-4 dark:bg-neutral-800/50">
                            <Key className="h-4 w-4 text-neutral-500" />
                            <h2 className="text-sm font-semibold">Seguridad</h2>
                        </div>
                        <div className="grid gap-6 p-6 sm:grid-cols-2">
                            <div className="col-span-full text-xs text-neutral-500 italic">
                                Dejar en blanco si no desea cambiar la
                                contraseña actual.
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="password">
                                    Nueva Contraseña
                                </Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(e) =>
                                        setData('password', e.target.value)
                                    }
                                    autoComplete="new-password"
                                />
                                <InputError message={errors.password} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="password_confirmation">
                                    Confirmar Contraseña
                                </Label>
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    value={data.password_confirmation}
                                    onChange={(e) =>
                                        setData(
                                            'password_confirmation',
                                            e.target.value,
                                        )
                                    }
                                    autoComplete="new-password"
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>
                        </div>
                    </div>

                    {/* Direct Permissions Card */}
                    <div className="overflow-hidden rounded-xl border">
                        <div className="flex items-center gap-2 border-b bg-neutral-50 px-6 py-4 dark:bg-neutral-800/50">
                            <ShieldCheck className="h-4 w-4 text-neutral-500" />
                            <h2 className="text-sm font-semibold">
                                Permisos Directos
                            </h2>
                        </div>
                        <div className="space-y-4 p-6">
                            <div className="flex items-start gap-3 rounded-lg border bg-neutral-50 px-4 py-3 dark:bg-neutral-900/30 dark:border-neutral-700">
                                <ShieldCheck className="mt-0.5 h-4 w-4 shrink-0 text-neutral-400" />
                                <div className="text-xs text-neutral-600 dark:text-neutral-400">
                                    <p className="font-medium text-neutral-700 dark:text-neutral-300">¿Qué son los permisos directos?</p>
                                    <p className="mt-0.5">
                                        Son capacidades que se asignan <strong>adicionalmente</strong> a un usuario, fuera de las que ya tiene por su rol.
                                        Los que ya están cubiertos por el rol aparecen <strong>deshabilitados</strong> con la etiqueta <Badge variant="outline" className="mx-0.5 px-1 py-0 text-[8px] font-normal uppercase align-middle border-amber-200 text-amber-600 bg-amber-50 dark:border-amber-800 dark:text-amber-500 dark:bg-amber-950/30">rol</Badge>.
                                    </p>
                                </div>
                            </div>
                            <div className="grid sm:grid-cols-5 gap-y-6 divide-x divide-neutral-200 dark:divide-neutral-700">
                                {Object.entries(groupedPermissions).map(
                                    ([module, perms]) => (
                                        <div key={module} className="space-y-3 px-3">
                                            <h3 className="border-b border-neutral-100 pb-1.5 text-[11px] font-bold tracking-wider text-neutral-500 uppercase dark:border-neutral-800">
                                                {module}
                                            </h3>
                                            <div className="space-y-2.5">
                                                {perms.map((perm) => {
                                                    const action =
                                                        perm.split('.')[1];
                                                    const isInherited =
                                                        inheritedPermissions.has(
                                                            perm,
                                                        );
                                                    const isDirectlyAssigned =
                                                        data.direct_permissions.includes(
                                                            perm,
                                                        );

                                                    return (
                                                        <div
                                                            key={perm}
                                                            className={`flex items-start gap-2.5 rounded px-1.5 py-1 -mx-1.5 ${isInherited ? 'bg-neutral-50 dark:bg-neutral-800/40' : ''}`}
                                                            title={isInherited ? `Este permiso lo hereda del rol que tiene asignado. No se puede modificar directamente.` : undefined}
                                                        >
                                                            <Checkbox
                                                                id={`direct-perm-${perm}`}
                                                                checked={
                                                                    isDirectlyAssigned ||
                                                                    isInherited
                                                                }
                                                                disabled={
                                                                    isInherited
                                                                }
                                                                className={`mt-0.5 ${isInherited ? 'opacity-40' : ''}`}
                                                                onCheckedChange={() =>
                                                                    toggleDirectPermission(
                                                                        perm,
                                                                    )
                                                                }
                                                            />
                                                            <Label
                                                                htmlFor={`direct-perm-${perm}`}
                                                                className={`cursor-pointer text-sm leading-tight capitalize ${isInherited ? 'text-neutral-400 dark:text-neutral-500' : 'text-neutral-700 dark:text-neutral-300'}`}
                                                            >
                                                                {action}
                                                                {isInherited && (
                                                                    <Badge
                                                                        variant="outline"
                                                                        className="ml-1 px-1.5 py-0.5 text-[10px] font-medium uppercase align-middle border-amber-300 text-amber-700 bg-amber-50 dark:border-amber-700 dark:text-amber-400 dark:bg-amber-950/30"
                                                                    >
                                                                heredado
                                                                    </Badge>
                                                                )}
                                                                {isDirectlyAssigned && !isInherited && (
                                                                    <Badge
                                                                        variant="outline"
                                                                        className="ml-1 px-1.5 py-0.5 text-[10px] font-medium uppercase align-middle border-blue-300 text-blue-700 bg-blue-50 dark:border-blue-700 dark:text-blue-400 dark:bg-blue-950/30"
                                                                    >
                                                                asignado
                                                                    </Badge>
                                                                )}
                                                            </Label>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    ),
                                )}
                            </div>
                        </div>
                    </div>

                </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
