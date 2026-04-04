import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
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

            <div className="mx-auto max-w-2xl p-4 lg:p-8">
                {/* Header */}
                <div className="mb-6">
                    <Button
                        variant="ghost"
                        size="sm"
                        asChild
                        className="mb-2 -ml-2 h-8"
                    >
                        <Link href={userIndex().url}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver al listado
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                        Editar Usuario
                    </h1>
                    <p className="text-sm text-neutral-500 dark:text-neutral-400">
                        Modifica los datos del perfil y los accesos de{' '}
                        {user.name}.
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
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
                            <p className="text-xs text-neutral-500">
                                Asigna capacidades específicas adicionales. Los
                                permisos otorgados por roles aparecen
                                deshabilitados.
                            </p>
                            <div className="grid gap-6 sm:grid-cols-2">
                                {Object.entries(groupedPermissions).map(
                                    ([module, perms]) => (
                                        <div key={module} className="space-y-2">
                                            <h3 className="text-xs font-semibold tracking-wider text-neutral-500 uppercase">
                                                {module}
                                            </h3>
                                            <div className="space-y-1.5">
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
                                                            className="flex items-center gap-2"
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
                                                                onCheckedChange={() =>
                                                                    toggleDirectPermission(
                                                                        perm,
                                                                    )
                                                                }
                                                            />
                                                            <Label
                                                                htmlFor={`direct-perm-${perm}`}
                                                                className={`cursor-pointer text-sm capitalize ${isInherited ? 'text-neutral-400' : ''}`}
                                                            >
                                                                {action}
                                                                {isInherited && (
                                                                    <Badge
                                                                        variant="outline"
                                                                        className="ml-1 px-1 py-0 text-[8px] font-normal uppercase"
                                                                    >
                                                                        rol
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

                    {/* Actions */}
                    <div className="flex items-center justify-end gap-3">
                        <Button variant="outline" asChild disabled={processing}>
                            <Link href={userIndex().url}>Cancelar</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? (
                                'Guardando...'
                            ) : (
                                <>
                                    <Save className="mr-2 h-4 w-4" />
                                    Actualizar Usuario
                                </>
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
