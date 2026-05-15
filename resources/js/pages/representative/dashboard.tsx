import { Head, Link } from '@inertiajs/react';
import { Calendar, User } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent } from '@/components/ui/card';
import { TrafficLightBadge } from '@/components/ui/traffic-light';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import type { RepresentativeDashboardData } from '@/types/dashboard';

interface Props {
    activeYear: {
        id: number;
        name: string;
        requiredHours: number;
    } | null;
    students: RepresentativeDashboardData['students'];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Panel del Representante',
        href: '/representative/dashboard',
    },
];

const progressBarColor = (status: string): string => {
    if (status === 'green') return 'bg-emerald-500';
    if (status === 'yellow') return 'bg-amber-500';
    return 'bg-red-500';
};

export default function RepresentativeDashboard({
    activeYear,
    students,
}: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Panel del Representante" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-y-auto p-4 lg:p-8">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">
                            Panel del Representante
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Seguimiento de horas de tus representados
                        </p>
                    </div>
                    {activeYear && (
                        <div className="flex items-center gap-2 rounded-lg border bg-card px-4 py-2 text-sm">
                            <Calendar className="h-4 w-4 text-muted-foreground" />
                            <span className="font-medium">
                                {activeYear.name}
                            </span>
                        </div>
                    )}
                </div>

                {/* Empty state */}
                {students.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center gap-4 py-24">
                        <User className="h-16 w-16 text-muted-foreground" />
                        <div className="text-center">
                            <h1 className="text-xl font-semibold">
                                Sin estudiantes asignados
                            </h1>
                            <p className="mt-2 text-muted-foreground">
                                No se encontraron estudiantes asociados a tu cuenta.
                                <br />
                                Contacta al administrador del sistema.
                            </p>
                        </div>
                    </div>
                ) : (
                    /* Student cards */
                    <div className="flex flex-col gap-4">
                        {students.map((student) => (
                            <Link
                                key={student.id}
                                href={`/representative/student/${student.id}/dashboard`}
                                className="block"
                            >
                                <Card className="rounded-xl border transition-colors hover:bg-accent">
                                    <CardContent className="p-5">
                                        {/* Top row: name + grade/section + traffic light */}
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0 flex-1">
                                                <div className="flex flex-wrap items-baseline gap-x-2">
                                                    <span className="font-semibold">
                                                        {student.name}
                                                    </span>
                                                </div>

                                                {/* Student details: cedula + email */}
                                                <div className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-sm text-muted-foreground">
                                                    {student.cedula && (
                                                        <span>C.I. {student.cedula}</span>
                                                    )}
                                                    {student.email && (
                                                        <span className="truncate">{student.email}</span>
                                                    )}
                                                </div>

                                                {/* Grade and section for current year */}
                                                {(student.gradeName ||
                                                    student.sectionName) && (
                                                    <div className="mt-1 text-sm text-muted-foreground">
                                                        {student.gradeName}
                                                        {student.sectionName &&
                                                            ` — Sección ${student.sectionName}`}
                                                    </div>
                                                )}

                                                {/* Progress bar */}
                                                <div className="mt-2.5 h-2.5 overflow-hidden rounded-full bg-muted">
                                                    <div
                                                        className={`h-full rounded-full ${progressBarColor(student.status)} transition-all`}
                                                        style={{
                                                            width: `${Math.min(student.percentage, 100)}%`,
                                                        }}
                                                    />
                                                </div>

                                                {/* Hours / quota + percentage */}
                                                <p className="mt-1.5 text-sm tabular-nums">
                                                    <span className="font-medium">
                                                        {student.hours.toFixed(
                                                            1,
                                                        )}
                                                        h
                                                    </span>
                                                    <span className="text-muted-foreground">
                                                        {' '}
                                                        / {student.quota}h
                                                    </span>
                                                    <span className="ml-2 text-muted-foreground">
                                                        {student.percentage.toFixed(
                                                            0,
                                                        )}
                                                        %
                                                    </span>
                                                </p>

                                                {/* Next session */}
                                                {student.nextSession && (
                                                    <div className="mt-2 flex items-center gap-1.5 text-xs text-muted-foreground">
                                                        <Calendar className="h-3 w-3" />
                                                        <span>
                                                            Próx. sesión:{' '}
                                                            {new Date(
                                                                student.nextSession.date,
                                                            ).toLocaleDateString(
                                                                'es-ES',
                                                            )}
                                                            {' · '}
                                                            {
                                                                student
                                                                    .nextSession
                                                                    .location
                                                            }
                                                        </span>
                                                    </div>
                                                )}
                                            </div>

                                            {/* Traffic light badge */}
                                            <TrafficLightBadge
                                                status={student.status}
                                            />
                                        </div>
                                    </CardContent>
                                </Card>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
