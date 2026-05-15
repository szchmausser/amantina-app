import { Transition } from '@headlessui/react';
import { Form, Head, Link, usePage } from '@inertiajs/react';
import {
    Calendar,
    Clock,
    FileText,
    Heart,
    MapPin,
    Paperclip,
    Phone,
    Save,
    ShieldCheck,
    User as UserIcon,
    Users,
} from 'lucide-react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
// import DeleteUser from '@/components/delete-user'; // Comentado: bloqueamos eliminación de cuentas por ahora
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { UserAvatar } from '@/components/user-avatar';
import type { BreadcrumbItem } from '@/types';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit(),
    },
];

interface HealthRecord {
    id: number;
    condition: string | null;
    received_at: string | null;
    received_at_location: string | null;
    observations: string | null;
    media: {
        id: number;
        file_name: string;
        url: string;
        description: string;
    }[];
}

interface Representative {
    id: number;
    name: string;
    cedula: string;
    phone: string | null;
    relationship_type_name: string;
}

interface RepresentedStudent {
    id: number;
    name: string;
    cedula: string;
    relationship_type_name: string;
}

interface HourHistoryActivity {
    id: number;
    hours: number;
    activity_category: string | null;
    photos?: { id: number; url: string; name: string }[];
}

interface HourHistoryFieldSession {
    id: number;
    name: string;
    start_datetime: string | null;
    status: string | null;
}

interface HourHistoryItem {
    id: number;
    attended: boolean;
    notes: string | null;
    created_at: string;
    fieldSession: HourHistoryFieldSession | null;
    activities: HourHistoryActivity[];
}

export default function Profile({
    mustVerifyEmail,
    status,
    avatar_url,
    userRoles,
    userPermissions,
    isAlumno,
    isRepresentante,
    showRolesAndPermissions,
    canDeleteAccount,
    userData,
    representatives,
    representedStudents,
    healthRecords,
    hourHistory,
}: {
    mustVerifyEmail: boolean;
    status?: string;
    avatar_url?: string | null;
    userRoles: string[];
    userPermissions: Record<string, string[]>;
    isAlumno: boolean;
    isRepresentante: boolean;
    showRolesAndPermissions: boolean;
    canDeleteAccount: boolean;
    userData: {
        cedula: string;
        phone: string | null;
        address: string | null;
        is_transfer: boolean | null;
        institution_origin: string | null;
        is_active: boolean;
    };
    representatives: Representative[];
    representedStudents: RepresentedStudent[];
    healthRecords: HealthRecord[];
    hourHistory?: HourHistoryItem[];
}) {
    const { auth } = usePage<any>().props;
    const [activeTab, setActiveTab] = useState('profile');

    // Determine which tabs to show
    const tabs = [
        { value: 'profile', label: 'Mi Información' },
        ...(showRolesAndPermissions && !isAlumno && !isRepresentante
            ? [{ value: 'info', label: 'Mis Roles y Permisos' }]
            : []),
        ...(isAlumno
            ? [{ value: 'representantes', label: 'Mis Representantes' }]
            : []),
        ...(isRepresentante
            ? [{ value: 'representados', label: 'Mis Representados' }]
            : []),
        ...(isAlumno ? [{ value: 'salud', label: 'Salud', icon: Heart }] : []),
    ];

    const tabCount = tabs.length;
    const gridCols =
        tabCount === 2
            ? 'grid-cols-2'
            : tabCount === 3
              ? 'grid-cols-3'
              : tabCount === 4
                ? 'grid-cols-2 sm:grid-cols-4'
                : 'grid-cols-2 sm:grid-cols-5';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profile settings" />

            <h1 className="sr-only">Profile settings</h1>

            <SettingsLayout>
                <Tabs
                    value={activeTab}
                    onValueChange={setActiveTab}
                    className="space-y-6"
                >
                    <div className="flex items-center justify-between gap-4">
                        <TabsList className={`w-full ${gridCols} sm:w-auto`}>
                            {tabs.map((tab) => (
                                <TabsTrigger key={tab.value} value={tab.value}>
                                    {'icon' in tab && tab.icon ? (
                                        <>
                                            <tab.icon className="mr-1.5 h-3.5 w-3.5" />
                                            {tab.label}
                                        </>
                                    ) : (
                                        tab.label
                                    )}
                                </TabsTrigger>
                            ))}
                        </TabsList>

                        <Button
                            type="submit"
                            form="profile-form"
                            data-test="update-profile-button"
                        >
                            <Save className="mr-2 h-4 w-4" />
                            Guardar
                        </Button>
                    </div>

                    {/* Tab: Mi Información */}
                    <TabsContent value="profile" className="space-y-6">
                        {/* Personal Info Form + Additional Data merged */}
                        <div className="overflow-hidden rounded-xl border">
                            <div className="flex items-center gap-2 border-b bg-neutral-50 px-6 py-4 dark:bg-neutral-800/50">
                                <UserIcon className="h-4 w-4 text-neutral-500 dark:text-neutral-400" />
                                <h2 className="text-sm font-semibold">
                                    Datos Personales
                                </h2>
                            </div>
                            <div className="p-6">
                                <Form
                                    id="profile-form"
                                    {...ProfileController.update.form()}
                                    options={{ preserveScroll: true }}
                                    className="space-y-6"
                                >
                                    {({
                                        processing,
                                        recentlySuccessful,
                                        errors,
                                    }) => (
                                        <>
                                            <div className="grid gap-6 sm:grid-cols-2">
                                                <div className="space-y-2">
                                                    <Label>Cédula</Label>
                                                    <p className="text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                                        {userData.cedula || '—'}
                                                    </p>
                                                    <input type="hidden" name="cedula" value={userData.cedula || ''} />
                                                </div>

                                                <div className="space-y-2">
                                                    <Label htmlFor="name">
                                                        Nombre Completo
                                                    </Label>
                                                    <Input
                                                        id="name"
                                                        defaultValue={
                                                            auth.user.name
                                                        }
                                                        name="name"
                                                        required
                                                        autoComplete="name"
                                                        placeholder="Nombre completo"
                                                    />
                                                    <InputError
                                                        className="mt-2"
                                                        message={errors.name}
                                                    />
                                                </div>

                                                <div className="space-y-2">
                                                    <Label htmlFor="email">
                                                        Correo Electrónico
                                                    </Label>
                                                    <Input
                                                        id="email"
                                                        type="email"
                                                        defaultValue={
                                                            auth.user.email
                                                        }
                                                        name="email"
                                                        required
                                                        autoComplete="username"
                                                        placeholder="correo@ejemplo.com"
                                                    />
                                                    <InputError
                                                        className="mt-2"
                                                        message={errors.email}
                                                    />
                                                </div>

                                                <div className="space-y-2">
                                                    <Label htmlFor="phone">
                                                        Teléfono
                                                    </Label>
                                                    <Input
                                                        id="phone"
                                                        defaultValue={userData.phone ?? ''}
                                                        name="phone"
                                                        placeholder="0412-0000000"
                                                    />
                                                    <InputError
                                                        className="mt-2"
                                                        message={errors.phone}
                                                    />
                                                </div>

                                                <div className="sm:col-span-2 space-y-2">
                                                    <Label htmlFor="address">
                                                        Dirección
                                                    </Label>
                                                    <Input
                                                        id="address"
                                                        defaultValue={userData.address ?? ''}
                                                        name="address"
                                                        placeholder="Dirección completa"
                                                    />
                                                    <InputError
                                                        className="mt-2"
                                                        message={errors.address}
                                                    />
                                                </div>

                                                {isAlumno && (
                                                    <>
                                                        <div className="space-y-2">
                                                            <Label>Tipo de Ingreso</Label>
                                                            <Badge
                                                                variant="outline"
                                                                className="font-medium"
                                                            >
                                                                {userData.is_transfer
                                                                    ? 'Transferido'
                                                                    : 'Regular'}
                                                            </Badge>
                                                        </div>
                                                        {userData.is_transfer && (
                                                            <div className="space-y-2">
                                                                <Label>
                                                                    Institución de Procedencia
                                                                </Label>
                                                                <p className="text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                                                    {userData.institution_origin ||
                                                                        'No especificada'}
                                                                </p>
                                                            </div>
                                                        )}
                                                    </>
                                                )}
                                            </div>

                                            {mustVerifyEmail &&
                                                auth.user.email_verified_at ===
                                                    null && (
                                                    <div>
                                                        <p className="text-sm text-muted-foreground">
                                                            Your email address
                                                            is unverified.{' '}
                                                            <Link
                                                                href={send()}
                                                                as="button"
                                                                className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                                            >
                                                                Click here to
                                                                resend the
                                                                verification
                                                                email.
                                                            </Link>
                                                        </p>

                                                        {status ===
                                                            'verification-link-sent' && (
                                                            <div className="mt-2 text-sm font-medium text-green-600 dark:text-green-400">
                                                                A new
                                                                verification
                                                                link has been
                                                                sent to your
                                                                email address.
                                                            </div>
                                                        )}
                                                    </div>
                                                )}

                                            <Transition
                                                show={recentlySuccessful}
                                                enter="transition ease-in-out"
                                                enterFrom="opacity-0"
                                                leave="transition ease-in-out"
                                                leaveTo="opacity-0"
                                            >
                                                <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                                    Guardado correctamente
                                                </p>
                                            </Transition>
                                        </>
                                    )}
                                </Form>
                            </div>
                        </div>

                        {/* Avatar — moved to end */}
                        <div className="overflow-hidden rounded-xl border">
                            <div className="flex items-center gap-2 border-b bg-neutral-50 px-6 py-4 dark:bg-neutral-800/50">
                                <UserIcon className="h-4 w-4 text-neutral-500 dark:text-neutral-400" />
                                <h2 className="text-sm font-semibold">
                                    Foto de Perfil
                                </h2>
                            </div>
                            <div className="flex justify-center p-6">
                                <UserAvatar
                                    name={auth.user.name}
                                    avatarUrl={avatar_url ?? undefined}
                                    avatarUpdateUrl="/settings/profile/avatar"
                                    avatarRemoveUrl="/settings/profile/avatar"
                                />
                            </div>
                        </div>

                        {/* {canDeleteAccount && <DeleteUser />} — Comentado: bloqueamos eliminación de cuentas por ahora */}
                    </TabsContent>

                    {/* Tab: Mis Roles y Permisos */}
                    {showRolesAndPermissions && !isAlumno && !isRepresentante && (
                        <TabsContent value="info" className="space-y-6">
                            {/* Roles */}
                            <div className="overflow-hidden rounded-xl border">
                                <div className="flex items-center gap-2 border-b bg-neutral-50 px-6 py-4 dark:bg-neutral-800/50">
                                    <ShieldCheck className="h-4 w-4 text-neutral-500 dark:text-neutral-400" />
                                    <h2 className="text-sm font-semibold">
                                        Mis Roles
                                    </h2>
                                </div>
                                <div className="p-6">
                                    <div className="flex flex-wrap gap-2">
                                        {userRoles.map((role) => (
                                            <Badge
                                                key={role}
                                                variant="secondary"
                                                className="px-3 py-1 text-sm capitalize"
                                            >
                                                {role}
                                            </Badge>
                                        ))}
                                    </div>
                                </div>
                            </div>

                            {/* Permissions */}
                            <div className="overflow-hidden rounded-xl border">
                                <div className="flex items-center gap-2 border-b bg-neutral-50 px-6 py-4 dark:bg-neutral-800/50">
                                    <ShieldCheck className="h-4 w-4 text-neutral-500 dark:text-neutral-400" />
                                    <h2 className="text-sm font-semibold">
                                        Mis Permisos
                                    </h2>
                                </div>
                                <div className="p-6">
                                    {Object.keys(userPermissions).length > 0 ? (
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            {Object.entries(
                                                userPermissions,
                                            ).map(([module, perms]) => (
                                                <div
                                                    key={module}
                                                    className="space-y-2"
                                                >
                                                    <h3 className="text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                                        {module}
                                                    </h3>
                                                    <div className="flex flex-wrap gap-1.5">
                                                        {perms.map((perm) => {
                                                            const action =
                                                                perm.split(
                                                                    '.',
                                                                )[1];
                                                            return (
                                                                <Badge
                                                                    key={perm}
                                                                    variant="outline"
                                                                    className="text-xs capitalize"
                                                                >
                                                                    {action}
                                                                </Badge>
                                                            );
                                                        })}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-sm text-neutral-400 italic dark:text-neutral-500">
                                            Sin permisos asignados.
                                        </p>
                                    )}
                                </div>
                            </div>
                        </TabsContent>
                    )}

                    {/* Tab: Mis Representantes (solo alumnos) */}
                    {isAlumno && (
                        <TabsContent
                            value="representantes"
                            className="space-y-6"
                        >
                            <Card>
                                <CardHeader className="border-b bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                    <div className="flex items-center gap-2">
                                        <Users className="h-4 w-4 text-neutral-500 dark:text-neutral-400" />
                                        <CardTitle className="text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                            Mis Representantes Legales
                                        </CardTitle>
                                    </div>
                                </CardHeader>
                                <CardContent className="p-0">
                                    {representatives.length > 0 ? (
                                        <div className="divide-y">
                                            {representatives.map((rep) => (
                                                <div
                                                    key={rep.id}
                                                    className="flex items-center justify-between p-4 px-6 transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-800/30"
                                                >
                                                    <div className="flex items-center gap-3">
                                                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-neutral-100 dark:bg-neutral-800">
                                                            <UserIcon className="h-5 w-5 text-neutral-500 dark:text-neutral-400" />
                                                        </div>
                                                        <div>
                                                            <p className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                                                {rep.name}
                                                            </p>
                                                            <div className="flex items-center gap-2">
                                                                <Badge
                                                                    variant="secondary"
                                                                    className="h-4 px-1.5 text-[10px] uppercase"
                                                                >
                                                                    {
                                                                        rep.relationship_type_name
                                                                    }
                                                                </Badge>
                                                                <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                                                    {rep.cedula}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    {rep.phone && (
                                                        <div className="flex items-center gap-1 text-neutral-500 dark:text-neutral-400">
                                                            <Phone className="h-3.5 w-3.5" />
                                                            <span className="text-xs">
                                                                {rep.phone}
                                                            </span>
                                                        </div>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="py-12 text-center text-neutral-400 dark:text-neutral-500">
                                            <Users className="mx-auto mb-2 h-10 w-10 opacity-20" />
                                            <p className="text-sm italic">
                                                No tienes representantes
                                                vinculados.
                                            </p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>
                    )}

                    {/* Tab: Mis Representados (solo representantes) */}
                    {isRepresentante && (
                        <TabsContent
                            value="representados"
                            className="space-y-6"
                        >
                            <Card>
                                <CardHeader className="border-b bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                    <div className="flex items-center gap-2">
                                        <Users className="h-4 w-4 text-neutral-500 dark:text-neutral-400" />
                                        <CardTitle className="text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                            Estudiantes a Mi Cargo
                                        </CardTitle>
                                    </div>
                                </CardHeader>
                                <CardContent className="p-0">
                                    {representedStudents.length > 0 ? (
                                        <div className="divide-y">
                                            {representedStudents.map(
                                                (student) => (
                                                    <div
                                                        key={student.id}
                                                        className="flex items-center justify-between p-4 px-6 transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-800/30"
                                                    >
                                                        <div className="flex items-center gap-3">
                                                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-neutral-100 dark:bg-neutral-800">
                                                                <UserIcon className="h-5 w-5 text-neutral-500 dark:text-neutral-400" />
                                                            </div>
                                                            <div>
                                                                <Link
                                                                    href={`/admin/users/${student.id}`}
                                                                    className="text-sm font-semibold text-neutral-900 hover:text-blue-600 hover:underline dark:text-neutral-100 dark:hover:text-blue-400"
                                                                >
                                                                    {
                                                                        student.name
                                                                    }
                                                                </Link>
                                                                <div className="flex items-center gap-2">
                                                                    <Badge
                                                                        variant="secondary"
                                                                        className="h-4 px-1.5 text-[10px] uppercase"
                                                                    >
                                                                        {
                                                                            student.relationship_type_name
                                                                        }
                                                                    </Badge>
                                                                    <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                                                        {
                                                                            student.cedula
                                                                        }
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                ),
                                            )}
                                        </div>
                                    ) : (
                                        <div className="py-12 text-center text-neutral-400 dark:text-neutral-500">
                                            <Users className="mx-auto mb-2 h-10 w-10 opacity-20" />
                                            <p className="text-sm italic">
                                                No tienes estudiantes a cargo
                                                vinculados.
                                            </p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>
                    )}

                    {/* Tab: Salud (solo alumnos) */}
                    {isAlumno && (
                        <TabsContent value="salud" className="space-y-6">
                            <Card>
                                <CardHeader className="border-b bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                    <div className="flex items-center gap-2">
                                        <Heart className="h-4 w-4 text-red-500 dark:text-red-400" />
                                        <CardTitle className="text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                            Mis Registros de Salud
                                        </CardTitle>
                                    </div>
                                </CardHeader>
                                <CardContent className="p-0">
                                    {healthRecords.length > 0 ? (
                                        <div className="divide-y">
                                            {healthRecords.map((record) => (
                                                <div
                                                    key={record.id}
                                                    className="p-4 px-6"
                                                >
                                                    <div className="flex items-start gap-3">
                                                        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-50 dark:bg-red-900/20 dark:bg-red-950/30">
                                                            <Heart className="h-4 w-4 text-red-500 dark:text-red-400" />
                                                        </div>
                                                        <div className="min-w-0 flex-1">
                                                            <p className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                                                {record.condition ||
                                                                    'Sin condición'}
                                                            </p>
                                                            <div className="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-neutral-500 dark:text-neutral-400">
                                                                {record.received_at && (
                                                                    <span className="flex items-center gap-1">
                                                                        <Calendar className="h-3 w-3" />
                                                                        {
                                                                            record.received_at
                                                                        }
                                                                    </span>
                                                                )}
                                                                {record.received_at_location && (
                                                                    <span className="flex items-center gap-1">
                                                                        <MapPin className="h-3 w-3" />
                                                                        {
                                                                            record.received_at_location
                                                                        }
                                                                    </span>
                                                                )}
                                                            </div>
                                                            {record.observations && (
                                                                <p className="mt-1 text-xs text-neutral-400 italic dark:text-neutral-500">
                                                                    {
                                                                        record.observations
                                                                    }
                                                                </p>
                                                            )}
                                                            {record.media
                                                                .length > 0 && (
                                                                <div className="mt-2 flex flex-wrap gap-2">
                                                                    {record.media.map(
                                                                        (m) => (
                                                                            <a
                                                                                key={
                                                                                    m.id
                                                                                }
                                                                                href={
                                                                                    m.url
                                                                                }
                                                                                target="_blank"
                                                                                rel="noopener noreferrer"
                                                                                className="inline-flex items-center gap-1 rounded border bg-neutral-50 px-2 py-1 text-xs text-neutral-600 transition-colors hover:bg-neutral-100 dark:bg-neutral-800 dark:text-neutral-400 dark:hover:bg-neutral-700 dark:hover:bg-neutral-800"
                                                                            >
                                                                                <Paperclip className="h-3 w-3" />
                                                                                {m.description ||
                                                                                    m.file_name}
                                                                                <FileText className="h-3 w-3" />
                                                                            </a>
                                                                        ),
                                                                    )}
                                                                </div>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="py-12 text-center text-neutral-400 dark:text-neutral-500">
                                            <Heart className="mx-auto mb-2 h-10 w-10 opacity-20" />
                                            <p className="text-sm italic">
                                                No tienes registros de salud
                                                registrados.
                                            </p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>
                    )}

                </Tabs>
            </SettingsLayout>
        </AppLayout>
    );
}
