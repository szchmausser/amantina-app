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
    ShieldCheck,
    User as UserIcon,
    Users,
} from 'lucide-react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
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
        ...(showRolesAndPermissions
            ? [{ value: 'info', label: 'Mis Roles y Permisos' }]
            : []),
        ...(isAlumno
            ? [{ value: 'representantes', label: 'Mis Representantes' }]
            : []),
        ...(isRepresentante
            ? [{ value: 'representados', label: 'Mis Representados' }]
            : []),
        ...(isAlumno ? [{ value: 'salud', label: 'Salud', icon: Heart }] : []),
        ...(isAlumno
            ? [{ value: 'horas', label: 'Mis Horas', icon: Clock }]
            : []),
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

                    {/* Tab: Mi Información */}
                    <TabsContent value="profile" className="space-y-6">
                        {/* Avatar */}
                        <Card>
                            <CardHeader className="border-b bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                <div className="flex items-center gap-2">
                                    <UserIcon className="h-4 w-4 text-neutral-500" />
                                    <CardTitle className="text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                        Foto de Perfil
                                    </CardTitle>
                                </div>
                            </CardHeader>
                            <CardContent className="p-6">
                                <div className="flex justify-center">
                                    <UserAvatar
                                        name={auth.user.name}
                                        avatarUrl={avatar_url ?? undefined}
                                        avatarUpdateUrl="/settings/profile/avatar"
                                        avatarRemoveUrl="/settings/profile/avatar"
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        {/* Personal Info Form */}
                        <Card>
                            <CardHeader className="border-b bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                <div className="flex items-center gap-2">
                                    <UserIcon className="h-4 w-4 text-neutral-500" />
                                    <CardTitle className="text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                        Datos Personales
                                    </CardTitle>
                                </div>
                            </CardHeader>
                            <CardContent className="p-6">
                                <Form
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
                                            <div className="grid gap-4 sm:grid-cols-2">
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
                                                        placeholder="Full name"
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
                                                        placeholder="Email address"
                                                    />
                                                    <InputError
                                                        className="mt-2"
                                                        message={errors.email}
                                                    />
                                                </div>
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
                                                            <div className="mt-2 text-sm font-medium text-green-600">
                                                                A new
                                                                verification
                                                                link has been
                                                                sent to your
                                                                email address.
                                                            </div>
                                                        )}
                                                    </div>
                                                )}

                                            <div className="flex items-center gap-4">
                                                <Button
                                                    disabled={processing}
                                                    data-test="update-profile-button"
                                                >
                                                    Guardar
                                                </Button>

                                                <Transition
                                                    show={recentlySuccessful}
                                                    enter="transition ease-in-out"
                                                    enterFrom="opacity-0"
                                                    leave="transition ease-in-out"
                                                    leaveTo="opacity-0"
                                                >
                                                    <p className="text-sm text-neutral-600">
                                                        Guardado correctamente
                                                    </p>
                                                </Transition>
                                            </div>
                                        </>
                                    )}
                                </Form>
                            </CardContent>
                        </Card>

                        {/* Read-only info */}
                        <Card>
                            <CardHeader className="border-b bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                <div className="flex items-center gap-2">
                                    <UserIcon className="h-4 w-4 text-neutral-500" />
                                    <CardTitle className="text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                        Información Adicional
                                    </CardTitle>
                                </div>
                            </CardHeader>
                            <CardContent className="p-6">
                                <div className="grid gap-6 sm:grid-cols-2">
                                    <div className="space-y-1">
                                        <p className="text-[10px] font-bold tracking-wider text-neutral-400 uppercase dark:text-neutral-500">
                                            Cédula
                                        </p>
                                        <p className="text-sm font-medium">
                                            {userData.cedula || '—'}
                                        </p>
                                    </div>
                                    <div className="space-y-1">
                                        <p className="text-[10px] font-bold tracking-wider text-neutral-400 uppercase dark:text-neutral-500">
                                            Teléfono
                                        </p>
                                        <p className="text-sm font-medium">
                                            {userData.phone || '—'}
                                        </p>
                                    </div>
                                    <div className="space-y-1 sm:col-span-2">
                                        <p className="text-[10px] font-bold tracking-wider text-neutral-400 uppercase dark:text-neutral-500">
                                            Dirección
                                        </p>
                                        <p className="text-sm font-medium">
                                            {userData.address || '—'}
                                        </p>
                                    </div>
                                    {isAlumno && (
                                        <>
                                            <div className="space-y-1">
                                                <p className="text-[10px] font-bold tracking-wider text-neutral-400 uppercase dark:text-neutral-500">
                                                    Tipo de Ingreso
                                                </p>
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
                                                <div className="space-y-1">
                                                    <p className="text-[10px] font-bold tracking-wider text-neutral-400 uppercase dark:text-neutral-500">
                                                        Institución de
                                                        Procedencia
                                                    </p>
                                                    <p className="text-sm font-medium">
                                                        {userData.institution_origin ||
                                                            'No especificada'}
                                                    </p>
                                                </div>
                                            )}
                                        </>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {canDeleteAccount && <DeleteUser />}
                    </TabsContent>

                    {/* Tab: Mis Roles y Permisos */}
                    {showRolesAndPermissions && (
                        <TabsContent value="info" className="space-y-6">
                            {/* Roles */}
                            <Card>
                                <CardHeader className="border-b bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                    <div className="flex items-center gap-2">
                                        <ShieldCheck className="h-4 w-4 text-neutral-500" />
                                        <CardTitle className="text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                            Mis Roles
                                        </CardTitle>
                                    </div>
                                </CardHeader>
                                <CardContent className="p-6">
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
                                </CardContent>
                            </Card>

                            {/* Permissions */}
                            <Card>
                                <CardHeader className="border-b bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                    <div className="flex items-center gap-2">
                                        <ShieldCheck className="h-4 w-4 text-neutral-500" />
                                        <CardTitle className="text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                            Mis Permisos
                                        </CardTitle>
                                    </div>
                                </CardHeader>
                                <CardContent className="p-6">
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
                                        <p className="text-sm text-neutral-400 italic">
                                            Sin permisos asignados.
                                        </p>
                                    )}
                                </CardContent>
                            </Card>
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
                                        <Users className="h-4 w-4 text-neutral-500" />
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
                                                            <UserIcon className="h-5 w-5 text-neutral-500" />
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
                                                                <span className="text-xs text-neutral-500">
                                                                    {rep.cedula}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    {rep.phone && (
                                                        <div className="flex items-center gap-1 text-neutral-500">
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
                                        <div className="py-12 text-center text-neutral-400">
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
                                        <Users className="h-4 w-4 text-neutral-500" />
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
                                                                <UserIcon className="h-5 w-5 text-neutral-500" />
                                                            </div>
                                                            <div>
                                                                <Link
                                                                    href={`/admin/users/${student.id}`}
                                                                    className="text-sm font-semibold text-neutral-900 hover:text-blue-600 hover:underline dark:text-neutral-100"
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
                                                                    <span className="text-xs text-neutral-500">
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
                                        <div className="py-12 text-center text-neutral-400">
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
                                        <Heart className="h-4 w-4 text-red-500" />
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
                                                        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-50 dark:bg-red-900/20">
                                                            <Heart className="h-4 w-4 text-red-500" />
                                                        </div>
                                                        <div className="min-w-0 flex-1">
                                                            <p className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                                                {record.condition ||
                                                                    'Sin condición'}
                                                            </p>
                                                            <div className="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-neutral-500">
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
                                                                <p className="mt-1 text-xs text-neutral-400 italic">
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
                                                                                className="inline-flex items-center gap-1 rounded border bg-neutral-50 px-2 py-1 text-xs text-neutral-600 transition-colors hover:bg-neutral-100 dark:bg-neutral-800 dark:text-neutral-400 dark:hover:bg-neutral-700"
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
                                        <div className="py-12 text-center text-neutral-400">
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

                    {/* Tab: Mis Horas (solo alumnos) */}
                    {isAlumno && hourHistory && (
                        <TabsContent value="horas" className="space-y-6">
                            <Card>
                                <CardHeader className="border-b bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                    <div className="flex items-center gap-2">
                                        <Clock className="h-4 w-4 text-green-600" />
                                        <CardTitle className="text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                            Historial de Horas Socioproductivas
                                        </CardTitle>
                                    </div>
                                </CardHeader>
                                <CardContent className="p-0">
                                    {hourHistory.length > 0 ? (
                                        <div className="divide-y">
                                            {hourHistory.map((item) => (
                                                <div
                                                    key={item.id}
                                                    className="p-4 px-6"
                                                >
                                                    <div className="flex items-start justify-between">
                                                        <div className="flex items-start gap-3">
                                                            <div
                                                                className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-full ${
                                                                    item.attended
                                                                        ? 'bg-green-50 dark:bg-green-900/20'
                                                                        : 'bg-red-50 dark:bg-red-900/20'
                                                                }`}
                                                            >
                                                                {item.attended ? (
                                                                    <Clock className="h-4 w-4 text-green-600" />
                                                                ) : (
                                                                    <UserIcon className="h-4 w-4 text-red-600" />
                                                                )}
                                                            </div>
                                                            <div className="min-w-0 flex-1">
                                                                <p className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                                                    {item
                                                                        .fieldSession
                                                                        ?.name ||
                                                                        'Jornada'}
                                                                </p>
                                                                <div className="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-neutral-500">
                                                                    {item
                                                                        .fieldSession
                                                                        ?.start_datetime && (
                                                                        <span className="flex items-center gap-1">
                                                                            <Calendar className="h-3 w-3" />
                                                                            {
                                                                                item
                                                                                    .fieldSession
                                                                                    .start_datetime
                                                                            }
                                                                        </span>
                                                                    )}
                                                                    <span
                                                                        className={`flex items-center gap-1 ${
                                                                            item.attended
                                                                                ? 'text-green-600'
                                                                                : 'text-red-600'
                                                                        }`}
                                                                    >
                                                                        {item.attended
                                                                            ? 'Asistió'
                                                                            : 'Ausente'}
                                                                    </span>
                                                                </div>
                                                                {item.activities &&
                                                                    item
                                                                        .activities
                                                                        .length >
                                                                        0 && (
                                                                        <div className="mt-2 flex flex-wrap gap-2">
                                                                            {item.activities.map(
                                                                                (
                                                                                    activity,
                                                                                ) => (
                                                                                    <Badge
                                                                                        key={
                                                                                            activity.id
                                                                                        }
                                                                                        className="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400"
                                                                                    >
                                                                                        {
                                                                                            activity.hours
                                                                                        }
                                                                                        h{' '}
                                                                                        {activity.activity_category ||
                                                                                            ''}
                                                                                    </Badge>
                                                                                ),
                                                                            )}
                                                                        </div>
                                                                    )}
                                                                {item.notes && (
                                                                    <p className="mt-1 text-xs text-neutral-400 italic">
                                                                        {
                                                                            item.notes
                                                                        }
                                                                    </p>
                                                                )}
                                                            </div>
                                                        </div>
                                                        <Badge
                                                            variant="outline"
                                                            className="text-xs"
                                                        >
                                                            {item.created_at}
                                                        </Badge>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="py-12 text-center text-neutral-400">
                                            <Clock className="mx-auto mb-2 h-10 w-10 opacity-20" />
                                            <p className="text-sm italic">
                                                No tienes horas registradas aún.
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
