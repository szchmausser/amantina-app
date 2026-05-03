import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, User, Lock } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import { index as userIndex, store as userStore } from '@/routes/admin/users';
import type { BreadcrumbItem } from '@/types';

interface Props {
    roles: string[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Gestión de Usuarios', href: '/admin/users' },
    { title: 'Nuevo Usuario', href: '/admin/users/create' },
];

export default function Create({ roles }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        cedula: '',
        phone: '',
        address: '',
        roles: [] as string[],
        password: '',
        password_confirmation: '',
        is_transfer: false,
        institution_origin: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(userStore().url);
    };

    const toggleRole = (roleName: string) => {
        const newRoles = data.roles.includes(roleName)
            ? data.roles.filter((r) => r !== roleName)
            : [...data.roles, roleName];

        const isAlumno = newRoles.includes('alumno');

        setData((previousData) => ({
            ...previousData,
            roles: newRoles,
            institution_origin: !isAlumno
                ? ''
                : previousData.institution_origin,
            is_transfer: !isAlumno ? false : previousData.is_transfer,
        }));
    };

    const isAlumno = data.roles.includes('alumno');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo Usuario" />

            <div className="p-4 lg:p-8">
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
                        Nuevo Usuario
                    </h1>
                    <p className="text-sm text-neutral-500 dark:text-neutral-400">
                        Completa la información para crear una nueva cuenta en
                        el sistema.
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Roles Card */}
                    <div className="overflow-hidden rounded-xl border">
                        <div className="flex items-center gap-2 border-b bg-neutral-50 px-6 py-4 dark:bg-neutral-800/50">
                            <User className="h-4 w-4 text-neutral-500" />
                            <h2 className="text-sm font-semibold">
                                Roles y Datos Básicos
                            </h2>
                        </div>
                        <div className="grid gap-6 p-6">
                            <div className="space-y-3">
                                <Label>Roles en el Sistema</Label>
                                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
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
                                                data-test={`role-checkbox-${r}`}
                                            />
                                            <Label
                                                htmlFor={`role-${r}`}
                                                className="cursor-pointer capitalize"
                                            >
                                                {r}
                                            </Label>
                                        </div>
                                    ))}
                                </div>
                                <InputError message={errors.roles} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="cedula">Cédula</Label>
                                    <Input
                                        id="cedula"
                                        value={data.cedula}
                                        onChange={(e) =>
                                            setData('cedula', e.target.value)
                                        }
                                        placeholder="V-12345678"
                                        data-test="cedula-input"
                                    />
                                    <InputError message={errors.cedula} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="name">
                                        Nombre Completo
                                    </Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) =>
                                            setData('name', e.target.value)
                                        }
                                        placeholder="Nombre completo"
                                        data-test="name-input"
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
                                        placeholder="ejemplo@amantina.test"
                                        data-test="email-input"
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="phone">
                                        Teléfono{' '}
                                        {!isAlumno && (
                                            <span className="text-red-500">
                                                *
                                            </span>
                                        )}
                                        {isAlumno && (
                                            <span className="text-xs font-normal text-neutral-400">
                                                {' '}
                                                (Opcional)
                                            </span>
                                        )}
                                    </Label>
                                    <Input
                                        id="phone"
                                        value={data.phone}
                                        onChange={(e) =>
                                            setData('phone', e.target.value)
                                        }
                                        placeholder="0412-1234567"
                                        data-test="phone-input"
                                    />
                                    <InputError message={errors.phone} />
                                </div>

                                <div className="space-y-2 sm:col-span-2">
                                    <Label htmlFor="address">
                                        Dirección{' '}
                                        {!isAlumno && (
                                            <span className="text-red-500">
                                                *
                                            </span>
                                        )}
                                        {isAlumno && (
                                            <span className="text-xs font-normal text-neutral-400">
                                                {' '}
                                                (Opcional)
                                            </span>
                                        )}
                                    </Label>
                                    <Input
                                        id="address"
                                        value={data.address}
                                        onChange={(e) =>
                                            setData('address', e.target.value)
                                        }
                                        placeholder="Ciudad, Sector, Calle..."
                                        data-test="address-input"
                                    />
                                    <InputError message={errors.address} />
                                </div>
                            </div>

                            {/* Transfer section */}
                            {isAlumno && (
                                <div className="rounded-lg border bg-neutral-50 p-4 dark:bg-neutral-800/30">
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
                                            className="cursor-pointer"
                                        >
                                            ¿Es alumno transferido de otra
                                            institución?
                                        </Label>
                                    </div>
                                    {data.is_transfer && (
                                        <div className="mt-3 space-y-2">
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
                                                message={
                                                    errors.institution_origin
                                                }
                                            />
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Password Card */}
                    <div className="overflow-hidden rounded-xl border">
                        <div className="flex items-center gap-2 border-b bg-neutral-50 px-6 py-4 dark:bg-neutral-800/50">
                            <Lock className="h-4 w-4 text-neutral-500" />
                            <h2 className="text-sm font-semibold">
                                Contraseña
                            </h2>
                        </div>
                        <div className="grid gap-6 p-6 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="password">Contraseña</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(e) =>
                                        setData('password', e.target.value)
                                    }
                                    placeholder="Mínimo 8 caracteres"
                                    data-test="password-input"
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
                                    placeholder="Repite la contraseña"
                                    data-test="password-confirmation-input"
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>
                        </div>
                    </div>

                    {/* Actions */}
                    <div className="flex items-center justify-end gap-3">
                        <Button variant="outline" asChild disabled={processing}>
                            <Link href={userIndex().url}>Cancelar</Link>
                        </Button>
                        <Button type="submit" disabled={processing} data-test="submit-button">
                            {processing ? (
                                'Guardando...'
                            ) : (
                                <>
                                    <Save className="mr-2 h-4 w-4" />
                                    Crear Usuario
                                </>
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
