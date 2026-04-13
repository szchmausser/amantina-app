import { cn } from '@/lib/utils';
import { Card, CardContent, CardHeader, CardTitle } from './card';

interface StatCardProps {
    title: string;
    value: string | number;
    description?: string;
    icon?: React.ReactNode;
    trend?: {
        value: number;
        isPositive: boolean;
    };
    className?: string;
}

export function StatCard({
    title,
    value,
    description,
    icon,
    trend,
    className,
}: StatCardProps) {
    return (
        <Card className={cn('', className)}>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium text-muted-foreground">
                    {title}
                </CardTitle>
                {icon && (
                    <div className="text-muted-foreground">{icon}</div>
                )}
            </CardHeader>
            <CardContent>
                <div className="flex items-baseline gap-2">
                    <span className="text-2xl font-bold tabular-nums">
                        {value}
                    </span>
                    {trend && (
                        <span
                            className={cn(
                                'text-xs font-medium',
                                trend.isPositive
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-red-600 dark:text-red-400'
                            )}
                        >
                            {trend.isPositive ? '+' : ''}
                            {trend.value}%
                        </span>
                    )}
                </div>
                {description && (
                    <p className="mt-1 text-xs text-muted-foreground">
                        {description}
                    </p>
                )}
            </CardContent>
        </Card>
    );
}

interface StatGridProps {
    children: React.ReactNode;
    columns?: 2 | 3 | 4;
    className?: string;
}

export function StatGrid({
    children,
    columns = 4,
    className,
}: StatGridProps) {
    const gridCols = {
        2: 'grid-cols-1 sm:grid-cols-2',
        3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
        4: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
    };

    return (
        <div className={cn('grid gap-4', gridCols[columns], className)}>
            {children}
        </div>
    );
}
