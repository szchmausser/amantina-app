import * as React from 'react';
import { Check, ChevronsUpDown, Plus, Search, X } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

interface ComboboxInputProps {
    value: string;
    onChange: (value: string) => void;
    onCreateNew?: (value: string) => void;
    options: { value: string; label: string }[];
    placeholder?: string;
    emptyMessage?: string;
    disabled?: boolean;
    id?: string;
}

export function ComboboxInput({
    value,
    onChange,
    onCreateNew,
    options,
    placeholder = 'Seleccionar o escribir...',
    emptyMessage = 'No se encontraron opciones.',
    disabled = false,
    id,
}: ComboboxInputProps) {
    const [open, setOpen] = React.useState(false);
    const [search, setSearch] = React.useState(value);
    const inputRef = React.useRef<HTMLInputElement>(null);

    React.useEffect(() => {
        setSearch(value);
    }, [value]);

    React.useEffect(() => {
        if (open && inputRef.current) {
            inputRef.current.focus();
        }
    }, [open]);

    const filteredOptions = options.filter((opt) =>
        opt.label.toLowerCase().includes(search.toLowerCase()),
    );

    const hasMatch = options.some(
        (opt) => opt.value.toLowerCase() === search.toLowerCase(),
    );

    const handleSelect = (selectedValue: string) => {
        onChange(selectedValue);
        setSearch(selectedValue);
        setOpen(false);
    };

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const newValue = e.target.value;
        setSearch(newValue);
        onChange(newValue);
    };

    const handleClear = () => {
        setSearch('');
        onChange('');
        inputRef.current?.focus();
    };

    const handleCreateNew = () => {
        if (search.trim() && onCreateNew) {
            onCreateNew(search.trim());
            setOpen(false);
        }
    };

    return (
        <DropdownMenu open={open} onOpenChange={setOpen}>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className={cn(
                        'w-full justify-between font-normal',
                        !search && 'text-muted-foreground',
                    )}
                    disabled={disabled}
                    id={id}
                >
                    <span className="truncate">
                        {search || placeholder}
                    </span>
                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent className="w-[--radix-dropdown-menu-trigger-width] p-0" align="start">
                <div className="flex items-center border-b px-2">
                    <Search className="mr-2 h-4 w-4 shrink-0 opacity-50" />
                    <Input
                        ref={inputRef}
                        value={search}
                        onChange={handleInputChange}
                        placeholder={placeholder}
                        className="h-9 border-0 bg-transparent px-0 py-3 text-sm shadow-none focus-visible:ring-0"
                        onKeyDown={(e) => {
                            if (e.key === 'Enter' && search.trim() && !hasMatch) {
                                e.preventDefault();
                                handleCreateNew();
                            }
                        }}
                    />
                    {search && (
                        <button
                            type="button"
                            className="ml-1 rounded-sm opacity-70 hover:opacity-100"
                            onClick={handleClear}
                        >
                            <X className="h-4 w-4" />
                            <span className="sr-only">Limpiar</span>
                        </button>
                    )}
                </div>
                <div className="max-h-48 overflow-y-auto py-1">
                    {filteredOptions.length > 0 ? (
                        filteredOptions.map((option) => (
                            <DropdownMenuItem
                                key={option.value}
                                onSelect={() => handleSelect(option.value)}
                                className="flex items-center justify-between"
                            >
                                <span>{option.label}</span>
                                {option.value.toLowerCase() ===
                                    search.toLowerCase() && (
                                    <Check className="ml-2 h-4 w-4" />
                                )}
                            </DropdownMenuItem>
                        ))
                    ) : (
                        <div className="px-2 py-1.5 text-sm text-muted-foreground">
                            {onCreateNew && search.trim() ? (
                                <button
                                    type="button"
                                    className="flex w-full items-center gap-2 text-left text-foreground hover:underline"
                                    onClick={handleCreateNew}
                                >
                                    <Plus className="h-4 w-4" />
                                    Crear "{search}"
                                </button>
                            ) : (
                                emptyMessage
                            )}
                        </div>
                    )}
                    {filteredOptions.length > 0 &&
                        search.trim() &&
                        !hasMatch &&
                        onCreateNew && (
                            <div className="border-t px-1 py-1">
                                <DropdownMenuItem
                                    onSelect={handleCreateNew}
                                    className="flex items-center gap-2 text-muted-foreground"
                                >
                                    <Plus className="h-4 w-4" />
                                    Crear "{search}"
                                </DropdownMenuItem>
                            </div>
                        )}
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
