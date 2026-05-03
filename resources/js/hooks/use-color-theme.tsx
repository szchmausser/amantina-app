import { useSyncExternalStore } from 'react';

export type ColorTheme = 'blue' | 'green' | 'rose' | 'amber';

export type UseColorThemeReturn = {
    readonly colorTheme: ColorTheme;
    readonly updateColorTheme: (theme: ColorTheme) => void;
};

const listeners = new Set<() => void>();
let currentColorTheme: ColorTheme = 'blue';

const setCookie = (name: string, value: string, days = 365): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const getStoredColorTheme = (): ColorTheme => {
    if (typeof window === 'undefined') {
        return 'blue';
    }

    return (localStorage.getItem('color-theme') as ColorTheme) || 'blue';
};

const applyColorTheme = (theme: ColorTheme): void => {
    if (typeof document === 'undefined') {
        return;
    }

    // Remove all existing color theme classes
    const themes: ColorTheme[] = ['blue', 'green', 'rose', 'amber'];
    themes.forEach((t) => {
        document.documentElement.classList.remove(`theme-${t}`);
    });

    // Apply the new theme
    document.documentElement.classList.add(`theme-${theme}`);
};

const subscribe = (callback: () => void) => {
    listeners.add(callback);

    return () => listeners.delete(callback);
};

const notify = (): void => listeners.forEach((listener) => listener());

export function initializeColorTheme(): void {
    if (typeof window === 'undefined') {
        return;
    }

    if (!localStorage.getItem('color-theme')) {
        localStorage.setItem('color-theme', 'blue');
        setCookie('color-theme', 'blue');
    }

    currentColorTheme = getStoredColorTheme();
    applyColorTheme(currentColorTheme);
}

export function useColorTheme(): UseColorThemeReturn {
    const colorTheme: ColorTheme = useSyncExternalStore(
        subscribe,
        () => currentColorTheme,
        () => 'blue',
    );

    const updateColorTheme = (theme: ColorTheme): void => {
        currentColorTheme = theme;

        // Store in localStorage for client-side persistence
        localStorage.setItem('color-theme', theme);

        // Store in cookie for SSR
        setCookie('color-theme', theme);

        applyColorTheme(theme);
        notify();
    };

    return { colorTheme, updateColorTheme } as const;
}
