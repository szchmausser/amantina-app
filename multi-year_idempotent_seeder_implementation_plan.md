# Multi-Year Idempotent Seeder — Amantina App

Refactorizar el sistema de seeders para que cada ejecución de `CompleteTestDataSeeder` (o el nuevo orquestador) cree un **año escolar completo distinto**, con datos temporalmente realistas, promoviendo automáticamente los alumnos entre años. El objetivo es tener tres años históricos (2023-2024, 2024-2025, 2025-2026) para validar el cálculo de horas acumuladas multi-año y el lapso activo en curso.

---

## Contexto y estado actual

| Elemento | Estado actual |
|---|---|
| Año activo en DB | `2024-2025` (ID=1), lapsos ya vencidos → "Fuera de Período" |
| Alumnos | 335 inscritos en 2024-2025 |
| Jornadas | 179 jornadas, 6 783 asistencias |
| `AcademicYearSeeder` | Hardcodeado a `2024-2025` |
| `SchoolTermSeeder` | Hardcodeado a fechas 2024-2025 |
| `DemoDataSeeder` | Lee el año activo dinámicamente (OK) |
| `FieldSessionsSeeder` | Genera fechas dentro del rango de cada lapso (OK en diseño, requiere tope `now()` para el lapso activo) |
| `CompleteTestDataSeeder` | Orquestador, no controla el año |

El flujo deseado al ejecutar 3 veces:

```
1ª ejecución → crea 2023-2024 (año cerrado)
2ª ejecución → crea 2024-2025 (año cerrado)
3ª ejecución → crea 2025-2026 (año ACTIVO, lapso 3 en curso)
```

---

## Open Questions

> [!IMPORTANT]
> **¿Se ejecuta `migrate:fresh` antes de cada corrida o se preservan los datos?**
> El plan asume `migrate:fresh --seed` para la 1ª ejecución y solo `db:seed --class=YearSeeder` para las siguientes. Confirmar.

> [!IMPORTANT]
> **¿Los mismos alumnos (misma `cedula`/`email`) participan en los 3 años, o se crean alumnos nuevos por año?**
> El plan propone **reutilizar los mismos alumnos** (inscribirlos en el nuevo año), simulando una cohorte real. Los alumnos del 5to año del año anterior no se promueven (egresaron). Confirmar.

> [!IMPORTANT]
> **¿Se generan horas externas para una muestra de alumnos en la 3ª ejecución?**
> El plan incluye un paso opcional de `ExternalHoursSeeder` que asigna horas externas a ~10% de alumnos marcados como `is_transfer = true`. Confirmar si se desea.

---

## Propuesta de Cambios

### Componente 1: Nuevo `AcademicYearConfig` (lógica de calendarios)

Un array de configuración estático centralizado con los 3 años escolares y sus lapsos. Se usa en todos los seeders como fuente de verdad.

```php
// Definición centralizada (puede vivir en el orquestador o en un trait)
const SCHOOL_YEARS = [
    '2023-2024' => [
        'start' => '2023-09-01', 'end' => '2024-07-15', 'required_hours' => 275,
        'is_active' => false,
        'terms' => [
            ['type_order' => 1, 'start' => '2023-09-15', 'end' => '2023-12-15'],
            ['type_order' => 2, 'start' => '2024-01-08', 'end' => '2024-04-12'],
            ['type_order' => 3, 'start' => '2024-04-22', 'end' => '2024-07-15'],
        ],
    ],
    '2024-2025' => [
        'start' => '2024-09-01', 'end' => '2025-07-15', 'required_hours' => 275,
        'is_active' => false,
        'terms' => [
            ['type_order' => 1, 'start' => '2024-09-15', 'end' => '2024-12-15'],
            ['type_order' => 2, 'start' => '2025-01-07', 'end' => '2025-04-10'],
            ['type_order' => 3, 'start' => '2025-04-25', 'end' => '2025-07-15'],
        ],
    ],
    '2025-2026' => [
        'start' => '2025-09-01', 'end' => '2026-07-15', 'required_hours' => 275,
        'is_active' => true,  // ← El único activo
        'terms' => [
            ['type_order' => 1, 'start' => '2025-09-15', 'end' => '2025-12-15'],
            ['type_order' => 2, 'start' => '2026-01-07', 'end' => '2026-04-10'],
            ['type_order' => 3, 'start' => '2026-04-25', 'end' => '2026-07-15'],
            //                                                    ↑ hoy 2026-05-08 cae aquí → Lapso 3 activo ✅
        ],
    ],
];
```

---

### Componente 2: Nuevo `MultiYearSeeder` (orquestador principal)

**[NEW]** `database/seeders/MultiYearSeeder.php`

Reemplaza a `CompleteTestDataSeeder` como el seeder de datos demo. Acepta opcionalmente `--year=2025-2026` como argumento o detecta automáticamente cuál año crear basándose en cuáles ya existen.

**Lógica de detección automática:**
```
años existentes en DB → [] → crear 2023-2024
años existentes en DB → [2023-2024] → crear 2024-2025
años existentes en DB → [2023-2024, 2024-2025] → crear 2025-2026
años existentes en DB → [todos] → error: "Ya existen todos los años"
```

**Flujo interno por año:**
1. Crear `AcademicYear` con configuración del año detectado
2. Crear sus `SchoolTerm` (3 lapsos con fechas del config)
3. Crear estructura: grados y secciones
4. Si es el **1er año (2023-2024):** crear usuarios nuevos (alumnos + profesores)
5. Si es **2do o 3er año:** reutilizar alumnos existentes con lógica de promoción
6. Inscribir alumnos y asignar profesores a secciones
7. Generar jornadas de campo con fechas acotadas al año (y tope `now()` para el año activo)
8. (Solo en el 3er año, opcional) Generar horas externas para alumnos `is_transfer`

---

### Componente 3: Lógica de Promoción entre Años

**Escenario:** al crear el año `N+1`, los alumnos del año `N` se promueven al grado siguiente.

```
Alumnos de 1er Año → 2do Año
Alumnos de 2do Año → 3er Año
...
Alumnos de 4to Año → 5to Año
Alumnos de 5to Año → NO se inscriben (egresaron)
```

**Regla:** Un ~10% de alumnos "repiten" (se quedan en el mismo grado). Esto hace los datos más realistas.

---

### Componente 4: `FieldSessionsSeeder` — Fechas contextuales

#### [MODIFY] `database/seeders/FieldSessionsSeeder.php`

Recibir el `AcademicYear` por parámetro (o leer el activo como ahora). Agregar lógica de tope temporal:

```php
$today = Carbon::now();

// Para cada término:
$termEnd = Carbon::parse($term->end_date);
$effectiveEnd = $termEnd->isPast() ? $termEnd : $today; // tope = hoy si el lapso aún no terminó

$sessionDate = $startDate->copy()->addDays(rand(0, $startDate->diffInDays($effectiveEnd)));
```

Esto garantiza que las jornadas del **Lapso 3 del año activo** (que aún está en curso) solo se generen hasta la fecha actual.

---

### Componente 5: Seeders afectados por el cambio

| Seeder | Cambio requerido |
|---|---|
| `AcademicYearSeeder` | **Desactivar** (absorbido por `MultiYearSeeder`) |
| `SchoolTermSeeder` | **Desactivar** (absorbido por `MultiYearSeeder`) |
| `GradeSeeder` | Recibir `AcademicYear` por parámetro en lugar de leer el activo |
| `SectionSeeder` | Recibir `AcademicYear` por parámetro |
| `DemoDataSeeder` | Recibir `AcademicYear` + pool de alumnos (nuevo o existente) |
| `FieldSessionsSeeder` | Recibir `AcademicYear` + lógica de tope `now()` |
| `CompleteTestDataSeeder` | Mantener como orquestador de fresh setup (usar `MultiYearSeeder` para los 3 años) |

> [!NOTE]
> Los seeders existentes se mantienen funcionales para no romper los tests. Solo se les agrega la capacidad de recibir un año por parámetro. Si no se pasa parámetro, siguen leyendo el año activo (comportamiento actual).

---

### Componente 6: `ExternalHoursSeeder` (nuevo, opcional)

**[NEW]** `database/seeders/ExternalHoursSeeder.php`

Solo se ejecuta en el contexto del año activo (2025-2026). Toma ~10% de los alumnos `is_transfer = true` y les genera 1-3 registros de horas externas con datos realistas (institución, período, horas entre 50 y 200).

---

## Verificación del Plan

### Resultado esperado tras las 3 ejecuciones

| Check | Resultado |
|---|---|
| Años escolares | 3 (2023-2024 cerrado, 2024-2025 cerrado, 2025-2026 activo) |
| Lapso activo detectado | Lapso 3 (2026-04-25 → 2026-07-15) → tarjeta "Lapso Actual" muestra "Lapso 3" |
| Horas por año del alumno | Visibles por año en el dashboard |
| Total histórico | Suma de los 3 años correcta (sin doble conteo) |
| Horas externas | Visibles en perfil de alumnos transferidos |
| Progreso multi-año | Validable en PDF y dashboard |

### Comandos de verificación

```bash
# 1ra corrida (fresh + año 1)
php artisan migrate:fresh --seed
php artisan db:seed --class=MultiYearSeeder

# 2da corrida (año 2, sin migrate:fresh)
php artisan db:seed --class=MultiYearSeeder

# 3ra corrida (año 3, activo)
php artisan db:seed --class=MultiYearSeeder

# Verificar en browser
# Dashboard estudiante → ver horas 2023-2024, 2024-2025, 2025-2026 + total histórico
```

---

## Archivos a crear/modificar

### [NEW] `database/seeders/MultiYearSeeder.php`
### [MODIFY] `database/seeders/DemoDataSeeder.php`
### [MODIFY] `database/seeders/FieldSessionsSeeder.php`
### [MODIFY] `database/seeders/GradeSeeder.php`
### [MODIFY] `database/seeders/SectionSeeder.php`
### [MODIFY] `database/seeders/CompleteTestDataSeeder.php`
### [NEW] `database/seeders/ExternalHoursSeeder.php`
