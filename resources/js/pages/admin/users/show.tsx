import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Clock,
    Edit,
    ShieldCheck,
    User as UserIcon,
    BookOpen,
    Users,
    Phone,
    Trash2,
    UserPlus,
    Heart,
    FileText,
    Download,
    Plus,
    Calendar,
    MapPin,
    Paperclip,
} from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useInitials } from '@/hooks/use-initials';
import AppLayout from '@/layouts/app-layout';
import {
    index as userIndex,
    edit as userEdit,
    show as userShowDetail,
} from '@/routes/admin/users';
import { destroy as unlinkRepresentative } from '@/routes/admin/student-representatives';
import { store as linkRepresentative } from '@/routes/admin/student-representatives';
import type { BreadcrumbItem, User } from '@/types';
import { useState } from 'react';
import AssignRepresentativeModal from './partials/assign-representative-modal';
import HealthRecordModal from './partials/health-record-modal';
import { router } from '@inertiajs/react';

interface CurrentEnrollment {
    id: number;
    academic_year: {
        name: string;
    };
    grade: {
        name: string;
    };
    section: {
        name: string;
    };
}

interface Props {
    user: User & {
        roles: any[];
        permissions: any[];
        representatives: any[];
        represented_students: any[];
        health_records: any[];
        avatar_url?: string | null;
    };
    currentEnrollment?: CurrentEnrollment | null;
    relationshipTypes: any[];
    availableRepresentatives: any[];
    healthConditions: any[];
    hourHistory?: HourHistoryItem[];
    hourStats?: {
        current_year: {
            hours: number;
            required: number;
            percentage: number;
            year_name: string;
        };
        total: {
            hours: number;
            required: number;
            percentage: number;
        };
    } | null;
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
    academic_year_id: number;
}

interface HourHistoryItem {
    id: number;
    attended: boolean;
    notes: string | null;
    created_at: string;
    total_hours: number;
    fieldSession: HourHistoryFieldSession | null;
    activities: HourHistoryActivity[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Usuarios', href: '/admin/users' },
    { title: 'Detalles', href: '#' },
];

export default function Show({
    user,
    currentEnrollment,
    relationshipTypes,
    availableRepresentatives,
    healthConditions,
    hourHistory,
    hourStats,
}: Props) {
    const { auth } = usePage<any>().props;
    const [isAssignModalOpen, setIsAssignModalOpen] = useState(false);
    const [isHealthModalOpen, setIsHealthModalOpen] = useState(false);
    const [editingHealthRecord, setEditingHealthRecord] = useState<any>(null);
    const [activeTab, setActiveTab] = useState('general');
    const getInitials = useInitials();

    const hasPermission = (p: string) => auth.permissions?.includes(p);

    const roles = user.roles ? user.roles.map((r: any) => r.name) : [];
    const directPermissions = user.permissions
        ? user.permissions.map((p: any) => p.name)
        : [];

    // Collect all permissions from all roles
    const rolePermissions = new Set<string>();
    user.roles?.forEach((r: any) => {
        r.permissions?.forEach((p: any) => rolePermissions.add(p.name));
    });

    // Group all unique permissions by module
    const groupedPermissions: Record<string, string[]> = {};
    const allUserPermissions = Array.from(
        new Set([...Array.from(rolePermissions), ...directPermissions]),
    );

    allUserPermissions.sort().forEach((p) => {
        const module = p.split('.')[0];
        if (!groupedPermissions[module]) {
            groupedPermissions[module] = [];
        }
        groupedPermissions[module].push(p);
    });

    const isAlumno = roles.includes('alumno');
    const isRepresentante = roles.includes('representante');

    const handleUnlink = (pivotId: number) => {
        if (
            confirm(
                '¿Estás seguro de que deseas desvincular a este representante?',
            )
        ) {
            router.delete(unlinkRepresentative(pivotId).url);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Usuario: ${user.name}`} />

            <div className="p-4 lg:p-8">
                {/* User Header */}
                <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
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
                        <div className="flex items-center gap-3">
                            {(user as any).avatar_url ? (
                                <Avatar className="h-12 w-12">
                                    <AvatarImage
                                        src={(user as any).avatar_url}
                                        alt={user.name}
                                    />
                                    <AvatarFallback>
                                        {getInitials(user.name)}
                                    </AvatarFallback>
                                </Avatar>
                            ) : (
                                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 dark:bg-neutral-800">
                                    <UserIcon className="h-6 w-6 text-neutral-500" />
                                </div>
                            )}
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                    {user.name}
                                </h1>
                                <div className="flex flex-wrap items-center gap-2">
                                    {roles.map((role) => (
                                        <Badge
                                            key={role}
                                            variant="secondary"
                                            className="capitalize"
                                        >
                                            {role}
                                        </Badge>
                                    ))}
                                    {user.is_active ? (
                                        <Badge className="bg-green-500/10 text-green-500 hover:bg-green-500/20">
                                            Activo
                                        </Badge>
                                    ) : (
                                        <Badge variant="destructive">
                                            Inactivo
                                        </Badge>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                    {(hasPermission('users.edit') ||
                        (auth.user && auth.user.id === user.id)) && (
                        <Button asChild>
                            <Link href={userEdit(user.id).url}>
                                <Edit className="mr-2 h-4 w-4" />
                                Editar Perfil
                            </Link>
                        </Button>
                    )}
                </div>

                {/* Tabs */}
                <Tabs
                    value={activeTab}
                    onValueChange={setActiveTab}
                    className="space-y-6"
                >
                    <TabsList className="grid w-full grid-cols-2 sm:w-auto sm:grid-cols-4">
                        <TabsTrigger value="general">General</TabsTrigger>
                        {isAlumno && (
                            <TabsTrigger value="salud">
                                <Heart className="mr-1.5 h-3.5 w-3.5" />
                                Salud
                            </TabsTrigger>
                        )}
                        {isAlumno && (
                            <TabsTrigger value="horas">
                                <Clock className="mr-1.5 h-3.5 w-3.5" />
                                Horas
                            </TabsTrigger>
                        )}
                        <TabsTrigger value="permisos">Permisos</TabsTrigger>
                    </TabsList>

                    {/* Tab: General */}
                    <TabsContent value="general" className="space-y-6">
                        {/* Información Personal */}
                        <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                            <div className="flex items-center gap-2 bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                <UserIcon className="h-4 w-4 text-neutral-500" />
                                <h2 className="text-sm font-semibold tracking-wide text-neutral-600 uppercase dark:text-neutral-300">
                                    Información Personal
                                </h2>
                            </div>
                            <div className="grid gap-6 p-6 md:grid-cols-2">
                                <div className="space-y-1">
                                    <p className="text-[10px] font-bold tracking-wider text-neutral-400 uppercase dark:text-neutral-500">
                                        Cédula
                                    </p>
                                    <p className="text-sm font-medium">
                                        {user.cedula || '—'}
                                    </p>
                                </div>
                                <div className="space-y-1">
                                    <p className="text-[10px] font-bold tracking-wider text-neutral-400 uppercase dark:text-neutral-500">
                                        Correo Electrónico
                                    </p>
                                    <p className="text-sm font-medium">
                                        {user.email}
                                    </p>
                                </div>
                                <div className="space-y-1">
                                    <p className="text-[10px] font-bold tracking-wider text-neutral-400 uppercase dark:text-neutral-500">
                                        Teléfono
                                    </p>
                                    <p className="text-sm font-medium">
                                        {user.phone || '—'}
                                    </p>
                                </div>
                                <div className="space-y-1">
                                    <p className="text-[10px] font-bold tracking-wider text-neutral-400 uppercase dark:text-neutral-500">
                                        Dirección
                                    </p>
                                    <p className="text-sm font-medium">
                                        {user.address || '—'}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Información Académica (Solo si es alumno) */}
                        {isAlumno && (
                            <>
                                <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                                    <div className="flex items-center gap-2 bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                        <BookOpen className="h-4 w-4 text-neutral-500" />
                                        <h2 className="text-sm font-semibold tracking-wide text-neutral-600 uppercase dark:text-neutral-300">
                                            Información Académica
                                        </h2>
                                    </div>
                                    <div className="grid gap-6 p-6 md:grid-cols-2">
                                        <div className="space-y-1">
                                            <p className="text-[10px] font-bold tracking-wider text-neutral-400 uppercase dark:text-neutral-500">
                                                Tipo de Ingreso
                                            </p>
                                            <Badge
                                                variant="outline"
                                                className="font-medium"
                                            >
                                                {user.is_transfer
                                                    ? 'Transferido'
                                                    : 'Regular'}
                                            </Badge>
                                        </div>
                                        {user.is_transfer && (
                                            <div className="space-y-1">
                                                <p className="text-[10px] font-bold tracking-wider text-neutral-400 uppercase dark:text-neutral-500">
                                                    Institución de Procedencia
                                                </p>
                                                <p className="text-sm font-medium">
                                                    {user.institution_origin ||
                                                        'No especificada'}
                                                </p>
                                            </div>
                                        )}
                                        {currentEnrollment ? (
                                            <div className="space-y-1">
                                                <p className="text-[10px] font-bold tracking-wider text-neutral-400 uppercase dark:text-neutral-500">
                                                    Grado y Sección
                                                </p>
                                                <div className="flex items-center gap-2">
                                                    <Badge
                                                        variant="outline"
                                                        className="font-medium"
                                                    >
                                                        {currentEnrollment.grade
                                                            ?.name || '—'}
                                                    </Badge>
                                                    <span className="text-sm font-medium">
                                                        Sección{' '}
                                                        {currentEnrollment
                                                            .section?.name || '—'}
                                                    </span>
                                                </div>
                                                <p className="text-xs text-neutral-500">
                                                    Año Escolar:{' '}
                                                    {currentEnrollment
                                                        .academic_year?.name ||
                                                        '—'}
                                                </p>
                                            </div>
                                        ) : (
                                            <div className="col-span-full rounded-lg bg-neutral-50 p-3 dark:bg-neutral-800/50">
                                                <p className="text-xs text-neutral-500 italic">
                                                    * El grado y sección se
                                                    gestionan en el módulo de
                                                    Inscripciones.
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Información Familiar (Representantes) */}
                                <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                                    <div className="flex items-center justify-between bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                        <div className="flex items-center gap-2">
                                            <Users className="h-4 w-4 text-neutral-500" />
                                            <h2 className="text-sm font-semibold tracking-wide text-neutral-600 uppercase dark:text-neutral-300">
                                                Representantes Legales
                                            </h2>
                                        </div>
                                        {hasPermission('users.edit') && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="h-7 text-[10px] font-bold uppercase"
                                                onClick={() =>
                                                    setIsAssignModalOpen(true)
                                                }
                                            >
                                                <UserPlus className="mr-1.5 h-3 w-3" />
                                                Asignar
                                            </Button>
                                        )}
                                    </div>
                                    <div className="p-0">
                                        {user.representatives &&
                                        user.representatives.length > 0 ? (
                                            <div className="divide-y divide-sidebar-border/70">
                                                {user.representatives.map(
                                                    (rep) => (
                                                        <div
                                                            key={rep.id}
                                                            className="flex items-center justify-between p-4 px-6 transition-colors hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30"
                                                        >
                                                            <div className="flex items-center gap-3">
                                                                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-neutral-100 dark:bg-neutral-800">
                                                                    <UserIcon className="h-4 w-4 text-neutral-500" />
                                                                </div>
                                                                <div>
                                                                    <p className="text-sm font-semibold">
                                                                        {
                                                                            rep.name
                                                                        }
                                                                    </p>
                                                                    <div className="flex items-center gap-2">
                                                                        <Badge
                                                                            variant="secondary"
                                                                            className="h-4 px-1.5 text-[9px] uppercase"
                                                                        >
                                                                            {(
                                                                                rep as any
                                                                            )
                                                                                .pivot
                                                                                ?.relationship_type_name ||
                                                                                'Vínculo'}
                                                                        </Badge>
                                                                        <span className="text-[11px] text-neutral-500">
                                                                            {
                                                                                rep.cedula
                                                                            }
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div className="flex items-center gap-4">
                                                                {rep.phone && (
                                                                    <div className="hidden items-center gap-1 text-neutral-500 sm:flex">
                                                                        <Phone className="h-3 w-3" />
                                                                        <span className="text-xs">
                                                                            {
                                                                                rep.phone
                                                                            }
                                                                        </span>
                                                                    </div>
                                                                )}
                                                                {hasPermission(
                                                                    'users.edit',
                                                                ) && (
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        className="h-8 w-8 text-destructive hover:bg-destructive/10 hover:text-destructive"
                                                                        onClick={() =>
                                                                            handleUnlink(
                                                                                rep
                                                                                    .pivot
                                                                                    .id,
                                                                            )
                                                                        }
                                                                    >
                                                                        <Trash2 className="h-4 w-4" />
                                                                    </Button>
                                                                )}
                                                            </div>
                                                        </div>
                                                    ),
                                                )}
                                            </div>
                                        ) : (
                                            <div className="py-8 text-center text-neutral-400">
                                                <p className="text-sm italic">
                                                    No tiene representantes
                                                    vinculados.
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </>
                        )}

                        {/* Información de Representados (Solo si es representante) */}
                        {isRepresentante && (
                            <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                                <div className="flex items-center gap-2 bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                    <Users className="h-4 w-4 text-neutral-500" />
                                    <h2 className="text-sm font-semibold tracking-wide text-neutral-600 uppercase dark:text-neutral-300">
                                        Estudiantes a Cargo
                                    </h2>
                                </div>
                                <div className="p-0">
                                    {user.represented_students &&
                                    user.represented_students.length > 0 ? (
                                        <div className="divide-y divide-sidebar-border/70">
                                            {user.represented_students.map(
                                                (student) => (
                                                    <div
                                                        key={student.id}
                                                        className="flex items-center justify-between p-4 px-6 transition-colors hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30"
                                                    >
                                                        <div className="flex items-center gap-3">
                                                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-neutral-100 dark:bg-neutral-800">
                                                                <UserIcon className="h-4 w-4 text-neutral-500" />
                                                            </div>
                                                            <div>
                                                                <Link
                                                                    href={
                                                                        userShowDetail(
                                                                            student.id,
                                                                        ).url
                                                                    }
                                                                    className="text-sm font-semibold decoration-neutral-300 hover:underline"
                                                                >
                                                                    {
                                                                        student.name
                                                                    }
                                                                </Link>
                                                                <div className="flex items-center gap-2">
                                                                    <Badge
                                                                        variant="secondary"
                                                                        className="h-4 px-1.5 text-[9px] uppercase"
                                                                    >
                                                                        {student
                                                                            .pivot
                                                                            ?.relationship_type
                                                                            ?.name ||
                                                                            'Vínculo'}
                                                                    </Badge>
                                                                    <span className="text-[11px] text-neutral-500">
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
                                        <div className="py-8 text-center text-neutral-400">
                                            <p className="text-sm italic">
                                                No tiene estudiantes a cargo
                                                vinculados.
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Matriz de Permisos */}
                        <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                            <div className="flex items-center justify-between bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                <div className="flex items-center gap-2">
                                    <ShieldCheck className="h-4 w-4 text-neutral-500" />
                                    <h2 className="text-sm font-semibold tracking-wide text-neutral-600 uppercase dark:text-neutral-300">
                                        Capacidades y Permisos
                                    </h2>
                                </div>
                                <div className="flex gap-3">
                                    <div className="flex items-center gap-1.5">
                                        <div className="h-2 w-2 rounded-full border border-neutral-300 bg-white dark:border-neutral-600 dark:bg-neutral-900"></div>
                                        <span className="text-[10px] font-bold tracking-tighter text-neutral-400 uppercase">
                                            Heredado
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-1.5">
                                        <div className="h-2 w-2 rounded-full bg-blue-500"></div>
                                        <span className="text-[10px] font-bold tracking-tighter text-neutral-400 uppercase">
                                            Directo
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div className="divide-y divide-sidebar-border/70 p-6">
                                {Object.keys(groupedPermissions).length > 0 ? (
                                    <div className="grid gap-6 sm:grid-cols-2">
                                        {Object.entries(groupedPermissions).map(
                                            ([module, perms]) => (
                                                <div
                                                    key={module}
                                                    className="space-y-2"
                                                >
                                                    <h3 className="text-[10px] font-black tracking-widest text-neutral-400 uppercase dark:text-neutral-500">
                                                        {module}
                                                    </h3>
                                                    <div className="flex flex-wrap gap-1.5">
                                                        {perms.map((perm) => {
                                                            const action =
                                                                perm.split(
                                                                    '.',
                                                                )[1];
                                                            const isDirect =
                                                                directPermissions.includes(
                                                                    perm,
                                                                );
                                                            const isInherited =
                                                                rolePermissions.has(
                                                                    perm,
                                                                );

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
                                                                    {isDirect &&
                                                                        isInherited && (
                                                                            <span className="ml-1 text-[8px] opacity-60">
                                                                                (ambos)
                                                                            </span>
                                                                        )}
                                                                </Badge>
                                                            );
                                                        })}
                                                    </div>
                                                </div>
                                            ),
                                        )}
                                    </div>
                                ) : (
                                    <div className="flex flex-col items-center justify-center py-8 text-center text-neutral-400">
                                        <ShieldCheck className="mb-2 h-10 w-10 opacity-20" />
                                        <p className="text-sm italic">
                                            Sin permisos asignados.
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </TabsContent>

                    {/* Tab: Salud (solo para alumnos) */}
                    {isAlumno && (
                        <TabsContent value="salud" className="space-y-6">
                            {/* Health Records Section */}
                            <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                                <div className="flex items-center justify-between bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                    <div className="flex items-center gap-2">
                                        <Heart className="h-4 w-4 text-red-500" />
                                        <h2 className="text-sm font-semibold tracking-wide text-neutral-600 uppercase dark:text-neutral-300">
                                            Registros de Salud
                                        </h2>
                                    </div>
                                    {hasPermission('student_health.create') && (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            className="h-7 text-[10px] font-bold uppercase"
                                            onClick={() => {
                                                setEditingHealthRecord(null);
                                                setIsHealthModalOpen(true);
                                            }}
                                        >
                                            <Plus className="mr-1.5 h-3 w-3" />
                                            Agregar Registro
                                        </Button>
                                    )}
                                </div>
                                <div className="p-0">
                                    {user.health_records &&
                                    user.health_records.length > 0 ? (
                                        <div className="divide-y divide-sidebar-border/70">
                                            {user.health_records.map(
                                                (record: any) => (
                                                    <div
                                                        key={record.id}
                                                        className="p-4 px-6 transition-colors hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30"
                                                    >
                                                        <div className="flex items-start justify-between">
                                                            <div className="flex items-start gap-3">
                                                                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-red-50 dark:bg-red-900/20">
                                                                    <Heart className="h-4 w-4 text-red-500" />
                                                                </div>
                                                                <div>
                                                                    <p className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                                                        {record
                                                                            .condition
                                                                            ?.name ||
                                                                            'Sin condición'}
                                                                    </p>
                                                                    <div className="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-neutral-500">
                                                                        <span className="flex items-center gap-1">
                                                                            <Calendar className="h-3 w-3" />
                                                                            {new Date(
                                                                                record.received_at,
                                                                            ).toLocaleDateString(
                                                                                'es-ES',
                                                                            )}
                                                                        </span>
                                                                        {record.received_at_location && (
                                                                            <span className="flex items-center gap-1">
                                                                                <MapPin className="h-3 w-3" />
                                                                                {
                                                                                    record.received_at_location
                                                                                }
                                                                            </span>
                                                                        )}
                                                                        <span>
                                                                            Recibido
                                                                            por:{' '}
                                                                            {record
                                                                                .received_by
                                                                                ?.name ||
                                                                                '—'}
                                                                        </span>
                                                                    </div>
                                                                    {record.observations && (
                                                                        <p className="mt-1 text-xs text-neutral-400 italic">
                                                                            {
                                                                                record.observations
                                                                            }
                                                                        </p>
                                                                    )}
                                                                    {record.media &&
                                                                        record
                                                                            .media
                                                                            .length >
                                                                            0 && (
                                                                            <div className="mt-2 flex flex-wrap gap-2">
                                                                                {record.media.map(
                                                                                    (
                                                                                        m: any,
                                                                                    ) => (
                                                                                        <a
                                                                                            key={
                                                                                                m.id
                                                                                            }
                                                                                            href={
                                                                                                m.original_url ||
                                                                                                m.url
                                                                                            }
                                                                                            target="_blank"
                                                                                            rel="noopener noreferrer"
                                                                                            className="inline-flex items-center gap-1 rounded border bg-neutral-50 px-2 py-1 text-xs text-neutral-600 transition-colors hover:bg-neutral-100 dark:bg-neutral-800 dark:text-neutral-400 dark:hover:bg-neutral-700"
                                                                                        >
                                                                                            <Paperclip className="h-3 w-3" />
                                                                                            {m
                                                                                                .custom_properties
                                                                                                ?.description ||
                                                                                                m.file_name}
                                                                                            <Download className="h-3 w-3" />
                                                                                        </a>
                                                                                    ),
                                                                                )}
                                                                            </div>
                                                                        )}
                                                                </div>
                                                            </div>
                                                            <div className="flex gap-1">
                                                                {hasPermission(
                                                                    'student_health.edit',
                                                                ) && (
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        className="h-7 w-7 text-neutral-500 hover:text-blue-600"
                                                                    >
                                                                        <Edit className="h-3.5 w-3.5" />
                                                                    </Button>
                                                                )}
                                                                {hasPermission(
                                                                    'student_health.delete',
                                                                ) && (
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        className="h-7 w-7 text-red-500 hover:bg-red-50 hover:text-red-600"
                                                                        onClick={() => {
                                                                            if (
                                                                                confirm(
                                                                                    '¿Eliminar este registro de salud?\n\nLos archivos adjuntos se eliminarán permanentemente del servidor y no podrán recuperarse.',
                                                                                )
                                                                            ) {
                                                                                router.delete(
                                                                                    `/admin/student-health-records/${record.id}`,
                                                                                );
                                                                            }
                                                                        }}
                                                                    >
                                                                        <Trash2 className="h-3.5 w-3.5" />
                                                                    </Button>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                ),
                                            )}
                                        </div>
                                    ) : (
                                        <div className="py-12 text-center text-neutral-400">
                                            <Heart className="mx-auto mb-2 h-10 w-10 opacity-20" />
                                            <p className="text-sm italic">
                                                No hay registros de salud para
                                                este estudiante.
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </TabsContent>
                    )}

                    {/* Tab: Horas (solo para alumnos) */}
                    {isAlumno && hourHistory && (
                        <TabsContent value="horas" className="space-y-6">
                            {/* Estadísticas de Horas */}
                            {hourStats && (
                                <div className="grid gap-4 md:grid-cols-2">
                                    {/* Año Actual */}
                                    <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                                        <div className="bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                            <h3 className="text-sm font-semibold text-neutral-600 uppercase dark:text-neutral-300">
                                                {hourStats.current_year.year_name}
                                            </h3>
                                        </div>
                                        <div className="p-6">
                                            <div className="mb-4 flex items-end justify-between">
                                                <div>
                                                    <p className="text-3xl font-bold text-neutral-900 dark:text-neutral-100">
                                                        {hourStats.current_year.hours.toFixed(1)}h
                                                    </p>
                                                    <p className="text-sm text-neutral-500">
                                                        de {hourStats.current_year.required}h requeridas
                                                    </p>
                                                </div>
                                                <div className="text-right">
                                                    <p className={`text-2xl font-bold ${
                                                        hourStats.current_year.percentage >= 100
                                                            ? 'text-green-600'
                                                            : hourStats.current_year.percentage >= 75
                                                            ? 'text-blue-600'
                                                            : hourStats.current_year.percentage >= 50
                                                            ? 'text-amber-600'
                                                            : 'text-red-600'
                                                    }`}>
                                                        {hourStats.current_year.percentage.toFixed(0)}%
                                                    </p>
                                                </div>
                                            </div>
                                            {/* Barra de progreso */}
                                            <div className="relative h-3 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                                                <div
                                                    className={`h-full transition-all duration-500 ${
                                                        hourStats.current_year.percentage >= 100
                                                            ? 'bg-green-500'
                                                            : hourStats.current_year.percentage >= 75
                                                            ? 'bg-blue-500'
                                                            : hourStats.current_year.percentage >= 50
                                                            ? 'bg-amber-500'
                                                            : 'bg-red-500'
                                                    }`}
                                                    style={{
                                                        width: `${Math.min(hourStats.current_year.percentage, 100)}%`,
                                                    }}
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    {/* Acumulado Total */}
                                    <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                                        <div className="bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                            <h3 className="text-sm font-semibold text-neutral-600 uppercase dark:text-neutral-300">
                                                Acumulado General
                                            </h3>
                                        </div>
                                        <div className="p-6">
                                            <div className="mb-4 flex items-end justify-between">
                                                <div>
                                                    <p className="text-3xl font-bold text-neutral-900 dark:text-neutral-100">
                                                        {hourStats.total.hours.toFixed(1)}h
                                                    </p>
                                                    <p className="text-sm text-neutral-500">
                                                        de {hourStats.total.required}h totales
                                                    </p>
                                                </div>
                                                <div className="text-right">
                                                    <p className={`text-2xl font-bold ${
                                                        hourStats.total.percentage >= 100
                                                            ? 'text-green-600'
                                                            : hourStats.total.percentage >= 75
                                                            ? 'text-blue-600'
                                                            : hourStats.total.percentage >= 50
                                                            ? 'text-amber-600'
                                                            : 'text-red-600'
                                                    }`}>
                                                        {hourStats.total.percentage.toFixed(0)}%
                                                    </p>
                                                </div>
                                            </div>
                                            {/* Barra de progreso */}
                                            <div className="relative h-3 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                                                <div
                                                    className={`h-full transition-all duration-500 ${
                                                        hourStats.total.percentage >= 100
                                                            ? 'bg-green-500'
                                                            : hourStats.total.percentage >= 75
                                                            ? 'bg-blue-500'
                                                            : hourStats.total.percentage >= 50
                                                            ? 'bg-amber-500'
                                                            : 'bg-red-500'
                                                    }`}
                                                    style={{
                                                        width: `${Math.min(hourStats.total.percentage, 100)}%`,
                                                    }}
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Historial de Horas */}
                            <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                                <div className="flex items-center gap-2 bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                    <Clock className="h-4 w-4 text-green-600" />
                                    <h2 className="text-sm font-semibold tracking-wide text-neutral-600 uppercase dark:text-neutral-300">
                                        Historial de Horas Socioproductivas
                                    </h2>
                                </div>
                                <div className="p-0">
                                    {hourHistory.length > 0 ? (
                                        <div className="divide-y divide-sidebar-border/70">
                                            {hourHistory.map((item) => (
                                                <div
                                                    key={item.id}
                                                    className="p-4 px-6"
                                                >
                                                    <div className="flex items-start justify-between">
                                                        <div className="flex items-start gap-3 flex-1">
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
                                                        <div className="flex flex-col items-end gap-2 ml-4">
                                                            <Badge
                                                                variant="outline"
                                                                className="text-xs"
                                                            >
                                                                {item.created_at}
                                                            </Badge>
                                                            {/* Resultado de la jornada */}
                                                            <Badge
                                                                className={`text-sm font-bold ${
                                                                    item.total_hours > 0
                                                                        ? 'bg-green-500 text-white hover:bg-green-600'
                                                                        : item.total_hours < 0
                                                                        ? 'bg-red-500 text-white hover:bg-red-600'
                                                                        : 'bg-neutral-300 text-neutral-700 hover:bg-neutral-400 dark:bg-neutral-700 dark:text-neutral-300'
                                                                }`}
                                                            >
                                                                {item.total_hours > 0 && '+'}
                                                                {item.total_hours}h
                                                            </Badge>
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="py-12 text-center text-neutral-400">
                                            <Clock className="mx-auto mb-2 h-10 w-10 opacity-20" />
                                            <p className="text-sm italic">
                                                Este estudiante no tiene horas
                                                registradas aún.
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </TabsContent>
                    )}

                    {/* Tab: Permisos */}
                    <TabsContent value="permisos" className="space-y-6">
                        <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                            <div className="flex items-center justify-between bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                <div className="flex items-center gap-2">
                                    <ShieldCheck className="h-4 w-4 text-neutral-500" />
                                    <h2 className="text-sm font-semibold tracking-wide text-neutral-600 uppercase dark:text-neutral-300">
                                        Capacidades y Permisos
                                    </h2>
                                </div>
                                <div className="flex gap-3">
                                    <div className="flex items-center gap-1.5">
                                        <div className="h-2 w-2 rounded-full border border-neutral-300 bg-white dark:border-neutral-600 dark:bg-neutral-900"></div>
                                        <span className="text-[10px] font-bold tracking-tighter text-neutral-400 uppercase">
                                            Heredado
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-1.5">
                                        <div className="h-2 w-2 rounded-full bg-blue-500"></div>
                                        <span className="text-[10px] font-bold tracking-tighter text-neutral-400 uppercase">
                                            Directo
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div className="divide-y divide-sidebar-border/70 p-6">
                                {Object.keys(groupedPermissions).length > 0 ? (
                                    <div className="grid gap-6 sm:grid-cols-2">
                                        {Object.entries(groupedPermissions).map(
                                            ([module, perms]) => (
                                                <div
                                                    key={module}
                                                    className="space-y-2"
                                                >
                                                    <h3 className="text-[10px] font-black tracking-widest text-neutral-400 uppercase dark:text-neutral-500">
                                                        {module}
                                                    </h3>
                                                    <div className="flex flex-wrap gap-1.5">
                                                        {perms.map((perm) => {
                                                            const action =
                                                                perm.split(
                                                                    '.',
                                                                )[1];
                                                            const isDirect =
                                                                directPermissions.includes(
                                                                    perm,
                                                                );
                                                            const isInherited =
                                                                rolePermissions.has(
                                                                    perm,
                                                                );
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
                                                                    {isDirect &&
                                                                        isInherited && (
                                                                            <span className="ml-1 text-[8px] opacity-60">
                                                                                (ambos)
                                                                            </span>
                                                                        )}
                                                                </Badge>
                                                            );
                                                        })}
                                                    </div>
                                                </div>
                                            ),
                                        )}
                                    </div>
                                ) : (
                                    <div className="flex flex-col items-center justify-center py-8 text-center text-neutral-400">
                                        <ShieldCheck className="mb-2 h-10 w-10 opacity-20" />
                                        <p className="text-sm italic">
                                            Sin permisos asignados.
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </TabsContent>
                </Tabs>
            </div>

            <AssignRepresentativeModal
                isOpen={isAssignModalOpen}
                onClose={() => setIsAssignModalOpen(false)}
                studentId={user.id}
                relationshipTypes={relationshipTypes}
                availableRepresentatives={availableRepresentatives}
            />

            <HealthRecordModal
                isOpen={isHealthModalOpen}
                onClose={() => {
                    setIsHealthModalOpen(false);
                    setEditingHealthRecord(null);
                }}
                studentId={user.id}
                studentName={user.name}
                healthConditions={healthConditions}
                currentUserId={auth.user.id}
                existingRecord={editingHealthRecord}
                isEditing={!!editingHealthRecord}
            />
        </AppLayout>
    );
}
