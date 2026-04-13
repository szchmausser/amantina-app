import { cn } from '@/lib/utils';
import { TrafficLight } from './traffic-light';
import type { TrafficLightStatus } from '@/types/dashboard';

interface ProgressCardProps {
    title: string;
    currentHours: number;
    quota: number;
    status: TrafficLightStatus;
    subtitle?: string;
    showProgress?: boolean;
    className?: string;
}

export function ProgressCard({
    title,
    currentHours,
    quota,
    status,
    subtitle,
    showProgress = true,
    className,
}: ProgressCardProps) {
    const percentage = quota > 0 ? Math.min((currentHours / quota) * 100, 100) : 0;

    const statusColors: Record<TrafficLightStatus, string> = {
        green: 'border-emerald-200 dark:border-emerald-800',
        yellow: 'border-amber-200 dark:border-amber-800',
        red: 'border-red-200 dark:border-red-800',
    };

    return (
        <div
            className={cn(
                'rounded-xl border bg-card p-4 shadow-sm',
                statusColors[status],
                className
            )}
        >
            <div className="mb-3 flex items-start justify-between">
                <div>
                    <h3 className="font-medium text-foreground">{title}</h3>
                    {subtitle && (
                        <p className="mt-0.5 text-sm text-muted-foreground">
                            {subtitle}
                        </p>
                    )}
                </div>
                <TrafficLight status={status} />
            </div>

            <div className="space-y-2">
                <div className="flex items-baseline justify-between">
                    <span className="text-2xl font-bold tabular-nums text-foreground">
                        {currentHours.toFixed(1)}
                    </span>
                    <span className="text-sm text-muted-foreground">
                        / {quota} horas
                    </span>
                </div>

                {showProgress && (
                    <div className="space-y-1">
                        <div className="h-2 overflow-hidden rounded-full bg-muted">
                            <div
                                className={cn(
                                    'h-full rounded-full transition-all duration-500',
                                    status === 'green' &&
                                        'bg-emerald-500 dark:bg-emerald-600',
                                    status === 'yellow' &&
                                        'bg-amber-500 dark:bg-amber-600',
                                    status === 'red' &&
                                        'bg-red-500 dark:bg-red-600'
                                )}
                                style={{ width: `${percentage}%` }}
                            />
                        </div>
                        <p className="text-xs text-muted-foreground">
                            {percentage.toFixed(1)}% completado
                        </p>
                    </div>
                )}
            </div>
        </div>
    );
}
