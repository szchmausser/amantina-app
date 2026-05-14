import { Head, Link, usePage, router } from '@inertiajs/react';
import { useMemo } from 'react';
import {
    ArrowLeft,
    Clock,
    Pencil,
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
    Building2,
} from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import { useInitials } from '@/hooks/use-initials';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import {
    index as userIndex,
    edit as userEdit,
    show as userShowDetail,
    pdf as userPdf,
} from '@/routes/admin/users';
import { destroy as unlinkRepresentative } from '@/routes/admin/student-representatives';
import { store as linkRepresentative } from '@/routes/admin/student-representatives';
import type { BreadcrumbItem, User } from '@/types';
import { useState } from 'react';
import AssignRepresentativeModal from './partials/assign-representative-modal';
import AssignStudentModal from './partials/assign-student-modal';
import HealthRecordModal from './partials/health-record-modal';
import ExternalHourModal, {
    ExternalHourItem,
} from './partials/external-hour-modal';

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
    availableStudents?: { id: number; name: string; cedula: string }[];
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
        breakdown_by_term?: Array<{
            termName: string;
            totalHours: number;
            quota: number;
            percentage: number;
        }>;
    } | null;
    externalHours?: ExternalHourItem[];
    academicYears?: { id: number; name: string }[];
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
    academic_year_name: string;
    teacher: string | null;
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
    availableStudents = [],
    healthConditions,
    hourHistory,
    hourStats,
    externalHours = [],
    academicYears = [],
}: Props) {
    const { auth } = usePage<any>().props;
    const [isAssignModalOpen, setIsAssignModalOpen] = useState(false);
    const [isAssignStudentModalOpen, setIsAssignStudentModalOpen] = useState(false);
    const [isHealthModalOpen, setIsHealthModalOpen] = useState(false);
    const [editingHealthRecord, setEditingHealthRecord] = useState<any>(null);
    const [isExternalHourModalOpen, setIsExternalHourModalOpen] =
        useState(false);
    const [editingExternalHour, setEditingExternalHour] =
        useState<ExternalHourItem | null>(null);
    const [activeTab, setActiveTab] = useState('general');
    const getInitials = useInitials();

    // AlertDialog states
    const [unlinkDialogOpen, setUnlinkDialogOpen] = useState(false);
    const [pendingUnlinkId, setPendingUnlinkId] = useState<number | null>(null);
    const [deleteHealthDialogOpen, setDeleteHealthDialogOpen] = useState(false);
    const [pendingDeleteHealthId, setPendingDeleteHealthId] = useState<number | null>(null);
    const [deleteExternalHourDialogOpen, setDeleteExternalHourDialogOpen] = useState(false);
    const [pendingDeleteExternalHourId, setPendingDeleteExternalHourId] = useState<number | null>(null);
    const [permissionDetail, setPermissionDetail] = useState<string | null>(null);

    const hasPermission = (p: string) => auth.permissions?.includes(p);
    const authRoles = auth.user?.roles?.map((r: any) => r.name) ?? [];
    const canViewPermissionsTab = authRoles.includes('admin') || authRoles.includes('profesor');

    const getRolesForPermission = (perm: string): string[] => {
        const roleNames: string[] = [];
        user.roles?.forEach((r: any) => {
            if (r.permissions?.some((p: any) => p.name === perm)) {
                roleNames.push(r.name);
            }
        });
        return roleNames;
    };

    const roles = user.roles ? user.roles.map((r: any) => r.name) : [];
    const directPermissions = user.permissions
        ? user.permissions.map((p: any) => p.name)
        : [];

    // Collect all permissions from all roles
    const rolePermissions = new Set<string>();
    user.roles?.forEach((r: any) => {
        r.permissions?.forEach((p: any) => rolePermissions.add(p.name));
    });

    // All known permission modules and their standard actions
    const ALL_MODULES: Record<string, string[]> = {
        users: ['view', 'create', 'edit', 'delete'],
        roles: ['view', 'create', 'edit', 'delete'],
        permissions: ['view', 'create', 'edit', 'delete'],
        academic_years: ['view', 'create', 'edit', 'delete'],
        school_terms: ['view', 'create', 'edit', 'delete'],
        grades: ['view', 'create', 'edit', 'delete'],
        sections: ['view', 'create', 'edit', 'delete'],
        enrollments: ['view', 'create', 'edit', 'delete'],
        assignments: ['view', 'create', 'edit', 'delete'],
        academic_info: ['view'],
        health_conditions: ['view', 'create', 'edit', 'delete'],
        student_health: ['view', 'create', 'edit', 'delete'],
        activity_categories: ['view', 'create', 'edit', 'delete'],
        locations: ['view', 'create', 'edit', 'delete'],
        field_sessions: ['view', 'create', 'edit', 'delete'],
        attendances: ['view', 'create', 'edit', 'delete'],
        attendance_activities: ['view', 'create', 'edit', 'delete'],
        dashboard: ['view'],
        accumulated_hours: ['view'],
        external_hours: ['view', 'create', 'edit', 'delete'],
        grade_definitions: ['view', 'create', 'edit', 'delete'],
        section_definitions: ['view', 'create', 'edit', 'delete'],
    };

    // Card background
    const MODULE_CARD_BG = 'bg-neutral-50 dark:bg-neutral-900/20';
    // Border color by completion: red=none, amber=partial, green=all
    const moduleBorderClass = (assignedCount: number, totalActions: number): string => {
        if (assignedCount === 0) return 'border-l-red-400 dark:border-l-red-500';
        if (assignedCount === totalActions) return 'border-l-green-500 dark:border-l-green-600';
        return 'border-l-amber-400 dark:border-l-amber-500';
    };

    const moduleNames: Record<string, string> = {
        users: 'Usuarios',
        roles: 'Roles',
        permissions: 'Permisos',
        academic_years: 'Años Académicos',
        school_terms: 'Lapsos',
        grades: 'Grados',
        sections: 'Secciones',
        enrollments: 'Inscripciones',
        assignments: 'Asignaciones',
        academic_info: 'Info. Académica',
        health_conditions: 'Condiciones de Salud',
        student_health: 'Salud Estudiantil',
        activity_categories: 'Actividades',
        locations: 'Ubicaciones',
        field_sessions: 'Jornadas',
        attendances: 'Asistencias',
        attendance_activities: 'Act. de Asistencia',
        dashboard: 'Dashboard',
        accumulated_hours: 'Horas Acumuladas',
        external_hours: 'Horas Externas',
        grade_definitions: 'Def. de Grados',
        section_definitions: 'Def. de Secciones',
    };

    // Build the full list: all modules sorted, each with its actions
    const sortedModules = Object.keys(ALL_MODULES).sort();
    const fullPermissionList = sortedModules.map((moduleKey) => ({
        key: moduleKey,
        label: moduleNames[moduleKey] || moduleKey,
        actions: ALL_MODULES[moduleKey],
    }));

    // Group all unique permissions by module (only what the user has)
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

    // Total standard permissions vs assigned
    const totalStandardPerms = Object.values(ALL_MODULES).reduce((sum, actions) => sum + actions.length, 0);
    const assignedStandardPerms = allUserPermissions.filter((p) => {
        const [mod, act] = p.split('.');
        return ALL_MODULES[mod]?.includes(act);
    }).length;

    const isAlumno = roles.includes('alumno');
    const isRepresentante = roles.includes('representante');

    // Group hourHistory by academic year (most recent first)
    const groupedHistory = useMemo(() => {
        if (!hourHistory || hourHistory.length === 0) return null;

        const grouped: Record<string, typeof hourHistory> = {};
        hourHistory.forEach((item) => {
            const yearName = item.fieldSession?.academic_year_name || 'Sin año';
            if (!grouped[yearName]) {
                grouped[yearName] = [];
            }
            grouped[yearName].push(item);
        });

        // Sort years: most recent first
        const sortedYears = Object.keys(grouped).sort((a, b) => {
            if (a === 'Sin año') return 1;
            if (b === 'Sin año') return -1;
            return b.localeCompare(a); // Descending
        });

        return { grouped, sortedYears };
    }, [hourHistory]);

    const handleUnlink = (pivotId: number) => {
        setPendingUnlinkId(pivotId);
        setUnlinkDialogOpen(true);
    };

    const confirmUnlink = () => {
        if (!pendingUnlinkId) return;
        router.delete(unlinkRepresentative(pendingUnlinkId).url);
        setUnlinkDialogOpen(false);
        setPendingUnlinkId(null);
    };

    const handleDeleteHealthRecord = (recordId: number) => {
        setPendingDeleteHealthId(recordId);
        setDeleteHealthDialogOpen(true);
    };

    const confirmDeleteHealthRecord = () => {
        if (!pendingDeleteHealthId) return;
        router.delete(`/admin/student-health-records/${pendingDeleteHealthId}`);
        setDeleteHealthDialogOpen(false);
        setPendingDeleteHealthId(null);
    };

    const handleDeleteExternalHour = (externalHourId: number) => {
        setPendingDeleteExternalHourId(externalHourId);
        setDeleteExternalHourDialogOpen(true);
    };

    const confirmDeleteExternalHour = () => {
        if (!pendingDeleteExternalHourId) return;
        router.delete(`/admin/external-hours/${pendingDeleteExternalHourId}`, {
            preserveScroll: true,
        });
        setDeleteExternalHourDialogOpen(false);
        setPendingDeleteExternalHourId(null);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Usuario: ${user.name}`} />

            <SettingsLayout>
            <div>
                {/* User Header */}
                <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
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
                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => window.history.back()}
                        >
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                        {isAlumno && (
                            <Button variant="outline" size="sm" asChild>
                                <a
                                    href={userPdf(user.id).url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <Download className="mr-2 h-4 w-4" />
                                    Exportar PDF
                                </a>
                            </Button>
                        )}
                        {(hasPermission('users.edit') ||
                            (auth.user && auth.user.id === user.id)) && (
                            <Button variant="outline" size="sm" className="text-blue-600 border-blue-200 hover:bg-blue-50" asChild>
                                <Link href={userEdit(user.id).url}>
                                    <Pencil className="mr-2 h-4 w-4" />
                                    Editar Perfil
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                {/* Tabs */}
                <Tabs
                    value={activeTab}
                    onValueChange={setActiveTab}
                    className="space-y-6"
                >
                    {(() => {
                        const tabCount = 1 + (isAlumno ? 2 : 0) + (canViewPermissionsTab ? 1 : 0);
                        const grid = tabCount === 1 ? 'grid-cols-1' : tabCount === 2 ? 'grid-cols-2' : tabCount === 3 ? 'grid-cols-2 sm:grid-cols-3' : 'grid-cols-2 sm:grid-cols-4';
                        return (
                            <TabsList className={`grid w-full bg-neutral-100 sm:w-auto dark:bg-neutral-800 ${grid}`}>
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
                                {canViewPermissionsTab && (
                                    <TabsTrigger value="permisos">Permisos</TabsTrigger>
                                )}
                            </TabsList>
                        );
                    })()}

                    {/* Tab: General */}
                    <TabsContent value="general" className="space-y-6">
                        {/* Información Personal */}
                        <div className="overflow-hidden rounded-xl border">
                            <div className="flex items-center gap-2 bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                <UserIcon className="h-4 w-4 text-neutral-500" />
                                <h2 className="text-sm font-semibold tracking-wide text-neutral-600 uppercase dark:text-neutral-300">
                                    Información Personal
                                </h2>
                            </div>
                            <div className="grid gap-6 p-6 md:grid-cols-2">
                                <div className="space-y-1">
                                    <p className="text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                        Cédula
                                    </p>
                                    <p className="text-sm font-medium">
                                        {user.cedula || '—'}
                                    </p>
                                </div>
                                <div className="space-y-1">
                                    <p className="text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                        Correo Electrónico
                                    </p>
                                    <p className="text-sm font-medium">
                                        {user.email}
                                    </p>
                                </div>
                                <div className="space-y-1">
                                    <p className="text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                        Teléfono
                                    </p>
                                    <p className="text-sm font-medium">
                                        {user.phone || '—'}
                                    </p>
                                </div>
                                <div className="space-y-1">
                                    <p className="text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
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
                                <div className="overflow-hidden rounded-xl border">
                                    <div className="flex items-center gap-2 bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                        <BookOpen className="h-4 w-4 text-neutral-500" />
                                        <h2 className="text-sm font-semibold tracking-wide text-neutral-600 uppercase dark:text-neutral-300">
                                            Información Académica
                                        </h2>
                                    </div>
                                    <div className="grid gap-6 p-6 md:grid-cols-2">
                                        {user.is_transfer && (
                                            <div className="space-y-1">
                                                <p className="text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                                    Institución de Procedencia
                                                </p>
                                                <p className="text-sm font-medium">
                                                    {user.institution_origin ||
                                                        'No especificada'}
                                                </p>
                                            </div>
                                        )}
                                        {currentEnrollment ? (
                                            <div className="space-y-2">
                                                <p className="text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                                    Grado y Sección
                                                </p>
                                                <div className="flex flex-wrap items-center gap-2">
                                                    {hasPermission('academic_years.view') ? (
                                                        <Link href="/admin/academic-years">
                                                            <Badge
                                                                variant="secondary"
                                                                className="font-medium hover:bg-accent transition-colors cursor-pointer"
                                                            >
                                                                Año:{' '}
                                                                {currentEnrollment
                                                                    .academic_year?.name ||
                                                                    '—'}
                                                            </Badge>
                                                        </Link>
                                                    ) : (
                                                        <Badge variant="secondary" className="font-medium">
                                                            Año:{' '}
                                                            {currentEnrollment
                                                                .academic_year?.name ||
                                                                '—'}
                                                        </Badge>
                                                    )}
                                                    {hasPermission('grades.view') ? (
                                                        <Link href="/admin/grades">
                                                            <Badge
                                                                variant="secondary"
                                                                className="font-medium hover:bg-accent transition-colors cursor-pointer"
                                                            >
                                                                Grado:{' '}
                                                                {currentEnrollment.grade
                                                                    ?.name || '—'}
                                                            </Badge>
                                                        </Link>
                                                    ) : (
                                                        <Badge variant="secondary" className="font-medium">
                                                            Grado:{' '}
                                                            {currentEnrollment.grade
                                                                ?.name || '—'}
                                                        </Badge>
                                                    )}
                                                    {hasPermission('sections.view') ? (
                                                        <Link href="/admin/sections">
                                                            <Badge
                                                                variant="secondary"
                                                                className="font-medium hover:bg-accent transition-colors cursor-pointer"
                                                            >
                                                                Sección:{' '}
                                                                {currentEnrollment
                                                                    .section?.name ||
                                                                    '—'}
                                                            </Badge>
                                                        </Link>
                                                    ) : (
                                                        <Badge variant="secondary" className="font-medium">
                                                            Sección:{' '}
                                                            {currentEnrollment
                                                                .section?.name ||
                                                                '—'}
                                                        </Badge>
                                                    )}
                                                </div>
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
                                <div className="overflow-hidden rounded-xl border">
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
                                            <div className="divide-y divide-border">
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
                                                                    <Link
                                                                        href={userShowDetail(rep.id).url}
                                                                        className="text-sm font-semibold decoration-neutral-300 hover:underline"
                                                                    >
                                                                        {
                                                                            rep.name
                                                                        }
                                                                    </Link>
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
                            <div className="overflow-hidden rounded-xl border">
                                <div className="flex items-center justify-between bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                    <div className="flex items-center gap-2">
                                        <Users className="h-4 w-4 text-neutral-500" />
                                        <h2 className="text-sm font-semibold tracking-wide text-neutral-600 uppercase dark:text-neutral-300">
                                            Estudiantes a Cargo
                                        </h2>
                                    </div>
                                    {hasPermission('users.edit') && (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            className="h-7 text-[10px] font-bold uppercase"
                                            onClick={() =>
                                                setIsAssignStudentModalOpen(true)
                                            }
                                        >
                                            <UserPlus className="mr-1.5 h-3 w-3" />
                                            Asignar
                                        </Button>
                                    )}
                                </div>
                                <div className="p-0">
                                    {user.represented_students &&
                                    user.represented_students.length > 0 ? (
                                        <div className="divide-y divide-border">
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

                    </TabsContent>

                    {/* Tab: Salud (solo para alumnos) */}
                    {isAlumno && (
                        <TabsContent value="salud" className="space-y-6">
                            <div className="overflow-hidden rounded-xl border">
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
                                    {user.health_records && user.health_records.length > 0 ? (
                                        <div className="divide-y divide-border">
                                            {user.health_records.map((record: any) => (
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
                                                                    {record.condition?.name || 'Sin condición'}
                                                                </p>
                                                                <div className="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-neutral-500">
                                                                    <span className="flex items-center gap-1">
                                                                        <Calendar className="h-3 w-3" />
                                                                        {new Date(record.received_at).toLocaleDateString('es-ES')}
                                                                    </span>
                                                                    {record.received_at_location && (
                                                                        <span className="flex items-center gap-1">
                                                                            <MapPin className="h-3 w-3" />
                                                                            {record.received_at_location}
                                                                        </span>
                                                                    )}
                                                                    <span>Recibido por: {record.received_by?.name || '—'}</span>
                                                                </div>
                                                                {record.observations && (
                                                                    <p className="mt-1 text-xs text-neutral-400 italic">{record.observations}</p>
                                                                )}
                                                            </div>
                                                        </div>
                                                        <div className="flex gap-1">
                                                            {hasPermission('student_health.edit') && (
                                                                <Button variant="ghost" size="icon" className="h-7 w-7 text-neutral-500 hover:text-blue-600">
                                                                    <Pencil className="h-3.5 w-3.5" />
                                                                </Button>
                                                            )}
                                                            {hasPermission('student_health.delete') && (
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="h-7 w-7 text-red-500 hover:bg-red-50 hover:text-red-600"
                                                                    onClick={() => handleDeleteHealthRecord(record.id)}
                                                                >
                                                                    <Trash2 className="h-3.5 w-3.5" />
                                                                </Button>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="flex flex-col items-center gap-3 py-16 text-center">
                                            <div className="flex h-14 w-14 items-center justify-center rounded-full bg-neutral-100 dark:bg-neutral-800">
                                                <Heart className="h-7 w-7 text-neutral-300 dark:text-neutral-600" />
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-neutral-500 dark:text-neutral-400">
                                                    Sin registros de salud
                                                </p>
                                                <p className="mt-0.5 text-xs text-neutral-400 dark:text-neutral-500">
                                                    Este estudiante no tiene condiciones de salud registradas.
                                                </p>
                                            </div>
                                            {hasPermission('student_health.create') && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => {
                                                        setEditingHealthRecord(null);
                                                        setIsHealthModalOpen(true);
                                                    }}
                                                >
                                                    <Plus className="mr-1.5 h-3 w-3" />
                                                    Agregar primer registro
                                                </Button>
                                            )}
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
                                    <div className="overflow-hidden rounded-xl border">
                                        <div className="bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                            <h3 className="text-sm font-semibold text-neutral-600 uppercase dark:text-neutral-300">
                                                {
                                                    hourStats.current_year
                                                        .year_name
                                                }
                                            </h3>
                                        </div>
                                        <div className="p-6">
                                            <div className="mb-4 flex items-end justify-between">
                                                <div>
                                                    <p className="text-3xl font-bold text-neutral-900 dark:text-neutral-100">
                                                        {hourStats.current_year.hours.toFixed(
                                                            1,
                                                        )}
                                                        h
                                                    </p>
                                                    <p className="text-sm text-neutral-500">
                                                        de{' '}
                                                        {
                                                            hourStats
                                                                .current_year
                                                                .required
                                                        }
                                                        h requeridas
                                                    </p>
                                                </div>
                                                <div className="text-right">
                                                    <p
                                                        className={`text-2xl font-bold ${
                                                            hourStats
                                                                .current_year
                                                                .percentage >=
                                                            100
                                                                ? 'text-green-600'
                                                                : hourStats
                                                                        .current_year
                                                                        .percentage >=
                                                                    75
                                                                  ? 'text-blue-600'
                                                                  : hourStats
                                                                          .current_year
                                                                          .percentage >=
                                                                      50
                                                                    ? 'text-amber-600'
                                                                    : 'text-red-600'
                                                        }`}
                                                    >
                                                        {hourStats.current_year.percentage.toFixed(
                                                            0,
                                                        )}
                                                        %
                                                    </p>
                                                </div>
                                            </div>
                                            {/* Barra de progreso */}
                                            <div className="relative h-3 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                                                <div
                                                    className={`h-full transition-all duration-500 ${
                                                        hourStats.current_year
                                                            .percentage >= 100
                                                            ? 'bg-green-500'
                                                            : hourStats
                                                                    .current_year
                                                                    .percentage >=
                                                                75
                                                              ? 'bg-blue-500'
                                                              : hourStats
                                                                      .current_year
                                                                      .percentage >=
                                                                  50
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
                                    <div className="overflow-hidden rounded-xl border">
                                        <div className="bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                            <h3 className="text-sm font-semibold text-neutral-600 uppercase dark:text-neutral-300">
                                                Acumulado General
                                            </h3>
                                        </div>
                                        <div className="p-6">
                                            <div className="mb-4 flex items-end justify-between">
                                                <div>
                                                    <p className="text-3xl font-bold text-neutral-900 dark:text-neutral-100">
                                                        {hourStats.total.hours.toFixed(
                                                            1,
                                                        )}
                                                        h
                                                    </p>
                                                    <p className="text-sm text-neutral-500">
                                                        de{' '}
                                                        {
                                                            hourStats.total
                                                                .required
                                                        }
                                                        h totales
                                                    </p>
                                                </div>
                                                <div className="text-right">
                                                    <p
                                                        className={`text-2xl font-bold ${
                                                            hourStats.total
                                                                .percentage >=
                                                            100
                                                                ? 'text-green-600'
                                                                : hourStats
                                                                        .total
                                                                        .percentage >=
                                                                    75
                                                                  ? 'text-blue-600'
                                                                  : hourStats
                                                                          .total
                                                                          .percentage >=
                                                                      50
                                                                    ? 'text-amber-600'
                                                                    : 'text-red-600'
                                                        }`}
                                                    >
                                                        {hourStats.total.percentage.toFixed(
                                                            0,
                                                        )}
                                                        %
                                                    </p>
                                                </div>
                                            </div>
                                            {/* Barra de progreso */}
                                            <div className="relative h-3 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                                                <div
                                                    className={`h-full transition-all duration-500 ${
                                                        hourStats.total
                                                            .percentage >= 100
                                                            ? 'bg-green-500'
                                                            : hourStats.total
                                                                    .percentage >=
                                                                75
                                                              ? 'bg-blue-500'
                                                              : hourStats.total
                                                                      .percentage >=
                                                                  50
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

                            {/* Desglose por Lapso */}
                            {hourStats && hourStats.breakdown_by_term && hourStats.breakdown_by_term.length > 0 && (
                                <div className="overflow-hidden rounded-xl border">
                                    <div className="bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                        <h3 className="text-sm font-semibold text-neutral-600 uppercase dark:text-neutral-300">
                                            Desglose por Lapso ({hourStats.current_year.year_name})
                                        </h3>
                                    </div>
                                    <div className="p-6">
                                        <div className="grid gap-4 md:grid-cols-3">
                                            {hourStats.breakdown_by_term.map((term, index) => {
                                                const colorClass = term.percentage >= 100
                                                    ? 'text-green-600 dark:text-green-500'
                                                    : term.percentage >= 75
                                                    ? 'text-blue-600 dark:text-blue-500'
                                                    : term.percentage >= 50
                                                    ? 'text-amber-600 dark:text-amber-500'
                                                    : 'text-red-600 dark:text-red-500';

                                                return (
                                                    <div key={index} className="rounded-lg border bg-card p-4">
                                                        <p className="mb-1 text-xs font-medium text-muted-foreground uppercase">
                                                            {term.termName}
                                                        </p>
                                                        <p className={`text-2xl font-bold ${colorClass}`}>
                                                            {term.totalHours}h
                                                        </p>
                                                        <p className="mt-1 text-xs text-muted-foreground">
                                                            de {term.quota}h ({term.percentage.toFixed(0)}%)
                                                        </p>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Horas Externas Acreditadas */}
                            <div className="overflow-hidden rounded-xl border">
                                <div className="flex items-center justify-between bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                    <div className="flex items-center gap-2">
                                        <Building2 className="h-4 w-4 text-indigo-500" />
                                        <h2 className="text-sm font-semibold tracking-wide text-neutral-600 uppercase dark:text-neutral-300">
                                            Horas Externas Acreditadas
                                        </h2>
                                    </div>
                                    {hasPermission('external_hours.create') && (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            className="h-7 text-[10px] font-bold uppercase"
                                            onClick={() => {
                                                setEditingExternalHour(null);
                                                setIsExternalHourModalOpen(
                                                    true,
                                                );
                                            }}
                                        >
                                            <Plus className="mr-1.5 h-3 w-3" />
                                            Agregar
                                        </Button>
                                    )}
                                </div>
                                <div className="p-0">
                                    {externalHours.length > 0 ? (
                                        <div className="divide-y divide-border">
                                            {externalHours.map((item) => (
                                                <div
                                                    key={item.id}
                                                    className="p-4 px-6 transition-colors hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30"
                                                >
                                                    <div className="flex items-start justify-between gap-4">
                                                        <div className="flex flex-1 items-start gap-3">
                                                            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-50 dark:bg-indigo-900/20">
                                                                <Building2 className="h-4 w-4 text-indigo-500" />
                                                            </div>
                                                            <div className="min-w-0 flex-1">
                                                                <div className="flex flex-wrap items-center gap-2">
                                                                    <p className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                                                        {
                                                                            item.institution_name
                                                                        }
                                                                    </p>
                                                                    {item.period && (
                                                                        <Badge
                                                                            variant="secondary"
                                                                            className="text-xs"
                                                                        >
                                                                            {
                                                                                item.period
                                                                            }
                                                                        </Badge>
                                                                    )}
                                                                    <Badge className="bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400">
                                                                        {
                                                                            item.hours
                                                                        }
                                                                        h
                                                                    </Badge>
                                                                </div>
                                                                {item.description && (
                                                                    <p className="mt-1 text-xs text-neutral-400 italic">
                                                                        {
                                                                            item.description
                                                                        }
                                                                    </p>
                                                                )}
                                                                <div className="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-neutral-500">
                                                                    {item.admin && (
                                                                        <span>
                                                                            Cargado
                                                                            por:{' '}
                                                                            {
                                                                                item
                                                                                    .admin
                                                                                    .name
                                                                            }
                                                                        </span>
                                                                    )}
                                                                    <span className="flex items-center gap-1">
                                                                        <Calendar className="h-3 w-3" />
                                                                        {new Date(
                                                                            item.created_at,
                                                                        ).toLocaleDateString(
                                                                            'es-ES',
                                                                        )}
                                                                    </span>
                                                                </div>
                                                                {item.media &&
                                                                    item.media
                                                                        .length >
                                                                        0 && (
                                                                        <div className="mt-2 flex flex-wrap gap-2">
                                                                            {item.media.map(
                                                                                (
                                                                                    m,
                                                                                ) => (
                                                                                    <a
                                                                                        key={
                                                                                            m.id
                                                                                        }
                                                                                        href={
                                                                                            m.original_url
                                                                                        }
                                                                                        target="_blank"
                                                                                        rel="noopener noreferrer"
                                                                                        className="inline-flex items-center gap-1 rounded border bg-neutral-50 px-2 py-1 text-xs text-neutral-600 transition-colors hover:bg-neutral-100 dark:bg-neutral-800 dark:text-neutral-400 dark:hover:bg-neutral-700"
                                                                                    >
                                                                                        <Paperclip className="h-3 w-3" />
                                                                                        {
                                                                                            m.name
                                                                                        }
                                                                                        <Download className="h-3 w-3" />
                                                                                    </a>
                                                                                ),
                                                                            )}
                                                                        </div>
                                                                    )}
                                                            </div>
                                                        </div>
                                                        <div className="flex shrink-0 gap-1">
                                                            {hasPermission(
                                                                'external_hours.edit',
                                                            ) && (
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="h-7 w-7 text-neutral-500 hover:text-blue-600"
                                                                    onClick={() => {
                                                                        setEditingExternalHour(
                                                                            item,
                                                                        );
                                                                        setIsExternalHourModalOpen(
                                                                            true,
                                                                        );
                                                                    }}
                                                                >
                                                                    <Pencil className="h-3.5 w-3.5" />
                                                                </Button>
                                                            )}
                                                            {hasPermission(
                                                                'external_hours.delete',
                                                            ) && (
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="h-7 w-7 text-red-500 hover:bg-red-50 hover:text-red-600"
                                                                    onClick={() =>
                                                                        handleDeleteExternalHour(
                                                                            item.id,
                                                                        )
                                                                    }
                                                                >
                                                                    <Trash2 className="h-3.5 w-3.5" />
                                                                </Button>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="px-6 py-4 text-center">
                                            <p className="text-xs text-neutral-400 italic">
                                                Sin horas externas acreditadas.
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Historial de Horas */}
                            <div className="overflow-hidden rounded-xl border">
                                <div className="flex items-center gap-2 bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                    <Clock className="h-4 w-4 text-green-600" />
                                    <h2 className="text-sm font-semibold tracking-wide text-neutral-600 uppercase dark:text-neutral-300">
                                        Historial de Horas Socioproductivas
                                    </h2>
                                </div>
                                 <div className="p-0">
                                     {groupedHistory && groupedHistory.sortedYears.length > 0 ? (
                                         <div className="divide-y divide-border">
                                             {groupedHistory.sortedYears.map((yearName) => (
                                                 <div key={yearName}>
                                                     {/* Year Header */}
                                                     <div className="bg-neutral-100/50 px-6 py-2 dark:bg-neutral-800/30">
                                                          <span className="text-xs font-semibold tracking-wider text-neutral-500 uppercase">
                                                              {yearName}
                                                         </span>
                                                     </div>
                                                     {/* Sessions for this year */}
                                                     {groupedHistory.grouped[yearName].map((item) => (
                                                         <div
                                                             key={item.id}
                                                             className="p-4 px-6"
                                                         >
                                                             <div className="flex items-start justify-between">
                                                                 <div className="flex flex-1 items-start gap-3">
                                                                     <div
                                                                         className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-full ${
                                                                             item.attended
                                                                                 ? 'bg-green-50 dark:bg-green-900/20'
                                                                                 : 'bg-red-50 dark:bg-red-50 dark:bg-red-900/20'
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
                                                                             {
                                                                                 item.fieldSession
                                                                                     ?.name ||
                                                                                     'Jornada'}
                                                                             <span className="ml-2 text-[10px] font-normal text-neutral-400">
                                                                                 ({item.fieldSession?.academic_year_name || 'Sin año'})
                                                                             </span>
                                                                         </p>
                                          <div className="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-neutral-500">
                                              {item.fieldSession && (
                                                  <span className="flex items-center gap-1">
                                                      <Calendar className="h-3 w-3" />
                                                      {item.fieldSession.start_datetime}
                                                  </span>
                                              )}
                                              {item.fieldSession?.teacher && (
                                                  <span className="flex items-center gap-1">
                                                      <UserIcon className="h-3 w-3" />
                                                      {item.fieldSession.teacher}
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
                                                                             item.activities.length >
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
                                                                                                 }{' '}
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
                                                                 <div className="ml-4 flex flex-col items-end gap-2">
                                                                     <Badge
                                                                         variant="outline"
                                                                         className="text-xs"
                                                                     >
                                                                         {
                                                                             item.created_at
                                                                         }
                                                                     </Badge>
                                                                     {/* Resultado de la jornada */}
                                                                     <Badge
                                                                         className={`text-sm font-bold ${
                                                                             item.total_hours >
                                                                             0
                                                                                 ? 'bg-green-500 text-white hover:bg-green-600'
                                                                                 : item.total_hours <
                                                                                     0
                                                                                 ? 'bg-red-500 text-white hover:bg-red-600'
                                                                                 : 'bg-neutral-300 text-neutral-700 hover:bg-neutral-400'
                                                                         }`}
                                                                     >
                                                                         {item.total_hours >
                                                                             0 && '+'}
                                                                         {
                                                                             item.total_hours
                                                                         }{' '}
                                                                     </Badge>
                                                                 </div>
                                                             </div>
                                                         </div>
                                                     ))}
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

                    {canViewPermissionsTab && (
                        <TabsContent value="permisos" className="space-y-6">
                            <div className="overflow-hidden rounded-xl border">
                                <div className="flex items-center justify-between bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                    <div className="flex items-center gap-2">
                                        <ShieldCheck className="h-4 w-4 text-neutral-500" />
                                        <h2 className="text-sm font-semibold tracking-wide text-neutral-600 uppercase dark:text-neutral-300">
                                            Capacidades y Permisos
                                            <span className="ml-2 font-normal text-neutral-400">— {assignedStandardPerms}/{totalStandardPerms}</span>
                                        </h2>
                                    </div>
                                    <div className="flex gap-3">
                                        <div className="flex items-center gap-1.5">
                                            <div className="h-2 w-2 rounded-full border border-neutral-300 bg-white dark:border-neutral-600 dark:bg-neutral-900"></div>
                                            <span className="text-xs font-semibold tracking-wider text-neutral-500 uppercase">
                                                Heredado
                                            </span>
                                        </div>
                                        <div className="flex items-center gap-1.5">
                                            <div className="h-2 w-2 rounded-full bg-blue-500"></div>
                                            <span className="text-xs font-semibold tracking-wider text-neutral-500 uppercase">
                                                Directo
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div className="p-6">
                                    <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                                        {fullPermissionList.map(
                                            ({ key: moduleKey, label, actions }) => {
                                                const assignedCount = actions.filter((a) =>
                                                    allUserPermissions.includes(`${moduleKey}.${a}`)
                                                ).length;
                                                const totalActions = actions.length;

                                                return (
                                                    <div
                                                        key={moduleKey}
                                                        className={`rounded-lg border-l-4 p-4 ${MODULE_CARD_BG} ${moduleBorderClass(assignedCount, totalActions)}`}
                                                    >
                                                        <h3 className="mb-3 text-xs font-bold tracking-wider uppercase">
                                                            {label}
                                                            <span className="ml-1.5 font-normal text-neutral-400">— {assignedCount}/{totalActions}</span>
                                                        </h3>
                                                        <div className="flex flex-wrap gap-1.5">
                                                            {actions.map((action) => {
                                                                const perm = `${moduleKey}.${action}`;
                                                                const isAssigned = allUserPermissions.includes(perm);
                                                                const isDirect = directPermissions.includes(perm);
                                                                const isInherited = rolePermissions.has(perm);

                                                                return (
                                                                    <button
                                                                        key={perm}
                                                                        type="button"
                                                                        onClick={() => setPermissionDetail(perm)}
                                                                        className={`inline-flex cursor-pointer items-center rounded-md border px-2.5 py-1 text-xs font-medium transition-colors ${
                                                                            isAssigned
                                                                                ? 'border-neutral-200 bg-neutral-100 text-neutral-700 hover:bg-neutral-200 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-300'
                                                                                : 'border-dashed border-neutral-200 text-neutral-400 hover:border-neutral-300 dark:border-neutral-700 dark:text-neutral-500'
                                                                        }`}
                                                                    >
                                                                        {isAssigned ? action : (
                                                                            <span className="opacity-50">{action}</span>
                                                                        )}
                                                                    </button>
                                                                );
                                                            })}
                                                        </div>
                                                    </div>
                                                );
                                            },
                                        )}
                                    </div>
                                </div>
                            </div>
                        </TabsContent>
                    )}
                </Tabs>
            </div>
            </SettingsLayout>

            <AssignRepresentativeModal
                isOpen={isAssignModalOpen}
                onClose={() => setIsAssignModalOpen(false)}
                studentId={user.id}
                relationshipTypes={relationshipTypes}
                availableRepresentatives={availableRepresentatives}
            />

            {isRepresentante && (
                <AssignStudentModal
                    isOpen={isAssignStudentModalOpen}
                    onClose={() => setIsAssignStudentModalOpen(false)}
                    representativeId={user.id}
                    relationshipTypes={relationshipTypes}
                    availableStudents={availableStudents}
                />
            )}

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

            <ExternalHourModal
                isOpen={isExternalHourModalOpen}
                onClose={() => {
                    setIsExternalHourModalOpen(false);
                    setEditingExternalHour(null);
                }}
                studentId={user.id}
                studentName={user.name}
                existingRecord={editingExternalHour}
                isEditing={!!editingExternalHour}
            />

            {/* Dialog: Detalle de Permiso */}
            <Dialog open={permissionDetail !== null} onOpenChange={(open) => { if (!open) setPermissionDetail(null); }}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <ShieldCheck className="h-5 w-5 text-neutral-500" />
                            Capacidad del Usuario
                        </DialogTitle>
                        <DialogDescription>
                            Lo que este usuario puede hacer en el sistema.
                        </DialogDescription>
                    </DialogHeader>
                    {permissionDetail && (() => {
                        const parts = permissionDetail.split('.');
                        const moduleKey = parts[0] || '';
                        const actionKey = parts[1] || '';
                        const isDirect = directPermissions.includes(permissionDetail);
                        const isInherited = rolePermissions.has(permissionDetail);
                        const rolesForPerm = getRolesForPermission(permissionDetail);

                        // Human-readable module names
                        const moduleLabel = moduleNames[moduleKey] || moduleKey;

                        // Human-readable action descriptions
                        const actionDescriptions: Record<string, string> = {
                            view: 'consultar y visualizar',
                            create: 'crear nuevos registros de',
                            edit: 'editar y modificar',
                            delete: 'eliminar',
                        };
                        const actionDesc = actionDescriptions[actionKey] || actionKey;

                        // Origin text for the user
                        let originBadge: { label: string; className: string } | null = null;
                        if (isDirect && isInherited) {
                            originBadge = { label: 'Concedido directamente y por rol', className: 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800' };
                        } else if (isDirect) {
                            originBadge = { label: 'Concedido directamente', className: 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800' };
                        } else if (isInherited) {
                            originBadge = { label: 'Heredado por su rol', className: 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800' };
                        } else {
                            originBadge = { label: 'No asignado', className: 'bg-neutral-100 text-neutral-500 border-neutral-200 dark:bg-neutral-800 dark:text-neutral-400 dark:border-neutral-700' };
                        }

                        return (
                            <div className="space-y-5">
                                {/* What the user can do */}
                                <div className="rounded-lg border bg-neutral-50 p-5 dark:bg-neutral-900/50">
                                    <p className="text-sm leading-relaxed text-neutral-700 dark:text-neutral-300">
                                        {isDirect || isInherited ? (
                                            <>Este usuario <strong className="text-neutral-900 dark:text-neutral-100">puede {actionDesc}</strong> <strong className="text-neutral-900 dark:text-neutral-100">{moduleLabel.toLowerCase()}</strong> en el sistema.</>
                                        ) : (
                                            <>Este usuario <span className="text-neutral-400">no tiene asignada</span> la capacidad de <strong className="text-neutral-400">{actionDesc}</strong> <strong className="text-neutral-400">{moduleLabel.toLowerCase()}</strong>.</>
                                        )}
                                    </p>
                                </div>

                                {/* How they got it */}
                                <div className="space-y-3">
                                    <h4 className="text-xs font-semibold tracking-wider text-neutral-500 uppercase">
                                        ¿Cómo obtuvo esta capacidad?
                                    </h4>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className={`inline-flex items-center rounded-md border px-2.5 py-1 text-xs font-medium ${originBadge.className}`}>
                                            {originBadge.label}
                                        </span>
                                    </div>
                                    {rolesForPerm.length > 0 && (
                                        <p className="text-xs text-neutral-500">
                                            A través del rol{rolesForPerm.length > 1 ? 'es' : ''}:{' '}
                                            {rolesForPerm.map((r) => (
                                                <Badge key={r} variant="secondary" className="ml-1 text-xs capitalize">
                                                    {r}
                                                </Badge>
                                            ))}
                                        </p>
                                    )}
                                </div>

                                <div className="text-xs text-neutral-400 italic">
                                    Presiona ESC o haz clic fuera para cerrar.
                                </div>
                            </div>
                        );
                    })()}
                </DialogContent>
            </Dialog>

            {/* AlertDialog: Desvincular representante */}
            <AlertDialog open={unlinkDialogOpen} onOpenChange={setUnlinkDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>¿Desvincular representante?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Esta acción no se puede deshacer. El vínculo entre el estudiante y el representante será eliminado permanentemente.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={confirmUnlink}
                            className="bg-red-600 hover:bg-red-700"
                            data-test="confirm-delete-button"
                        >
                            Desvincular
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            {/* AlertDialog: Eliminar registro de salud */}
            <AlertDialog open={deleteHealthDialogOpen} onOpenChange={setDeleteHealthDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>¿Eliminar registro de salud?</AlertDialogTitle>
                        <AlertDialogDescription>
                            <strong>Advertencia:</strong> Los archivos adjuntos se eliminarán permanentemente del servidor y no podrán recuperarse.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={confirmDeleteHealthRecord}
                            className="bg-red-600 hover:bg-red-700"
                            data-test="confirm-delete-button"
                        >
                            Eliminar
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            {/* AlertDialog: Eliminar horas externas */}
            <AlertDialog open={deleteExternalHourDialogOpen} onOpenChange={setDeleteExternalHourDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>¿Eliminar registro de horas externas?</AlertDialogTitle>
                        <AlertDialogDescription>
                            <strong>Advertencia:</strong> Los archivos adjuntos se eliminarán permanentemente.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={confirmDeleteExternalHour}
                            className="bg-red-600 hover:bg-red-700"
                            data-test="confirm-delete-button"
                        >
                            Eliminar
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
