import { useState, useEffect } from 'react';

/**
 * Hook para debounce de valores
 * Evita que una función se ejecute múltiples veces mientras el valor cambia rápidamente
 * Uso típico: búsqueda en tiempo real, validación de formularios
 *
 * @param value - El valor a debouncear
 * @param delay - Milisegundos de espera antes de actualizar el valor (default: 300ms)
 * @returns Valor debounceado
 */
export function useDebounce<T>(value: T, delay: number = 300): T {
    const [debouncedValue, setDebouncedValue] = useState<T>(value);

    useEffect(() => {
        const handler = setTimeout(() => {
            setDebouncedValue(value);
        }, delay);

        return () => {
            clearTimeout(handler);
        };
    }, [value, delay]);

    return debouncedValue;
}
