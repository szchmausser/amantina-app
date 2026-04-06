# Amantina App

## Guía de Implementación por Hitos

**Sistema Bitácora Socioproductiva**

Cada hito entrega una funcionalidad completa de punta a punta.
Los pasos son reproducibles y sirven como referencia para nuevos desarrolladores.
Stack: Laravel 12 · React 19 · TypeScript · Inertia.js · PostgreSQL · Spatie

### Convenciones de este documento

Cada hito está organizado de la misma forma para facilitar la lectura y la replicación:

| Elemento          | Qué indica                                           |
| ----------------- | ---------------------------------------------------- |
| Contexto          | Por qué existe este hito y qué problema resuelve     |
| Prerrequisitos    | Qué debe estar hecho antes de empezar                |
| Entidades creadas | Modelos, tablas y relaciones                         |
| Backend           | Controladores, requests, policies, middleware        |
| Frontend          | Páginas React, componentes, rutas                    |
| Roles y Permisos  | Permisos nuevos, roles que los heredan, protecciones |
| Datos de prueba   | Seeders, factories                                   |
| Tests             | Feature tests creados                                |
| ENTREGA           | Lo que debe existir y funcionar al finalizar el hito |

> **NOTA:** Los bloques de código asumen que el directorio de trabajo es la raíz del proyecto (`amantina-app/`) salvo que se indique explícitamente lo contrario.

---

# Plan de Desarrollo

El desarrollo se organiza en hitos verticales. Cada hito entrega una funcionalidad completamente operativa de punta a punta: migración, modelo, validaciones, seeders, factories, controlador y UI. No se crean todas las migraciones al inicio. El principio es que al finalizar cada hito el sistema tenga algo nuevo y funcional que se pueda usar, probar y validar antes de continuar.

| Prerequisito antes del Hito 0: PHP 8.2+, Composer, Node.js 20+, npm y PostgreSQL instalados en el entorno de desarrollo. |
| ------------------------------------------------------------------------------------------------------------------------ |

## Resumen de Hitos

| Hito | Título                           | Enfoque Principal                               | Estado |
| ---- | -------------------------------- | ----------------------------------------------- | ------ |
| 0    | Instalación y esqueleto base     | Setup inicial, auth base, Spatie                | ✅     |
| 1    | Usuarios y datos institucionales | Tabla users, Institution, registro público      | ✅     |
| 2    | Roles técnicos (Spatie)          | Definición de roles base para Auth              | ✅     |
| 3    | Autenticación personalizada      | Login con contexto y multi-rol                  | ✅     |
| 4    | CRUD de usuarios y RBAC          | Gestión integral, permisos, seguridad           | ✅     |
| 5    | Estructura académica             | Años, lapsos, tipos de lapso, grados, secciones | ✅     |
| 6    | Inscripciones y asignaciones     | Vínculo académico, promoción masiva             | ✅     |
| 7    | Representantes                   | Vínculo familiar (asignación desde perfil)      | ✅     |
| 8    | Información de salud             | Condiciones médicas y soportes                  | ✅     |
| 9    | Catálogos de configuración       | Actividades y ubicaciones                       | ✅     |
| 10   | Jornadas de campo                | Registro central de actividades                 | ✅     |
| 11   | Asistencia y subactividades      | Acreditación de horas y evidencias              | ✅     |
| 12   | Horas externas                   | Acreditación para transferidos                  | 🔲     |
| 13   | Acumulados y dashboards          | Progreso visual y KPIs                          | 🔲     |
| 14   | Reportes en PDF                  | Generación de certificados y listados           | 🔲     |
| 15   | Revisión y estabilización        | QA final y seeders demo                         | 🔲     |

> **Nota de progreso (2026-04-05):**
>
> - Hitos 0-11 completados
> - Hito 7 simplificado: solo asignación de representantes desde perfil de estudiante
> - Hito 8 completado: Información de salud con archivos adjuntos y eliminación en cascada
> - Hito 9 completado: Catálogos de configuración (actividades y ubicaciones) con CRUD para admin y profesor
> - Hito 10 completado: Jornadas de campo con registro central de actividades
> - Hito 11 completado: Asistencia y subactividades con ownership, alertas de horas, asignación rápida y gestión completa de evidencias
> - Próximos hitos pendientes: 12-15

---

## Estadísticas Actuales del Proyecto

| Categoría          | Cantidad                                                   |
| ------------------ | ---------------------------------------------------------- |
| Modelos            | 15                                                         |
| Controladores      | 20 (1 base + 16 admin + 3 settings)                        |
| Form Requests      | 26 (22 admin + 4 settings)                                 |
| Policies           | 3 (UserPolicy, AttendancePolicy, AttendanceActivityPolicy) |
| Middleware custom  | 3                                                          |
| Migraciones        | 25                                                         |
| Seeders            | 15                                                         |
| Factories          | 11                                                         |
| Feature Tests      | 30                                                         |
| Páginas React      | 43                                                         |
| Componentes UI     | 28                                                         |
| Permisos definidos | 54                                                         |
| Roles definidos    | 4 (admin, profesor, alumno, representante)                 |

---

### Hito 0 — Instalación y esqueleto base

**Estado:** Finalizado ✅

Punto de partida del proyecto. Todo lo que se instala aquí es prerrequisito para todos los hitos siguientes.

#### Entorno

| Herramienta  | Versión | Verificar con    |
| ------------ | ------- | ---------------- |
| PHP          | 8.4     | `php -v`         |
| Composer     | 2.x     | `composer -V`    |
| Node.js      | 20.x    | `node -v`        |
| npm          | 10.x    | `npm -v`         |
| PostgreSQL   | 18      | `psql --version` |
| Laravel Herd | latest  | `herd --version` |

#### Paquetes instalados

- `spatie/laravel-permission` — Control de roles y permisos (RBAC)
- `spatie/laravel-medialibrary` — Gestión unificada de archivos adjuntos
- `laravel/fortify` — Autenticación headless
- `laravel/boost` — Servidor MCP para agentes IA
- `askedio/laravel-soft-cascade` — Eliminación suave en cascada

#### Tablas creadas

- `users`, `password_reset_tokens`, `sessions`, `cache`, `jobs` (Laravel core)
- `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` (Spatie)
- `media` (Spatie Media Library)

#### ENTREGA

- Proyecto corriendo en Laravel Herd con PostgreSQL conectado
- Login del starter kit operativo
- Spatie Permissions y Media Library instalados y configurados
- Modelo `User` con traits `HasRoles` e `InteractsWithMedia`

#### Roles y Permisos

> **Sin permisos en este hito.** Solo se instala la infraestructura de Spatie Permissions. Las tablas `roles`, `permissions`, `model_has_roles`, etc. existen pero están vacías. Los permisos se definen en el Hito 2.

---

### Hito 1 — Usuarios y Datos Institucionales

**Estado:** Finalizado ✅

Extiende la tabla `users` del starter kit con los campos del sistema e introduce la entidad `Institution` (datos de la sede escolar).

#### Entidades creadas

| Entidad            | Tabla                    | Campos clave                                                                                 |
| ------------------ | ------------------------ | -------------------------------------------------------------------------------------------- |
| `User` (extendido) | `users`                  | `cedula`, `phone`, `address`, `is_active`, `is_transfer`, `institution_origin`, `deleted_at` |
| `Institution`      | `institution` (singular) | `name`, `address`, `email`, `phone`, `code`                                                  |

#### Backend

- **Modelo User:** `$fillable` extendido, `casts` para booleanos, trait `SoftDeletes`
- **Modelo Institution:** `$table = 'institution'`, fillable básico
- **`app/Concerns/ProfileValidationRules.php`:** Trait con reglas de validación reutilizables (cedula única, campos condicionales para transferidos)
- **`app/Actions/Fortify/CreateNewUser.php`:** Intercepción del registro público para auto-asignar `institution_origin` si no es transferido
- **Migración de constraints parciales:** `fix_users_unique_constraints_for_soft_deletes` — índices únicos parciales para permitir re-registro tras soft delete

#### Frontend

- **`resources/js/pages/auth/register.tsx`:** Formulario de autoregistro con campos condicionales (institution_origin solo si es transferido)
- **`resources/js/pages/settings/institution.tsx`:** Formulario de edición de datos institucionales
- **`resources/js/layouts/settings/layout.tsx`:** Menú lateral de settings con enlace a "Datos Institucionales"

#### Datos de prueba

- **UserSeeder:** Admin raíz (`admin@amantina.test`)
- **InstitutionSeeder:** Registro por defecto "Amantina de Sucre"
- **UserFactory:** Campos completos para tests

#### Tests

- `tests/Feature/Auth/RegistrationTest.php` — Registro público, transferidos, campos obligatorios
- `tests/Feature/Settings/InstitutionTest.php` — CRUD de institución

#### ENTREGA

- Tabla `users` con todos los campos del sistema
- Entidad `Institution` con registro por defecto
- Autoregistro público funcional con lógica de transferencia
- Página de settings para editar datos institucionales
- Seeder de admin funcional

#### Roles y Permisos

> **Sin permisos todavía.** La página de "Datos Institucionales" es visible para cualquier usuario autenticado. Esto es temporal y se restringe en el Hito 2.

| Ruta                    | Acceso actual  | Acceso esperado (Hito 2)    |
| ----------------------- | -------------- | --------------------------- |
| `/settings/institution` | Cualquier auth | Solo `admin`                |
| `/register`             | Público        | Público (solo rol `alumno`) |

---

### Hito 2 — Roles Técnicos (Spatie)

**Estado:** Finalizado ✅

Define la infraestructura técnica de roles para permitir la lógica de "Login con Contexto".

#### Roles creados

| Rol             | Jerarquía  | Descripción                                     |
| --------------- | ---------- | ----------------------------------------------- |
| `admin`         | 1 (máxima) | Control total del sistema                       |
| `profesor`      | 2          | Registra jornadas, toma asistencia, carga horas |
| `alumno`        | 3          | Solo lectura de su propio historial             |
| `representante` | 4 (mínima) | Solo lectura del representado                   |

#### Backend

- **`database/seeders/RoleAndPermissionSeeder.php`:** Crea los 4 roles base y 46 permisos organizados por módulo
- **Permisos definidos:** `users.*`, `roles.*`, `permissions.*`, `academic_years.*`, `school_terms.*`, `grades.*`, `sections.*`, `enrollments.*`, `assignments.*`, `academic_info.view`, `health_conditions.*`, `student_health.*`
- **Asignación:** `admin` recibe los 46 permisos; `profesor` recibe `users.view`, `enrollments.view`, `assignments.view`, `academic_info.view`

#### ENTREGA

- 4 roles base creados y seedeados
- 46 permisos definidos y organizados por módulo
- Admin con todos los permisos asignados

#### Roles y Permisos

Este hito es **exclusivamente** de infraestructura de permisos. Se definen TODOS los permisos del sistema de una vez, organizados por módulo:

**Permisos creados (46 en total):**

| Módulo            | Permisos                                                                                                   |
| ----------------- | ---------------------------------------------------------------------------------------------------------- |
| Usuarios          | `users.view`, `users.create`, `users.edit`, `users.delete`                                                 |
| Roles             | `roles.view`, `roles.create`, `roles.edit`, `roles.delete`                                                 |
| Permisos          | `permissions.view`, `permissions.create`, `permissions.edit`, `permissions.delete`                         |
| Años académicos   | `academic_years.view`, `academic_years.create`, `academic_years.edit`, `academic_years.delete`             |
| Lapsos            | `school_terms.view`, `school_terms.create`, `school_terms.edit`, `school_terms.delete`                     |
| Grados            | `grades.view`, `grades.create`, `grades.edit`, `grades.delete`                                             |
| Secciones         | `sections.view`, `sections.create`, `sections.edit`, `sections.delete`                                     |
| Inscripciones     | `enrollments.view`, `enrollments.create`, `enrollments.edit`, `enrollments.delete`                         |
| Asignaciones      | `assignments.view`, `assignments.create`, `assignments.edit`, `assignments.delete`                         |
| Info académica    | `academic_info.view`                                                                                       |
| Condiciones salud | `health_conditions.view`, `health_conditions.create`, `health_conditions.edit`, `health_conditions.delete` |
| Salud estudiantes | `student_health.view`, `student_health.create`, `student_health.edit`, `student_health.delete`             |

**Asignación por rol:**

| Rol             | Permisos heredados                                                         |
| --------------- | -------------------------------------------------------------------------- |
| `admin`         | **Todos los 46 permisos**                                                  |
| `profesor`      | `users.view`, `enrollments.view`, `assignments.view`, `academic_info.view` |
| `alumno`        | Ninguno (solo lectura de su propio perfil)                                 |
| `representante` | Ninguno (solo lectura de su representado)                                  |

> **NOTA:** Los permisos de hitos futuros (9-14) se agregarán progresivamente al `RoleAndPermissionSeeder` cuando se implementen. Por ahora solo existen los listados arriba.

---

### Hito 3 — Autenticación Personalizada

**Estado:** Finalizado ✅

Implementa login con contexto de rol y soporte multi-rol.

#### Backend

- **Middleware `EnsureRoleContext`:** Inicializa `active_role` en sesión basado en jerarquía (admin > profesor > alumno > representante)
- **Parámetro `context` en login:** Permite elegir rol al autenticarse si el usuario tiene múltiples roles
- **Validación de contexto:** El backend rechaza contextos no autorizados
- **`HandleInertiaRequests`:** Comparte `auth.active_role`, `auth.available_roles` y permisos a Inertia

#### Frontend

- **`resources/js/pages/auth/login.tsx`:** Login traducido al español con selector opcional de contexto
- **`resources/js/pages/auth/two-factor-challenge.tsx`:** Challenge 2FA

#### Tests

- `tests/Feature/Auth/LoginContextTest.php` — Contexto, jerarquía, seguridad
- `tests/Feature/Auth/AuthenticationTest.php` — Login/logout base
- `tests/Feature/Auth/TwoFactorChallengeTest.php` — 2FA

#### ENTREGA

- Login funcional con selector de contexto
- Jerarquía automática de roles al login
- Middleware que mantiene el contexto activo
- Datos de rol disponibles en todas las páginas vía Inertia

#### Roles y Permisos

> **Sin permisos nuevos.** Este hito usa la infraestructura del Hito 2 pero no agrega permisos. Lo que hace es:

- **Jerarquía de contexto:** `admin` > `profesor` > `alumno` > `representante`
- **Multi-rol:** Un usuario puede tener varios roles y elegir con cuál operar
- **Middleware `EnsureRoleContext`:** Valida que el contexto elegido sea un rol que el usuario realmente tiene

---

### Hito 4 — CRUD de Usuarios y RBAC

**Estado:** Finalizado ✅

Gestión integral de usuarios con seguridad blindada y panel de roles/permisos.

#### Entidades creadas

| Entidad      | Propósito                          |
| ------------ | ---------------------------------- |
| `UserPolicy` | Control de acceso granular por rol |

#### Backend

- **`UserController`:** CRUD completo (index, create, store, show, edit, update, destroy)
- **`RoleController`:** Listado, show y edición de permisos por rol (sin crear/eliminar roles)
- **`PermissionController`:** Listado informativo de permisos y su matriz de roles
- **`StoreUserRequest`, `UpdateUserRequest`:** Validaciones con reglas condicionales por rol
- **`UpdateRoleRequest`:** Validación de sincronización de permisos
- **`UserPolicy`:** Métodos `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`
- **`Gate::authorize()`:** Protección en todos los métodos de escritura

#### Frontend

- **`resources/js/pages/admin/users/index.tsx`:** Listado con DataTable, búsqueda, paginación
- **`resources/js/pages/admin/users/create.tsx`:** Formulario de creación multi-rol
- **`resources/js/pages/admin/users/edit.tsx`:** Edición con sección de permisos directos
- **`resources/js/pages/admin/users/show.tsx`:** Vista de solo lectura con toda la info del usuario
- **`resources/js/pages/admin/roles/index.tsx`:** Listado de roles con permisos agrupados (tarjetas colapsables)
- **`resources/js/pages/admin/roles/show.tsx`:** Detalle de rol
- **`resources/js/pages/admin/roles/edit.tsx`:** Edición de permisos por rol
- **`resources/js/pages/admin/permissions/index.tsx`:** Matriz informativa de permisos

#### Sidebar

- **`resources/js/components/app-sidebar.tsx`:** Navegación basada en permisos
- Items visibles solo si el usuario tiene el permiso correspondiente

#### Tests

- `tests/Feature/Admin/UserManagementTest.php` — CRUD base
- `tests/Feature/Admin/UserShowTest.php` — Vista de detalles
- `tests/Feature/Admin/AuthorizationVulnerabilityTest.php` — Seguridad y bloqueos
- `tests/Feature/Admin/RoleManagementTest.php` — CRUD restringido de roles
- `tests/Feature/Admin/PermissionBasedAccessTest.php` — Herencia y permisos directos
- `tests/Feature/Admin/PermissionManagementTest.php` — Gestión de permisos
- `tests/Feature/Authorization/BasicRoleTest.php` — Roles base

#### ENTREGA

- CRUD completo de usuarios con validaciones por rol
- Panel de roles con edición de permisos (sin crear/eliminar roles)
- Panel informativo de permisos
- Sección de permisos directos en edición de usuario
- Vista de detalles de usuario (solo lectura)
- Sidebar con navegación condicional por permisos
- Seguridad de doble capa (backend Gate + frontend conditional rendering)

#### Roles y Permisos

**Permisos utilizados:**

| Permiso            | Protege                  | Roles que lo tienen |
| ------------------ | ------------------------ | ------------------- |
| `users.view`       | Listado de usuarios      | `admin`, `profesor` |
| `users.create`     | Crear usuarios           | `admin`             |
| `users.edit`       | Editar usuarios          | `admin`             |
| `users.delete`     | Eliminar usuarios        | `admin`             |
| `roles.view`       | Listado de roles         | `admin`             |
| `roles.edit`       | Editar permisos de roles | `admin`             |
| `permissions.view` | Matriz de permisos       | `admin`             |

**UserPolicy:**

| Método        | Lógica                                                 |
| ------------- | ------------------------------------------------------ |
| `viewAny`     | Admin ve todos, profesor ve solo alumnos               |
| `view`        | Admin ve cualquiera, profesor ve alumnos de su sección |
| `create`      | Solo admin                                             |
| `update`      | Admin siempre, profesor solo alumnos de su sección     |
| `delete`      | Solo admin                                             |
| `restore`     | Solo admin                                             |
| `forceDelete` | Solo admin                                             |

**Protección en controladores:** Todos los métodos de escritura usan `Gate::authorize('permiso')`.

**Frontend:** Los botones y enlaces del sidebar se ocultan si el usuario no tiene el permiso correspondiente (`can('permiso')` en React).

---

### Hito 5 — Estructura Académica

**Estado:** Finalizado ✅

Define la jerarquía académica: Años Escolares, Tipos de Lapso, Lapsos, Grados y Secciones.

#### Entidades creadas

| Modelo         | Tabla            | Relaciones                                                               |
| -------------- | ---------------- | ------------------------------------------------------------------------ |
| `AcademicYear` | `academic_years` | HasMany: SchoolTerm, Grade, Section, Enrollment, TeacherAssignment       |
| `SchoolTerm`   | `school_terms`   | BelongsTo: AcademicYear, TermType                                        |
| `TermType`     | `term_types`     | Catálogo de tipos de lapso (ej: "1er Lapso")                             |
| `Grade`        | `grades`         | BelongsTo: AcademicYear; HasMany: Section, Enrollment, TeacherAssignment |
| `Section`      | `sections`       | BelongsTo: AcademicYear, Grade; HasMany: Enrollment, TeacherAssignment   |

**Cascada de soft deletes:** `AcademicYear` → `SchoolTerm` + `Grade` → `Section` → `Enrollment` + `TeacherAssignment`

#### Backend

- **`AcademicYearController`:** CRUD completo con lógica de "único año activo" (al activar uno, desactiva los demás)
- **`SchoolTermController`:** CRUD con numeración automática (`term_number`) y validación de no superposición de fechas
- **`TermTypeController`:** CRUD del catálogo de tipos de lapso
- **`GradeController`:** CRUD con campo `order` para ordenamiento correcto
- **`SectionController`:** CRUD con índice único `academic_year_id + grade_id + name`
- **`StoreAcademicYearRequest`, `UpdateAcademicYearRequest`:** Validaciones de unicidad y fechas
- **`StoreSchoolTermRequest`, `UpdateSchoolTermRequest`:** Validación de no superposición de fechas, máximo 3 lapsos
- **`StoreGradeRequest`, `UpdateGradeRequest`:** Validaciones de orden
- **`StoreSectionRequest`, `UpdateSectionRequest`:** Validaciones de unicidad jerárquica

#### Frontend

- **`resources/js/pages/admin/academic-years/index.tsx`:** Listado con DataTable, badge de año activo
- **`resources/js/pages/admin/academic-years/edit.tsx`:** Formulario de edición
- **`resources/js/pages/admin/academic-years/show.tsx`:** Vista de detalles
- **`resources/js/pages/admin/school-terms/index.tsx`:** Listado con filtros por año
- **`resources/js/pages/admin/school-terms/edit.tsx`:** Formulario de edición
- **`resources/js/pages/admin/grades/index.tsx`:** Listado con filtros por año
- **`resources/js/pages/admin/grades/edit.tsx`:** Formulario de edición
- **`resources/js/pages/admin/sections/index.tsx`:** Listado con filtros por año y grado
- **`resources/js/pages/admin/sections/edit.tsx`:** Formulario de edición
- **`resources/js/pages/admin/sections/show.tsx`:** Vista de detalles
- **`resources/js/pages/admin/term-types/index.tsx`:** Listado de tipos de lapso

#### Datos de prueba

- **AcademicYearSeeder, GradeSeeder, SectionSeeder, SchoolTermSeeder, TermTypeSeeder:** Generan estructura base
- **Factories:** AcademicYearFactory, GradeFactory, SectionFactory, SchoolTermFactory

#### Tests

- `tests/Feature/Admin/AcademicYearControllerTest.php`
- `tests/Feature/Admin/SchoolTermControllerTest.php`
- `tests/Feature/Admin/GradeControllerTest.php`
- `tests/Feature/Admin/SectionControllerTest.php`

#### ENTREGA

- Jerarquía académica completa (año → lapso/grado → sección)
- Catálogo de tipos de lapso administrable
- Lógica de "único año activo"
- Numeración automática de lapsos
- Validaciones de unicidad y no superposición
- Seeder operativo con estructura base

#### Roles y Permisos

**Permisos utilizados:**

| Permiso                 | Protege                    | Roles que lo tienen |
| ----------------------- | -------------------------- | ------------------- |
| `academic_years.view`   | Listado de años académicos | `admin`             |
| `academic_years.create` | Crear años académicos      | `admin`             |
| `academic_years.edit`   | Editar años académicos     | `admin`             |
| `academic_years.delete` | Eliminar años académicos   | `admin`             |
| `school_terms.view`     | Listado de lapsos          | `admin`             |
| `school_terms.create`   | Crear lapsos               | `admin`             |
| `school_terms.edit`     | Editar lapsos              | `admin`             |
| `school_terms.delete`   | Eliminar lapsos            | `admin`             |
| `grades.view`           | Listado de grados          | `admin`             |
| `grades.create`         | Crear grados               | `admin`             |
| `grades.edit`           | Editar grados              | `admin`             |
| `grades.delete`         | Eliminar grados            | `admin`             |
| `sections.view`         | Listado de secciones       | `admin`             |
| `sections.create`       | Crear secciones            | `admin`             |
| `sections.edit`         | Editar secciones           | `admin`             |
| `sections.delete`       | Eliminar secciones         | `admin`             |

> Todos los permisos de estructura académica son exclusivos de `admin`. Ni profesor, alumno ni representante tienen acceso de gestión a estas entidades.

---

### Hito 6 — Inscripciones y Asignaciones

**Estado:** Finalizado ✅

Vincula usuarios con la estructura académica y permite la promoción masiva de alumnos.

#### Entidades creadas

| Modelo              | Tabla                 | Relaciones                                              |
| ------------------- | --------------------- | ------------------------------------------------------- |
| `Enrollment`        | `enrollments`         | BelongsTo: AcademicYear, Grade, Section, User (student) |
| `TeacherAssignment` | `teacher_assignments` | BelongsTo: AcademicYear, Grade, Section, User (teacher) |

**Desnormalización:** Ambas tablas incluyen `academic_year_id`, `grade_id`, `section_id` para evitar JOINs en consultas frecuentes.

#### Backend

- **`EnrollmentController`:** Listado, creación individual, panel de promoción masiva, eliminación
- **`TeacherAssignmentController`:** Listado, creación, eliminación
- **`PromoteEnrollmentsRequest`:** Validación de promoción masiva (año activo, unicidad, roles)
- **`StoreEnrollmentRequest`:** Validación de inscripción individual
- **`StoreTeacherAssignmentRequest`:** Validación de asignación docente
- **Reglas de negocio RN-1 a RN-6:** Validadas en backend y reflejadas en frontend

#### Frontend

- **`resources/js/pages/admin/enrollments/index.tsx`:** Listado de inscripciones con DataTable
- **`resources/js/pages/admin/enrollments/create.tsx`:** Formulario de inscripción individual con autocompletado
- **`resources/js/pages/admin/enrollments/promote.tsx`:** Panel de promoción masiva con dos paneles (origen/destino)
- **`resources/js/pages/admin/assignments/index.tsx`:** Listado de asignaciones docentes
- **`resources/js/pages/admin/assignments/create.tsx`:** Formulario de asignación docente
- **`resources/js/pages/admin/academic-info/index.tsx`:** Vista general de información académica (dashboard)

#### Datos de prueba

- **EnrollmentFactory, TeacherAssignmentFactory**

#### Tests

- `tests/Feature/Admin/EnrollmentControllerTest.php`
- `tests/Feature/Admin/TeacherAssignmentControllerTest.php`
- `tests/Feature/Admin/AcademicStructureOverviewTest.php`

#### ENTREGA

- Inscripción individual de nuevos ingresos
- Panel de promoción masiva con sugerencia inteligente de grado (order + 1)
- Asignación de profesores a secciones
- Validaciones robustas (año activo, unicidad, roles, integridad jerárquica)
- Vista general de información académica

#### Roles y Permisos

**Permisos utilizados:**

| Permiso              | Protege                                | Roles que lo tienen |
| -------------------- | -------------------------------------- | ------------------- |
| `enrollments.view`   | Listado de inscripciones               | `admin`, `profesor` |
| `enrollments.create` | Crear inscripciones / promover alumnos | `admin`             |
| `enrollments.edit`   | Editar inscripciones                   | `admin`             |
| `enrollments.delete` | Eliminar inscripciones                 | `admin`             |
| `assignments.view`   | Listado de asignaciones docentes       | `admin`, `profesor` |
| `assignments.create` | Asignar profesores a secciones         | `admin`             |
| `assignments.edit`   | Editar asignaciones                    | `admin`             |
| `assignments.delete` | Eliminar asignaciones                  | `admin`             |
| `academic_info.view` | Vista general de info académica        | `admin`, `profesor` |

**Nota:** El profesor puede **ver** inscripciones y asignaciones pero no crearlas ni editarlas. Solo el admin puede inscribir alumnos y asignar profesores.

---

### Hito 7 — Representantes

**Estado:** Finalizado ✅

Vinculación familiar entre representantes y estudiantes.

#### Entidades creadas

| Modelo                  | Tabla                             | Relaciones                                                         |
| ----------------------- | --------------------------------- | ------------------------------------------------------------------ |
| `RelationshipType`      | `relationship_types`              | Catálogo de parentesco (Padre, Madre, Tutor legal, Otro)           |
| `StudentRepresentative` | `student_representatives` (Pivot) | BelongsTo: User (student), User (representative), RelationshipType |

#### Backend

- **`RepresentativeController`:** Store y destroy de representantes desde perfil de estudiante
- **Relación muchos-a-muchos en User:** `representatives()` y `representedStudents()` con `relationship_type` como pivot data
- **Índice único:** `student_id + representative_id` para evitar duplicados

#### Frontend

- **`resources/js/pages/admin/users/partials/assign-representative-modal.tsx`:** Modal de asignación desde perfil admin
- **`resources/js/pages/settings/profile.tsx`:** Pestañas dinámicas:
    - "Mis Representantes" (visible solo para alumnos)
    - "Mis Representados" (visible solo para representantes)

#### Datos de prueba

- **RelationshipTypeSeeder:** Padre, Madre, Tutor legal, Otro

#### Tests

- `tests/Feature/Reproduction/CreateRepresentativeTest.php`

#### ENTREGA

- Catálogo de tipos de parentesco configurable
- Asignación de representantes desde perfil de estudiante
- Visualización de representantes/representados en `/settings/profile` según rol

#### Roles y Permisos

> **Sin permisos nuevos.** La gestión de representantes usa los permisos existentes de usuarios (`users.edit` para admin).

**Acceso por rol:**

| Rol             | Puede ver representantes                           | Puede asignar representantes |
| --------------- | -------------------------------------------------- | ---------------------------- |
| `admin`         | Sí (desde perfil de alumno)                        | Sí                           |
| `profesor`      | No                                                 | No                           |
| `alumno`        | Sí (solo los suyos en `/settings/profile`)         | No                           |
| `representante` | Sí (solo sus representados en `/settings/profile`) | No                           |

---

### Hito 8 — Información de Salud

**Estado:** Finalizado ✅

Condiciones médicas de estudiantes con soporte de archivos adjuntos.

#### Entidades creadas

| Modelo                | Tabla                    | Relaciones                                                                      | Traits                                            |
| --------------------- | ------------------------ | ------------------------------------------------------------------------------- | ------------------------------------------------- |
| `HealthCondition`     | `health_conditions`      | HasMany: StudentHealthRecord                                                    | SoftDeletes, SoftCascadeTrait                     |
| `StudentHealthRecord` | `student_health_records` | BelongsTo: User (student), HealthCondition, User (receivedBy); MorphMany: Media | SoftDeletes, SoftCascadeTrait, InteractsWithMedia |

**Cascada de soft deletes:** `HealthCondition` → `StudentHealthRecord` → `Media` (eliminación física)

#### Backend

- **`HealthConditionController`:** CRUD del catálogo (index, store, update, destroy)
- **`StudentHealthRecordController`:** Store, update, destroy de registros médicos
- **`StoreHealthConditionRequest`, `UpdateHealthConditionRequest`:** Validaciones del catálogo
- **`StoreStudentHealthRecordRequest`, `UpdateStudentHealthRecordRequest`:** Validaciones con existencia de estudiante, condición, y usuario receptor
- **Eliminación de archivos físicos:** Al soft delete de un registro, los archivos adjuntos se eliminan físicamente (no se recuperan al restaurar)

#### Frontend

- **`resources/js/pages/admin/health-conditions/index.tsx`:** Listado de condiciones con DataTable
- **`resources/js/pages/admin/users/partials/health-record-modal.tsx`:** Modal de creación/edición con subida de archivos
- **`resources/js/pages/settings/profile.tsx`:** Pestaña "Salud" visible solo para alumnos

#### Datos de prueba

- **HealthConditionSeeder:** Condiciones base (asma, diabetes, epilepsia, etc.)
- **HealthConditionFactory, StudentHealthRecordFactory**

#### Tests

- `tests/Feature/Admin/HealthConditionTest.php` (pendiente de crear)
- `tests/Feature/Admin/StudentHealthRecordTest.php` (pendiente de crear)

#### ENTREGA

- Catálogo de condiciones de salud administrable
- Modal de registros médicos con subida de documentos
- Pestaña "Salud" en perfil de alumno
- Eliminación en cascada de archivos físicos

#### Roles y Permisos

**Permisos utilizados:**

| Permiso                    | Protege                    | Roles que lo tienen |
| -------------------------- | -------------------------- | ------------------- |
| `health_conditions.view`   | Listado de condiciones     | `admin`             |
| `health_conditions.create` | Crear condiciones          | `admin`             |
| `health_conditions.edit`   | Editar condiciones         | `admin`             |
| `health_conditions.delete` | Eliminar condiciones       | `admin`             |
| `student_health.view`      | Ver registros médicos      | `admin`             |
| `student_health.create`    | Crear registros médicos    | `admin`             |
| `student_health.edit`      | Editar registros médicos   | `admin`             |
| `student_health.delete`    | Eliminar registros médicos | `admin`             |

**Acceso por rol:**

| Rol             | Ve catálogo condiciones | Crea/edita registros | Ve su propia salud                       |
| --------------- | ----------------------- | -------------------- | ---------------------------------------- |
| `admin`         | Sí                      | Sí                   | Sí (desde perfil de alumno)              |
| `profesor`      | No                      | No                   | No                                       |
| `alumno`        | No                      | No                   | Sí (solo la suya en `/settings/profile`) |
| `representante` | No                      | No                   | Sí (solo la de su representado)          |

> **Nota:** Los permisos de salud son exclusivos de `admin` actualmente. En el futuro, el rol `profesor` podría recibir `student_health.view` y `student_health.create` para registrar condiciones de alumnos bajo su cargo (como dice la matriz de permisos en las especificaciones).

---

## Mejoras Visuales y de Consistencia (Transversal)

**Contexto:** Después de completar los hitos 0-8, se realizó una refactorización visual masiva para estandarizar la apariencia y usabilidad de toda la aplicación administrativa.

### Componentes reutilizables creados

| Componente     | Archivo                                        | Propósito                                                                  |
| -------------- | ---------------------------------------------- | -------------------------------------------------------------------------- |
| `DataTable`    | `resources/js/components/ui/data-table.tsx`    | Tabla con paginación Inertia, numeración, selector de registros por página |
| `TableFilters` | `resources/js/components/ui/table-filters.tsx` | Filtros de búsqueda con debounce                                           |
| `useDebounce`  | `resources/js/hooks/use-debounce.ts`           | Hook personalizado para debounce                                           |

### Páginas refactorizadas

Todas las páginas administrativas fueron actualizadas para usar `DataTable` y `TableFilters`:

- `/admin/users` — Listado con búsqueda y paginación
- `/admin/enrollments` — Listado de inscripciones
- `/admin/sections` — Listado con filtros por año y grado
- `/admin/academic-years` — Listado con badge de año activo
- `/admin/school-terms` — Listado con filtros por año
- `/admin/grades` — Listado con filtros por año
- `/admin/health-conditions` — Listado de condiciones de salud
- `/admin/term-types` — Listado de tipos de lapso
- `/admin/roles` — Tarjetas colapsables con permisos agrupados
- `/admin/permissions` — Matriz informativa

### Mejoras en formularios

- Diseño basado en Cards con headers consistentes (iconos, padding, esquinas redondeadas)
- Eliminación de restricciones `max-w-2xl` para aprovechar ancho disponible
- Botones "Volver" redirigen correctamente al listado de cada entidad

### Mejoras en paginación

- Navegación directa a cualquier página (no solo anterior/siguiente)
- Selector de cantidad de registros por página
- Funcionamiento correcto con Inertia.js sin recarga completa
- Numeración de filas correcta considerando la página actual

### ENTREGA

- Interfaz uniforme en toda la aplicación administrativa
- Componentes reutilizables para tablas y filtros
- Formularios estandarizados con diseño moderno
- Experiencia de usuario consistente en todos los módulos

---

## Hitos Pendientes (9-15)

### Hito 9 — Catálogos de Configuración

**Estado:** Finalizado ✅

**Enfoque:** Categorías de actividades y ubicaciones.

**Principio de diseño:** Estas tablas son **catálogos plantilla**, no entidades relacionales. Las jornadas de campo **copian el valor** (snapshot) al momento de su creación, sin usar foreign keys. El mismo patrón que se usa con `term_types` → `school_terms.name`: el catálogo provee opciones, la jornada guarda el string.

#### Entidades creadas

| Modelo             | Tabla                 | Relaciones                       | Traits                        |
| ------------------ | --------------------- | -------------------------------- | ----------------------------- |
| `ActivityCategory` | `activity_categories` | Ninguna (catálogo independiente) | SoftDeletes, SoftCascadeTrait |
| `Location`         | `locations`           | Ninguna (catálogo independiente) | SoftDeletes, SoftCascadeTrait |

#### Diseño de snapshots en `field_sessions` (Hito 10)

```
field_sessions:
  activity_name (VARCHAR nullable)  ← snapshot del nombre de categoría
  location_name (VARCHAR nullable)  ← snapshot del nombre de ubicación
  ← SIN foreign keys a activity_categories ni locations
```

**Consecuencia:** Eliminar una categoría o ubicación del catálogo **no afecta** las jornadas existentes. Las jornadas pasadas conservan el nombre que se guardó como string en el momento de su creación.

#### Backend

- **`ActivityCategoryController`:** CRUD completo (index, store, update, destroy) con validación de nombres únicos
- **`LocationController`:** CRUD completo (index, store, update, destroy) con validación de nombres únicos
- **`StoreActivityCategoryRequest`, `UpdateActivityCategoryRequest`:** Validaciones con autorización por permiso
- **`StoreLocationRequest`, `UpdateLocationRequest`:** Validaciones con autorización por permiso

#### Frontend

- **`/admin/activity-categories`:** Listado con formulario inline de crear/editar. Enlace en menú de Configuración → "Categorías"
- **`/admin/locations`:** Listado con formulario inline de crear/editar. Enlace en menú de Configuración → "Ubicaciones"
- Ambos accesibles desde el menú de configuración (`SettingsLayout`) para `admin` y `profesor`

#### Datos de prueba

- **ActivityCategorySeeder:** 14 categorías base (desmalezamiento, siembra, riego, etc.)
- **LocationSeeder:** 10 ubicaciones base (huerto escolar, cancha, comunidad, etc.)
- **ActivityCategoryFactory, LocationFactory**

#### Tests

- `tests/Feature/Admin/ActivityCategoryControllerTest.php` — CRUD base, duplicados, permisos de profesor
- `tests/Feature/Admin/LocationControllerTest.php` — CRUD base, duplicados, permisos de profesor

#### ENTREGA

- Catálogo de categorías de actividad administrable por admin y profesor
- Catálogo de ubicaciones administrable por admin y profesor
- Permisos asignados a ambos roles en el seeder
- 14 tests pasando (7 por entidad)

#### Roles y Permisos

> **Decisión de arquitectura:** Se otorga CRUD completo a `profesor` en ambos catálogos porque serán su herramienta de trabajo diario. Al crear jornadas, el profesor selecciona del catálogo y el sistema guarda el **nombre** como snapshot, no una FK.

| Permiso                      | Protege                | Roles que lo tienen |
| ---------------------------- | ---------------------- | ------------------- |
| `activity_categories.view`   | Listado de categorías  | `admin`, `profesor` |
| `activity_categories.create` | Crear categorías       | `admin`, `profesor` |
| `activity_categories.edit`   | Editar categorías      | `admin`, `profesor` |
| `activity_categories.delete` | Eliminar categorías    | `admin`, `profesor` |
| `locations.view`             | Listado de ubicaciones | `admin`, `profesor` |
| `locations.create`           | Crear ubicaciones      | `admin`, `profesor` |
| `locations.edit`             | Editar ubicaciones     | `admin`, `profesor` |
| `locations.delete`           | Eliminar ubicaciones   | `admin`, `profesor` |

**Acceso por rol:**

| Rol             | Ve catálogos | Crea/edita/elimina      |
| --------------- | ------------ | ----------------------- |
| `admin`         | Sí           | Sí                      |
| `profesor`      | Sí           | Sí (autonomía completa) |
| `alumno`        | No           | No                      |
| `representante` | No           | No                      |

---

### Hito 10 — Jornadas de Campo

**Estado:** Finalizado ✅

**Enfoque:** Registro central de actividades de campo.

**Principio de diseño:** Las jornadas son el evento central del sistema. Todo gira alrededor de ellas: la asistencia se registra en jornadas, las horas se acreditan a partir de jornadas, los reportes se construyen sobre jornadas. Este hito cubre **solo la creación y gestión de jornadas**; la asistencia y subactividades se abordan en el Hito 11. Se permiten jornadas planificadas con anticipación o registradas después de realizadas.

**Regla de negocio:** Las jornadas siempre pertenecen al año escolar activo. No se permite seleccionar otro año al crear una jornada.

#### Entidades creadas

| Modelo               | Tabla                    | Relaciones                                                                          |
| -------------------- | ------------------------ | ----------------------------------------------------------------------------------- |
| `FieldSessionStatus` | `field_session_statuses` | Catálogo de estados (planned, realized, cancelled)                                  |
| `FieldSession`       | `field_sessions`         | BelongsTo: AcademicYear, SchoolTerm (nullable), User (profesor), FieldSessionStatus |

**Diseño de snapshots:** `activity_name` y `location_name` son campos VARCHAR que copian el valor del catálogo al momento de crear la jornada. **Sin foreign keys** a `activity_categories` ni `locations`.

**Cascada de soft deletes:** `AcademicYear` → `FieldSession` (ya configurado en AcademicYear con SoftCascadeTrait)

#### Estructura de `field_sessions`

| Campo                 | Tipo                  | Notas                                                   |
| --------------------- | --------------------- | ------------------------------------------------------- |
| `id`                  | BIGINT                | PK                                                      |
| `name`                | VARCHAR               | Nombre/título de la jornada                             |
| `description`         | TEXT nullable         | Descripción detallada                                   |
| `academic_year_id`    | BIGINT (FK)           | Desnormalizado. Se asigna automáticamente al año activo |
| `school_term_id`      | BIGINT (FK, nullable) | Sugerido automáticamente por fecha                      |
| `user_id`             | BIGINT (FK)           | Profesor responsable (dueño de la jornada)              |
| `activity_name`       | VARCHAR nullable      | Snapshot del catálogo (sin FK)                          |
| `location_name`       | VARCHAR nullable      | Snapshot del catálogo (sin FK)                          |
| `start_datetime`      | DATETIME              | Fecha y hora de inicio                                  |
| `end_datetime`        | DATETIME              | Fecha y hora de fin                                     |
| `base_hours`          | DECIMAL               | Calculado automáticamente (end - start)                 |
| `status_id`           | BIGINT (FK)           | FK a `field_session_statuses`                           |
| `cancellation_reason` | TEXT nullable         | Obligatorio si status = cancelled                       |
| `deleted_at`          | TIMESTAMP             | SoftDeletes                                             |
| `timestamps`          |                       |                                                         |

#### Estados de jornada (`field_session_statuses`)

| Estado      | Descripción                     |
| ----------- | ------------------------------- |
| `planned`   | Planificada (aún no se realiza) |
| `realized`  | Realizada (ya se ejecutó)       |
| `cancelled` | Cancelada (no se realizó)       |

#### Backend

- **`FieldSessionController`:** CRUD completo (index, create, store, show, edit, update, destroy)
- **`StoreFieldSessionRequest`:** Validaciones con:
    - `start_datetime` < `end_datetime`
    - `cancellation_reason` obligatorio si status = cancelled
    - `base_hours` calculado automáticamente en el backend
    - `school_term_id` sugerido automáticamente comparando `start_datetime` contra fechas de lapsos del año activo
    - `academic_year_id` NO se recibe del formulario — se asigna automáticamente desde el año activo
- **`UpdateFieldSessionRequest`:** Mismas validaciones
- **Regla de propiedad:** Solo el profesor responsable (`user_id`) o un admin puede editar/eliminar la jornada. Verificado en el controlador con `abort(403)`.
- **Lógica de sugerencia de lapso:** Al crear o editar, el backend busca el lapso del año activo cuyo rango de fechas contenga `start_datetime` y lo asigna automáticamente.

#### Frontend

- **`/admin/field-sessions`:** Listado con DataTable, filtros por año y estado, badges de colores por estado
- **`/admin/field-sessions/create`:** Formulario con:
    - Campo de nombre y descripción
    - Año escolar mostrado como texto fijo (no seleccionable, siempre el activo)
    - Select de profesor responsable
    - Select de estado (planned, realized, cancelled)
    - Campos de fecha/hora de inicio y fin
    - `base_hours` calculado en tiempo real en el frontend
    - **Combobox de categoría de actividad:** Dropdown con búsqueda que muestra las categorías existentes del catálogo. Permite escribir texto libre y crear nuevas categorías al vuelo si no existe.
    - **Combobox de ubicación:** Mismo patrón que categoría.
    - Campo de motivo de cancelación (visible solo si status = cancelled)
- **`/admin/field-sessions/{id}/edit`:** Mismo formulario, solo editable por dueño o admin
- **`/admin/field-sessions/{id}/show`:** Vista de detalles de la jornada

#### Datos de prueba

- **FieldSessionStatusSeeder:** 3 estados base (planned, realized, cancelled)
- **FieldSessionFactory, FieldSessionStatusFactory**

#### Tests

- `tests/Feature/Admin/FieldSessionControllerTest.php` — CRUD base, validación de fechas, motivo de cancelación obligatorio, regla de propiedad (profesor no puede editar jornada ajena), cálculo automático de base_hours

#### ENTREGA

- Catálogo de estados de jornada con 3 estados base
- CRUD completo de jornadas con asignación automática del año activo
- Sugerencia automática de lapso según fecha de inicio
- Cálculo automático de horas base
- Regla de propiedad: profesor solo edita/elimina sus propias jornadas
- Campo condicional de motivo de cancelación
- 14 tests pasando

#### Roles y Permisos

> **Decisión de arquitectura:** El profesor tiene CRUD completo sobre sus propias jornadas. Solo puede editar/eliminar las que creó. El admin puede gestionar todas.

| Permiso                 | Protege             | Roles que lo tienen                    |
| ----------------------- | ------------------- | -------------------------------------- |
| `field_sessions.view`   | Listado de jornadas | `admin`, `profesor`                    |
| `field_sessions.create` | Crear jornadas      | `admin`, `profesor`                    |
| `field_sessions.edit`   | Editar jornadas     | `admin`, `profesor` (solo las propias) |
| `field_sessions.delete` | Eliminar jornadas   | `admin`, `profesor` (solo las propias) |

**Regla de propiedad:** Solo el profesor responsable de una jornada (`user_id` en `field_sessions`) o un administrador puede editarla o eliminarla. Un profesor puede ver las jornadas de otros profesores pero no modificarlas.

**Acceso por rol:**

| Rol             | Ve todas las jornadas | Crea jornadas | Edita jornadas   | Elimina jornadas |
| --------------- | --------------------- | ------------- | ---------------- | ---------------- |
| `admin`         | Sí (todas)            | Sí            | Sí (todas)       | Sí (todas)       |
| `profesor`      | Sí (todas)            | Sí            | Solo las propias | Solo las propias |
| `alumno`        | No                    | No            | No               | No               |
| `representante` | No                    | No            | No               | No               |
| `profesor`      | Sí (todas)            | Sí            | Solo las propias | Solo las propias |
| `alumno`        | No                    | No            | No               | No               |
| `representante` | No                    | No            | No               | No               |

---

### Hito 11 — Asistencia y Subactividades

**Enfoque:** Acreditación de horas y evidencias.

**Estado:** ✅ Completado con verificación contra especificaciones (2026-04-05)

**Correcciones post-verificación:**

- Los permisos de delete para `attendances` y `attendance_activities` fueron extendidos a profesor con ownership (solo propias jornadas) — esto permite la autogestión del profesor en su día a día. Eliminar usuarios y otros datos críticos sigue siendo solo admin.

Entidades implementadas:

- `Attendance` — Registro binario de asistencia por estudiante en cada jornada
- `AttendanceActivity` — Subactividades con horas distribuidas por tipo de actividad, con soporte para evidencias (Media Library)

#### Roles y Permisos (implementados)

| Permiso                        | Protege                  | Roles que lo tendrán                    |
| ------------------------------ | ------------------------ | --------------------------------------- |
| `attendances.view`             | Ver asistencia           | `admin`, `profesor`                     |
| `attendances.create`           | Registrar asistencia     | `admin`, `profesor` (solo sus jornadas) |
| `attendances.edit`             | Editar asistencia        | `admin`, `profesor` (solo sus jornadas) |
| `attendances.delete`           | Eliminar asistencia      | `admin`, `profesor` (solo sus jornadas) |
| `attendance_activities.view`   | Ver subactividades       | `admin`, `profesor`                     |
| `attendance_activities.create` | Registrar subactividades | `admin`, `profesor` (solo sus jornadas) |
| `attendance_activities.edit`   | Editar subactividades    | `admin`, `profesor` (solo sus jornadas) |
| `attendance_activities.delete` | Eliminar subactividades  | `admin`, `profesor` (solo sus jornadas) |

> **Nota de diseño:** Los permisos de delete fueron extendidos a profesor con ownership para permitir autogestión. Solo datos críticos (usuarios, roles, estructura académica) son exclusivamente admin.

---

### Hito 12 — Horas Externas

**Enfoque:** Acreditación de horas previas para estudiantes transferidos.

#### Roles y Permisos (esperados)

| Permiso                 | Protege                 | Roles que lo tendrán |
| ----------------------- | ----------------------- | -------------------- |
| `external_hours.view`   | Ver horas externas      | `admin`              |
| `external_hours.create` | Cargar horas externas   | `admin`              |
| `external_hours.edit`   | Editar horas externas   | `admin`              |
| `external_hours.delete` | Eliminar horas externas | `admin`              |

> Solo `admin` puede cargar horas externas porque es un proceso formal que requiere verificación de documentación.

---

### Hito 13 — Acumulados y Dashboards

**Enfoque:** Progreso visual y KPIs de horas acumuladas por estudiante.

#### Roles y Permisos (esperados)

| Permiso                  | Protege                | Roles que lo tendrán                           |
| ------------------------ | ---------------------- | ---------------------------------------------- |
| `dashboard.view`         | Dashboard principal    | `admin`, `profesor`                            |
| `accumulated_hours.view` | Ver acumulados propios | `admin`, `profesor`, `alumno`, `representante` |

**Acceso por rol:**

| Rol             | Qué ve                                           |
| --------------- | ------------------------------------------------ |
| `admin`         | Dashboard global con KPIs de toda la institución |
| `profesor`      | Dashboard de sus secciones y alumnos asignados   |
| `alumno`        | Solo su propio progreso de horas                 |
| `representante` | Progreso de su representado                      |

---

### Hito 14 — Reportes en PDF

**Enfoque:** Generación de certificados y listados de horas.

#### Roles y Permisos (esperados)

| Permiso            | Protege          | Roles que lo tendrán                           |
| ------------------ | ---------------- | ---------------------------------------------- |
| `reports.view`     | Generar reportes | `admin`, `profesor`                            |
| `reports.download` | Descargar PDFs   | `admin`, `profesor`, `alumno`, `representante` |

**Acceso por rol:**

| Rol             | Qué puede descargar                                       |
| --------------- | --------------------------------------------------------- |
| `admin`         | Todos los reportes (listados, certificados, estadísticas) |
| `profesor`      | Reportes de sus secciones                                 |
| `alumno`        | Solo su certificado de horas                              |
| `representante` | Certificado de horas de su representado                   |

---

### Hito 15 — Revisión y Estabilización

**Enfoque:** QA final, seeders demo, documentación completa.

#### Roles y Permisos

> **Sin permisos nuevos.** Este hito es de estabilización. Se verifican todos los permisos implementados en hitos anteriores con tests de autorización integrales.

**Tests de autorización esperados:**

1. **Admin:** Puede acceder a TODO
2. **Profesor:** Solo ve lo de sus secciones, no puede gestionar estructura académica
3. **Alumno:** Solo ve su propio perfil, horas y salud
4. **Representante:** Solo ve la info de su representado
5. **Usuario sin rol:** No ve nada del panel admin
