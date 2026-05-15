import { Head, router, useForm } from '@inertiajs/react';
import { AlertCircle, ArrowLeft, Check, Eye, Info, Pencil, PlusCircle, Save, Shield, Trash2 } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
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
    is_protected?: boolean;
}

const ROLE_DESCRIPTIONS: Record<string, string> = {
    admin: 'Acceso total al sistema. Responsable de la configuración global, gestión de usuarios, roles y parámetros institucionales.',
    profesor: 'Encargado de la gestión académica de la asignatura Socioproductiva. Registra actividades, controla asistencias y evalúa el desempeño de los estudiantes.',
    alumno: 'Estudiante inscrito en el sistema. Puede consultar su progreso, horas acumuladas y detalles de las actividades en las que participa.',
    representante: 'Padre o tutor legal. Tiene acceso para supervisar el cumplimiento de las horas y el progreso académico de su representado.',
};

function groupPermissions(
    permissions: Permission[],
): Record<string, Permission[]> {
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

const ACTION_ICONS: Record<string, React.ElementType> = {
    create: PlusCircle,
    edit: Pencil,
    delete: Trash2,
    view: Eye,
    read: Eye,
    update: Pencil,
};

function getActionIcon(permName: string): React.ElementType {
    const action = permName.split('.').pop() ?? '';
    return ACTION_ICONS[action] ?? Shield;
}

function formatModuleName(name: string): string {
    return name
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

export default function RolesEdit({
    role,
    allPermissions,
    is_protected = false,
}: Props) {
    const initialPermissionNames = role.permissions.map((p) => p.name);

    const { data, setData, put, processing, recentlySuccessful, errors } =
        useForm({
            permissions: initialPermissionNames,
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
        const allSelected = modulePermNames.every((n) =>
            data.permissions.includes(n),
        );

        if (allSelected) {
            // In protected mode, we only keep the ones that were initially there
            if (is_protected) {
                const protectedInModule = modulePermNames.filter((n) =>
                    initialPermissionNames.includes(n),
                );
                setData(
                    'permissions',
                    [
                        ...data.permissions.filter(
                            (p) => !modulePermNames.includes(p),
                        ),
                        ...protectedInModule,
                    ],
                );
            } else {
                setData(
                    'permissions',
                    data.permissions.filter((p) => !modulePermNames.includes(p)),
                );
            }
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
        { title: 'Roles', href: '/admin/roles' },
        { title: 'Editar Permisos', href: `/admin/roles/${role.id}/edit` },
    ];

    const groups = groupPermissions(allPermissions);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar Rol: ${role.name}`} />

            <SettingsLayout>
                <div className="space-y-6">
                <div className="px-4 py-4 space-y-6">
                    {/* Header al estilo show.tsx */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                                {role.name.charAt(0).toUpperCase() + role.name.slice(1)}
                            </h1>
                            <div className="flex items-center gap-2 mt-1">
                                {['admin', 'profesor', 'alumno', 'representante'].includes(role.name) ? (
                                    <Badge variant="secondary" className="bg-primary/10 text-primary hover:bg-primary/20 border-transparent text-[10px] font-bold">
                                        Rol del Sistema
                                    </Badge>
                                ) : (
                                    <Badge variant="outline" className="text-[10px]">
                                        Rol Personalizado
                                    </Badge>
                                )}
                                <Badge variant="outline" className="text-[10px] text-neutral-500 dark:text-neutral-400">
                                    {data.permissions.length} Permisos Seleccionados
                                </Badge>
                            </div>
                            <p className="mt-2 text-sm text-neutral-500 dark:text-neutral-400 max-w-2xl">
                                {ROLE_DESCRIPTIONS[role.name.toLowerCase()] || 'Gestión de permisos y accesos para este rol personalizado.'}
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            <Button variant="outline" size="sm" onClick={() => window.history.back()}>
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Cancelar
                            </Button>
                            <Button onClick={handleSubmit} disabled={processing}>
                                <Check className="mr-2 h-4 w-4" />
                                Guardar Cambios
                            </Button>
                        </div>
                    </div>

                    <div className="space-y-4">
                        {/* Refined Information Boxes matching show.tsx */}
                        {['admin', 'profesor', 'alumno', 'representante'].includes(role.name) && (
                            <div className="rounded-xl border border-blue-100 bg-blue-50/30 p-4 dark:border-blue-900/20 dark:bg-blue-950/10">
                                <div className="flex gap-3">
                                    <Info className="h-4 w-4 shrink-0 text-blue-500 mt-0.5 dark:text-blue-400" />
                                    <div className="space-y-1">
                                        <p className="text-xs font-bold text-blue-700 uppercase tracking-wider dark:text-blue-400 dark:text-blue-300">
                                            Rol de Sistema
                                        </p>
                                        <p className="text-sm text-blue-800/80 leading-relaxed dark:text-blue-300/80">
                                            {ROLE_DESCRIPTIONS[role.name.toLowerCase()]}
                                        </p>
                                        <p className="text-[11px] font-medium text-blue-600/70 dark:text-blue-500/60 italic dark:text-blue-400">
                                            * Los roles fundamentales tienen un propósito fijo, pero puedes ajustar sus permisos aquí.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        )}

                        {is_protected && (
                            <div className="rounded-xl border border-amber-200 bg-amber-50/50 p-4 dark:border-amber-900/30 dark:bg-amber-950/10 dark:border-amber-800">
                                <div className="flex gap-3">
                                    <AlertCircle className="h-4 w-4 shrink-0 text-amber-600 mt-0.5 dark:text-amber-400" />
                                    <div className="space-y-1">
                                        <p className="text-xs font-bold text-amber-700 uppercase tracking-wider dark:text-amber-400 dark:text-amber-300">
                                            Modo Protegido
                                        </p>
                                        <p className="text-sm text-amber-800/80 leading-relaxed dark:text-amber-300/80">
                                            Estás editando un rol que tienes asignado. Por seguridad, no puedes quitarte permisos existentes para evitar bloqueos accidentales.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        {errors.permissions && (
                            <Alert variant="destructive">
                                <AlertCircle className="h-4 w-4" />
                                <AlertTitle>Error de validación</AlertTitle>
                                <AlertDescription>
                                    {errors.permissions}
                                </AlertDescription>
                            </Alert>
                        )}
                        <Card className="overflow-hidden rounded-xl border shadow-none p-0 gap-0">
                            <CardHeader className="border-b bg-neutral-50 px-6 py-3 dark:bg-neutral-800/50">
                                <div className="flex items-center gap-2">
                                    <Shield className="h-4 w-4 text-neutral-500 dark:text-neutral-400" />
                                    <h2 className="text-sm font-semibold tracking-wide text-neutral-600 uppercase dark:text-neutral-300 dark:text-neutral-400">
                                        Configuración de Permisos
                                    </h2>
                                </div>
                            </CardHeader>
                            <CardContent className="p-6">
                                <div className="mb-4 flex items-center justify-between px-1">
                                    <span className="text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                        Módulo
                                    </span>
                                    <span className="text-xs font-semibold tracking-wider text-neutral-500 uppercase dark:text-neutral-400">
                                        Permisos / Selección
                                    </span>
                                </div>
                                <div className="grid gap-4 grid-cols-1">
                                    {Object.entries(groups).map(([module, modulePerms]) => {
                                        const modulePermNames = modulePerms.map((p) => p.name);
                                        const allSelected = modulePermNames.every((n) => data.permissions.includes(n));
                                        const someSelected = !allSelected && modulePermNames.some((n) => data.permissions.includes(n));

                                        return (
                                            <div
                                                key={module}
                                                className="flex flex-col gap-3 rounded-lg border border-neutral-200 bg-neutral-50/50 px-4 py-3 dark:border-neutral-700/50 dark:bg-neutral-800/30 sm:flex-row sm:items-center sm:justify-between"
                                            >
                                                <div className="flex items-center gap-3 border-b pb-3 sm:border-b-0 sm:pb-0">
                                                    <span className="text-xs font-bold uppercase tracking-widest text-neutral-600 dark:text-neutral-300 min-w-[120px] dark:text-neutral-400">
                                                        {formatModuleName(module)}
                                                    </span>
                                                </div>

                                                <div className="flex flex-wrap items-center gap-2 sm:justify-end">
                                                    <div className="flex flex-wrap gap-2 pr-3 border-r border-neutral-200 dark:border-neutral-700">
                                                        {modulePerms.map((perm) => {
                                                            const ActionIcon = getActionIcon(perm.name);
                                                            const isSelected = data.permissions.includes(perm.name);
                                                            const isProtected = is_protected && initialPermissionNames.includes(perm.name);

                                                            return (
                                                                <div
                                                                    key={perm.id}
                                                                    className={`flex items-center gap-2 rounded border px-2.5 py-1 transition-colors ${
                                                                        isSelected
                                                                            ? 'border-primary/20 bg-primary/10 text-primary-foreground dark:bg-primary/20'
                                                                            : 'border-neutral-200 bg-neutral-100/50 text-neutral-600 dark:border-neutral-700 dark:bg-neutral-800/50 dark:text-neutral-400'
                                                                    }`}
                                                                >
                                                                    <Checkbox
                                                                        id={`perm-${perm.id}`}
                                                                        checked={isSelected}
                                                                        onCheckedChange={() => togglePermission(perm.name)}
                                                                        disabled={isProtected}
                                                                        className="h-3.5 w-3.5"
                                                                    />
                                                                    <Label
                                                                        htmlFor={`perm-${perm.id}`}
                                                                        className={`flex items-center gap-1.5 text-xs font-medium capitalize cursor-pointer ${
                                                                            isSelected ? 'text-primary dark:text-primary-foreground' : ''
                                                                        }`}
                                                                    >
                                                                        <ActionIcon className="h-3.5 w-3.5 opacity-60" />
                                                                        {perm.name.split('.').pop()}
                                                                    </Label>
                                                                </div>
                                                            );
                                                        })}
                                                    </div>

                                                    <div className="flex items-center gap-2 pl-1">
                                                        <Checkbox
                                                            id={`module-${module}`}
                                                            checked={allSelected ? true : someSelected ? 'indeterminate' : false}
                                                            onCheckedChange={() => toggleModule(module, modulePerms)}
                                                            className="h-4 w-4"
                                                        />
                                                        <Label
                                                            htmlFor={`module-${module}`}
                                                            className="text-[10px] font-bold uppercase tracking-wider text-neutral-400 cursor-pointer hover:text-neutral-600 dark:hover:text-neutral-300 dark:text-neutral-500 dark:hover:text-neutral-400"
                                                        >
                                                            Todos
                                                        </Label>
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </CardContent>
                        </Card>

                        <div className="flex items-center gap-4 border-t border-neutral-100 pt-6 dark:border-neutral-800">
                            <Button
                                type="submit"
                                disabled={processing}
                                size="lg"
                                className="px-8"
                            >
                                {processing ? (
                                    'Guardando...'
                                ) : (
                                    <>
                                        <Save className="mr-2 h-4 w-4" />
                                        Guardar Cambios
                                    </>
                                )}
                            </Button>

                            {recentlySuccessful && (
                                <p className="flex animate-in items-center gap-1.5 text-sm font-medium text-green-600 dark:text-green-400 fade-in slide-in-from-left-2">
                                    <Check className="h-4 w-4" />
                                    Cambios guardados con éxito
                                </p>
                            )}
                        </div>
                    </form>
                </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
