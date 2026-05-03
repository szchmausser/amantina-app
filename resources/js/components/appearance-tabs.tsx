import type { LucideIcon } from 'lucide-react';
import { Monitor, Moon, Sun, Droplet, Leaf, Heart, Flame } from 'lucide-react';
import type { HTMLAttributes } from 'react';
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';
import { useColorTheme, type ColorTheme } from '@/hooks/use-color-theme';
import { cn } from '@/lib/utils';

interface ColorThemeOption {
    value: ColorTheme;
    icon: LucideIcon;
    label: string;
    colorClass: string;
}

const colorThemes: ColorThemeOption[] = [
    {
        value: 'blue',
        icon: Droplet,
        label: 'Azul',
        colorClass: 'bg-blue-500',
    },
    {
        value: 'green',
        icon: Leaf,
        label: 'Verde',
        colorClass: 'bg-emerald-500',
    },
    {
        value: 'rose',
        icon: Heart,
        label: 'Rosa',
        colorClass: 'bg-rose-500',
    },
    {
        value: 'amber',
        icon: Flame,
        label: 'Ámbar',
        colorClass: 'bg-amber-500',
    },
];

export default function AppearanceToggleTab({
    className = '',
    ...props
}: HTMLAttributes<HTMLDivElement>) {
    const { appearance, updateAppearance } = useAppearance();
    const { colorTheme, updateColorTheme } = useColorTheme();

    const appearanceModes: {
        value: Appearance;
        icon: LucideIcon;
        label: string;
    }[] = [
        { value: 'light', icon: Sun, label: 'Claro' },
        { value: 'dark', icon: Moon, label: 'Oscuro' },
        { value: 'system', icon: Monitor, label: 'Sistema' },
    ];

    return (
        <div className="space-y-6" {...props}>
            {/* Appearance Mode (Light/Dark/System) */}
            <div className="space-y-3">
                <h3 className="text-sm font-medium text-foreground">
                    Modo de Apariencia
                </h3>
                <div
                    className={cn(
                        'inline-flex gap-1 rounded-lg p-1',
                        'border border-border bg-secondary',
                    )}
                >
                    {appearanceModes.map(({ value, icon: Icon, label }) => (
                        <button
                            key={value}
                            type="button"
                            onClick={() => updateAppearance(value)}
                            className={cn(
                                'flex items-center rounded-md px-3.5 py-1.5 transition-all duration-200',
                                appearance === value
                                    ? 'bg-primary text-primary-foreground shadow-sm'
                                    : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                            )}
                        >
                            <Icon className="-ml-1 h-4 w-4" />
                            <span className="ml-1.5 text-sm font-medium">
                                {label}
                            </span>
                        </button>
                    ))}
                </div>
            </div>

            {/* Color Theme Selection */}
            <div className="space-y-3">
                <h3 className="text-sm font-medium text-foreground">
                    Tema de Color
                </h3>
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    {colorThemes.map(
                        ({ value, icon: Icon, label, colorClass }) => (
                            <button
                                key={value}
                                type="button"
                                onClick={() => updateColorTheme(value)}
                                className={cn(
                                    'flex flex-col items-center gap-2 rounded-lg border-2 p-4 transition-all duration-200',
                                    colorTheme === value
                                        ? 'border-primary bg-primary/5 shadow-sm'
                                        : 'border-border bg-card hover:border-primary/50 hover:bg-accent/50',
                                )}
                            >
                                <div
                                    className={cn(
                                        'flex h-10 w-10 items-center justify-center rounded-full text-white shadow-sm',
                                        colorClass,
                                    )}
                                >
                                    <Icon className="h-5 w-5" />
                                </div>
                                <span
                                    className={cn(
                                        'text-sm font-medium',
                                        colorTheme === value
                                            ? 'text-primary'
                                            : 'text-foreground',
                                    )}
                                >
                                    {label}
                                </span>
                            </button>
                        ),
                    )}
                </div>
            </div>

            {/* Preview Card */}
            <div className="space-y-3">
                <h3 className="text-sm font-medium text-foreground">
                    Vista Previa
                </h3>
                <div className="rounded-lg border border-border bg-card p-4 shadow-sm">
                    <div className="mb-3 flex items-center gap-2">
                        <div className="h-3 w-3 rounded-full bg-primary" />
                        <span className="text-sm font-medium">
                            Tarjeta de Ejemplo
                        </span>
                    </div>
                    <div className="mb-3 flex items-baseline gap-2">
                        <span className="text-2xl font-bold">42.5</span>
                        <span className="text-sm text-muted-foreground">
                            horas acumuladas
                        </span>
                    </div>
                    <div className="h-2 overflow-hidden rounded-full bg-secondary">
                        <div
                            className="h-full rounded-full bg-primary"
                            style={{ width: '65%' }}
                        />
                    </div>
                    <div className="mt-3 flex gap-2">
                        <span className="inline-flex items-center rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">
                            En Progreso
                        </span>
                        <span className="inline-flex items-center rounded-full bg-accent/10 px-2 py-0.5 text-xs font-medium text-accent-foreground">
                            Destacado
                        </span>
                    </div>
                </div>
            </div>
        </div>
    );
}
