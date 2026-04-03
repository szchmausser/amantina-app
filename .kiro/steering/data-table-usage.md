---
inclusion: manual
---

# Guía de Uso: Sistema de Tablas Reutilizables

Este documento describe cómo usar el sistema de tablas estandarizado en la aplicación Amantina.

## Componentes Disponibles

### 1. DataTable (Componente Principal)
Ubicación: `resources/js/components/ui/data-table.tsx`

**Props:**
- `data: T[]` - Array de datos a mostrar
- `columns: React.ReactNode` - Definición de columnas (usar componentes DataTableHead/Body)
- `pagination?: PaginationInfo` - Información de paginación (opcional)
- `onPageChange?: (page: number, url: string) => void` - Callback para cambio de página
- `perPage?: number` - Cantidad actual de registros por página (opcional)
- `onPerPageChange?: (perPage: number) => void` - Callback para cambio de registros por página (opcional)
- `perPageOptions?: number[]` - Opciones disponibles para selector de registros (ej: [5, 15, 25, 50, 100])
- `searchable?: boolean` - Habilitar búsqueda integrada (opcional, por defecto false)
- `searchPlaceholder?: string` - Placeholder del input de búsqueda
- `searchValue?: string` - Valor actual de búsqueda
- `onSearchChange?: (value: string) => void` - Callback para cambio de búsqueda
- `searchLoading?: boolean` - Mostrar indicador de carga en búsqueda
- `onClearFilters?: () => void` - Callback para limpiar filtros
- `hasFilters?: boolean` - Indica si hay filtros activos
- `emptyMessage?: string` - Mensaje cuando no hay datos
- `className?: string` - Clases CSS adicionales

### 2. Componentes de Estructura

#### DataTableHead
Envuelve el `<thead>` de la tabla.

```tsx
<DataTableHead>
  <DataTableTH>Columna 1</DataTableTH>
  <DataTableTH>Columna 2</DataTableTH>
</DataTableHead>
```

#### DataTableTH
Define una celda de encabezado (`<th>`).

**Props:**
- `className?: string` - Clases CSS adicionales

#### DataTableBody
Envuelve el `<tbody>` de la tabla.

```tsx
<DataTableBody>
  {data.map(item => (
    <DataTableTR key={item.id}>
      <DataTableTD>{item.name}</DataTableTD>
    </DataTableTR>
  ))}
</DataTableBody>
```

#### DataTableTR
Define una fila (`<tr>`).

**Props:**
- `className?: string` - Clases CSS adicionales
- `onClick?: () => void` - Callback para click en la fila (hace la fila clickeable)

#### DataTableTD
Define una celda de datos (`<td>`).

**Props:**
- `className?: string` - Clases CSS adicionales

### 3. TableFilters (Componente de Filtros)
Ubicación: `resources/js/components/ui/table-filters.tsx`

Componente reutilizable para la barra de filtros encima de la tabla.

**Props:**
- `searchValue: string` - Valor actual de búsqueda
- `onSearchChange: (value: string) => void` - Callback para cambio de búsqueda
- `searchPlaceholder?: string` - Placeholder del input
- `searchLoading?: boolean` - Indicador de carga
- `filterSelect?: React.ReactNode` - Selector de filtro adicional (ej: rol, estado)
- `hasFilters: boolean` - Indica si hay filtros activos
- `onClearFilters: () => void` - Callback para limpiar filtros
- `createButton?: React.ReactNode` - Botón de crear (opcional)

## Ejemplo Completo: Tabla de Usuarios

```tsx
import { Head, Link, router } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import { Edit, Eye, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { useDebounce } from '@/hooks/use-debounce';
import {
    DataTable,
    DataTableHead,
    DataTableTH,
    DataTableBody,
    DataTableTR,
    DataTableTD,
    type PaginationInfo,
} from '@/components/ui/data-table';
import { TableFilters } from '@/components/ui/table-filters';

interface Props {
    users: {
        data: User[];
        links: PaginationLink[];
        total: number;
        current_page: number;
        last_page: number;
    };
    filters: {
        search?: string;
        role?: string;
    };
}

export default function Index({ users, filters }: Props) {
    // 1. Estado para filtros
    const [search, setSearch] = useState(filters.search || '');
    const [role, setRole] = useState(filters.role || 'all');
    const [isSearching, setIsSearching] = useState(false);
    const isFirstRender = useRef(true);

    // 2. Debounce para búsqueda
    const debouncedSearch = useDebounce(search, 300);

    // 3. Efecto para aplicar filtros
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }

        router.get(
            '/admin/users',
            {
                search: debouncedSearch || undefined,
                role: role === 'all' ? undefined : role,
            },
            {
                preserveState: true,
                replace: true,
                onFinish: () => setIsSearching(false),
            },
        );
        setIsSearching(true);
    }, [debouncedSearch, role]);

    // 4. Preparar paginación
    const pagination: PaginationInfo | undefined =
        users.last_page > 1
            ? {
                  links: users.links,
                  total: users.total,
                  current_page: users.current_page,
                  last_page: users.last_page,
              }
            : undefined;

    // 5. Definir columnas
    const tableColumns = (
        <>
            <DataTableHead>
                <DataTableTH className="w-16">#</DataTableTH>
                <DataTableTH>Nombre</DataTableTH>
                <DataTableTH>Email</DataTableTH>
                <DataTableTH className="w-24 text-right">Acciones</DataTableTH>
            </DataTableHead>
            <DataTableBody>
                {users.data.map((user, index) => (
                    <DataTableTR key={user.id}>
                        <DataTableTD className="font-mono text-xs text-neutral-400">
                            {(users.current_page - 1) * users.data.length + index + 1}
                        </DataTableTD>
                        <DataTableTD>
                            <Link href={`/admin/users/${user.id}`}>
                                {user.name}
                            </Link>
                        </DataTableTD>
                        <DataTableTD>{user.email}</DataTableTD>
                        <DataTableTD className="text-right">
                            <Button variant="ghost" size="icon" asChild>
                                <Link href={`/admin/users/${user.id}/edit`}>
                                    <Edit className="h-4 w-4" />
                                </Link>
                            </Button>
                        </DataTableTD>
                    </DataTableTR>
                ))}
            </DataTableBody>
        </>
    );

    // 6. Selector de filtro adicional (opcional)
    const roleFilterSelect = (
        <Select value={role} onValueChange={setRole}>
            <SelectTrigger className="h-10 w-full sm:w-44">
                <SelectValue placeholder="Filtrar por rol" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="all">Todos los roles</SelectItem>
                <SelectItem value="admin">Admin</SelectItem>
                <SelectItem value="profesor">Profesor</SelectItem>
            </SelectContent>
        </Select>
    );

    // 7. Botón de crear (opcional)
    const createButton = (
        <Button asChild className="sm:ml-auto">
            <Link href="/admin/users/create">
                <Plus className="mr-2 h-4 w-4" />
                Nuevo Usuario
            </Link>
        </Button>
    );

    return (
        <AppLayout>
            <Head title="Usuarios" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 lg:p-8">
                <div>
                    <h1 className="text-2xl font-bold">Usuarios</h1>
                    <p className="mt-1 text-sm text-neutral-500">
                        Administra las cuentas de usuario del sistema.
                    </p>
                </div>

                {/* Filtros */}
                <TableFilters
                    searchValue={search}
                    onSearchChange={setSearch}
                    searchPlaceholder="Buscar por nombre o email..."
                    searchLoading={isSearching}
                    filterSelect={roleFilterSelect}
                    hasFilters={Boolean(search || role !== 'all')}
                    onClearFilters={() => {
                        setSearch('');
                        setRole('all');
                    }}
                    createButton={createButton}
                />

                {/* Tabla */}
                <DataTable
                    data={users.data}
                    columns={tableColumns}
                    pagination={pagination}
                    onPageChange={(page, url) => {
                        router.get(url, {
                            search: search || undefined,
                            role: role === 'all' ? undefined : role,
                        }, {
                            preserveState: true,
                            replace: true,
                        })
                    }}
                    emptyMessage="No se encontraron usuarios."
                />
            </div>
        </AppLayout>
    );
}
```

## Ejemplo Simplificado: Tabla Sin Filtros

```tsx
export default function SimpleTable({ items }: Props) {
    const tableColumns = (
        <>
            <DataTableHead>
                <DataTableTH>Nombre</DataTableTH>
                <DataTableTH>Descripción</DataTableTH>
            </DataTableHead>
            <DataTableBody>
                {items.data.map((item) => (
                    <DataTableTR key={item.id}>
                        <DataTableTD>{item.name}</DataTableTD>
                        <DataTableTD>{item.description}</DataTableTD>
                    </DataTableTR>
                ))}
            </DataTableBody>
        </>
    );

    const pagination: PaginationInfo | undefined =
        items.last_page > 1
            ? {
                  links: items.links,
                  total: items.total,
                  current_page: items.current_page,
                  last_page: items.last_page,
              }
            : undefined;

    return (
        <DataTable
            data={items.data}
            columns={tableColumns}
            pagination={pagination}
            onPageChange={(_, url) => router.get(url)}
            emptyMessage="No hay elementos disponibles."
        />
    );
}
```

## Backend: Preparar Datos para la Tabla

### Controlador Laravel

```php
public function index(Request $request)
{
    $search = $request->query('search');
    $role = $request->query('role');

    $users = User::query()
        ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%"))
        ->when($role, fn($q) => $q->role($role))
        ->paginate(15)
        ->withQueryString(); // Importante: preserva query params en paginación

    return Inertia::render('admin/users/index', [
        'users' => $users,
        'filters' => [
            'search' => $search,
            'role' => $role,
        ],
    ]);
}
```

## Características Incluidas

✅ **Paginación automática** con controles completos:
- Botones primera/anterior/siguiente/última página
- Números de página visibles
- Selector rápido de página (se abre hacia arriba)
- Información de resultados

✅ **Búsqueda con debounce** (300ms)

✅ **Filtros personalizables** (selectores, checkboxes, etc.)

✅ **Botón de limpiar filtros**

✅ **Responsive** (se adapta a móviles)

✅ **Dark mode** compatible

✅ **Estados vacíos** con mensajes personalizables

✅ **Indicadores de carga** durante búsqueda

✅ **Preservación de estado** en navegación

## Estilos y Personalización

### Anchos de Columna
```tsx
<DataTableTH className="w-16">#</DataTableTH>        // Ancho fijo pequeño
<DataTableTH className="w-32">Cédula</DataTableTH>  // Ancho fijo mediano
<DataTableTH>Nombre</DataTableTH>                    // Ancho flexible
```

### Alineación
```tsx
<DataTableTH className="text-right">Acciones</DataTableTH>
<DataTableTD className="text-center">Estado</DataTableTD>
```

### Colores y Tipografía
```tsx
<DataTableTD className="font-mono text-xs text-neutral-400">
    {user.id}
</DataTableTD>
```

## Mejores Prácticas

1. **Siempre usar `withQueryString()`** en el backend para preservar filtros en paginación
2. **Usar `useDebounce`** para búsquedas (evita llamadas excesivas)
3. **Preservar estado** con `preserveState: true` en router.get
4. **Usar `replace: true`** para no llenar el historial del navegador
5. **Validar permisos** antes de mostrar botones de acción
6. **Mensajes descriptivos** en estados vacíos

## Notas Importantes

- El selector de páginas se abre hacia arriba (`side="top"`) para evitar chocar con el borde inferior
- La paginación solo se muestra si hay más de 1 página
- Los filtros se preservan automáticamente al cambiar de página
- El componente es completamente tipado con TypeScript
