import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import { index as userIndex, update as userUpdate } from '@/routes/admin/users';
import type { BreadcrumbItem, User } from '@/types';

interface Props {
    user: User & { roles: any[] };
    roles: string[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Gestión de Usuarios',
        href: '/admin/users',
    },
    {
        title: 'Editar Usuario',
        href: '#',
    },
];

export default function Edit({ user, roles }: Props) {
    const currentRole = user.roles && user.roles.length > 0 ? user.roles[0].name : '';

    const { data, setData, put, processing, errors } = useForm({
        name: user.name || '',
        email: user.email || '',
        cedula: user.cedula || '',
        phone: user.phone || '',
        address: user.address || '',
        role: currentRole,
        password: '',
        password_confirmation: '',
        is_transfer: !!user.is_transfer,
        institution_origin: (user.institution_origin as string) || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(userUpdate(user.id).url);
    };

    const isAlumno = data.role === 'alumno';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar Usuario: ${user.name}`} />

            <div className="mx-auto max-w-4xl p-4 lg:p-8">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <Button variant="ghost" size="sm" asChild className="-ml-2 mb-2">
                            <Link href={userIndex().url}>
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver al listado
                            </Link>
                        </Button>
                        <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">Editar Usuario</h1>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                            Modifica los datos del perfil de {user.name}.
                        </p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid gap-6 rounded-xl border border-sidebar-border/70 p-6 md:grid-cols-2 dark:border-sidebar-border">
                        <div className="col-span-full mb-2 border-b pb-2">
                            <h2 className="text-lg font-semibold">Información Básica</h2>
                            <p className="text-xs text-neutral-500">Datos de identificación y contacto institucional.</p>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="role">Rol en el Sistema</Label>
                            <Select value={data.role} onValueChange={(v) => setData('role', v)}>
                                <SelectTrigger className={errors.role ? 'border-red-500' : ''}>
                                    <SelectValue placeholder="Seleccionar rol" />
                                </SelectTrigger>
                                <SelectContent>
                                    {roles.map((r) => (
                                        <SelectItem key={r} value={r}>
                                            {r.charAt(0).toUpperCase() + r.slice(1)}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.role} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="cedula">Cédula de Identidad</Label>
                            <Input
                                id="cedula"
                                value={data.cedula || ''}
                                onChange={(e) => setData('cedula', e.target.value)}
                                placeholder="V-12345678"
                            />
                            <InputError message={errors.cedula} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="name">Nombre Completo</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder="Nombre completo del usuario"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="email">Correo Electrónico</Label>
                            <Input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder="ejemplo@amantina.test"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="phone">
                                Teléfono {isAlumno && <span className="text-xs font-normal text-neutral-400">(Opcional para alumnos)</span>}
                            </Label>
                            <Input
                                id="phone"
                                value={data.phone || ''}
                                onChange={(e) => setData('phone', e.target.value)}
                                placeholder="0412-1234567"
                            />
                            <InputError message={errors.phone} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="address">
                                Dirección de Residencia {isAlumno && <span className="text-xs font-normal text-neutral-400">(Opcional para alumnos)</span>}
                            </Label>
                            <Input
                                id="address"
                                value={data.address || ''}
                                onChange={(e) => setData('address', e.target.value)}
                                placeholder="Ciudad, Sector, Calle..."
                            />
                            <InputError message={errors.address} />
                        </div>

                        {data.role === 'alumno' && (
                            <div className="col-span-full space-y-4 rounded-lg bg-neutral-50 p-4 dark:bg-neutral-800/50">
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="is_transfer"
                                        checked={data.is_transfer}
                                        onCheckedChange={(checked) => setData('is_transfer', checked as boolean)}
                                    />
                                    <Label htmlFor="is_transfer" className="cursor-pointer">¿Es alumno transferido de otra institución?</Label>
                                </div>
                                {data.is_transfer && (
                                    <div className="space-y-2">
                                        <Label htmlFor="institution_origin">Institución de Procedencia</Label>
                                        <Input
                                            id="institution_origin"
                                            value={data.institution_origin}
                                            onChange={(e) => setData('institution_origin', e.target.value)}
                                            placeholder="Nombre del plantel anterior"
                                        />
                                        <InputError message={errors.institution_origin} />
                                    </div>
                                )}
                            </div>
                        )}
                    </div>

                    <div className="grid gap-6 rounded-xl border border-sidebar-border/70 p-6 md:grid-cols-2 dark:border-sidebar-border">
                        <div className="col-span-full mb-2 border-b pb-2">
                            <h2 className="text-lg font-semibold">Seguridad <span className="text-xs font-normal text-neutral-500 ml-2">(Opcional)</span></h2>
                            <p className="text-xs text-neutral-500">Solo completa si deseas cambiar la contraseña.</p>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="password">Nueva Contraseña</Label>
                            <Input
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                placeholder="Dejar en blanco para mantener"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="password_confirmation">Confirmar Nueva Contraseña</Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                placeholder="Repite la nueva contraseña"
                            />
                            <InputError message={errors.password_confirmation} />
                        </div>
                    </div>

                    <div className="flex justify-end gap-3">
                        <Button variant="outline" asChild disabled={processing}>
                            <Link href={userIndex().url}>Cancelar</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save className="mr-2 h-4 w-4" />
                            {processing ? 'Guardando...' : 'Actualizar Usuario'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
