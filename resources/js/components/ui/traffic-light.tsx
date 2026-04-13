import type { TrafficLightStatus } from '@/types/dashboard';

interface TrafficLightProps {
    status: TrafficLightStatus;
    size?: 'sm' | 'md' | 'lg';
    showLabel?: boolean;
    label?: string;
}

const sizeClasses = {
    sm: 'h-3 w-3',
    md: 'h-4 w-4',
    lg: 'h-5 w-5',
};

const labelSizeClasses = {
    sm: 'text-xs',
    md: 'text-sm',
    lg: 'text-base',
};

const statusConfig: Record<
    TrafficLightStatus,
    { bg: string; text: string; label: string }
> = {
    green: {
        bg: 'bg-emerald-500 dark:bg-emerald-600',
        text: 'text-emerald-600 dark:text-emerald-400',
        label: 'En meta',
    },
    yellow: {
        bg: 'bg-amber-500 dark:bg-amber-600',
        text: 'text-amber-600 dark:text-amber-400',
        label: 'En progreso',
    },
    red: {
        bg: 'bg-red-500 dark:bg-red-600',
        text: 'text-red-600 dark:text-red-400',
        label: 'En riesgo',
    },
};

export function TrafficLight({
    status,
    size = 'md',
    showLabel = false,
    label,
}: TrafficLightProps) {
    const config = statusConfig[status];

    return (
        <div className="flex items-center gap-2">
            <div
                className={`${sizeClasses[size]} ${config.bg} rounded-full shadow-sm`}
                title={config.label}
            />
            {showLabel && (
                <span
                    className={`${labelSizeClasses[size]} ${config.text} font-medium`}
                >
                    {label ?? config.label}
                </span>
            )}
        </div>
    );
}

export function TrafficLightBadge({ status }: { status: TrafficLightStatus }) {
    const config = statusConfig[status];

    return (
        <span
            className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ${config.bg} text-white`}
        >
            <span className={`h-2 w-2 rounded-full bg-white/80`} />
            {config.label}
        </span>
    );
}
