# 🔐 Matriz Maestra de Permisos - Amantina App

**Documento Maestro de Autorización**

Este documento es la **fuente única de verdad** para todos los permisos del sistema. Cualquier cambio en permisos debe reflejarse aquí primero y luego sincronizarse con:

- `database/seeders/RoleAndPermissionSeeder.php`
- Tests de seguridad en `tests/Browser/Admin/`
- Controladores en `app/Http/Controllers/Admin/`
- Policies en `app/Policies/`

---

## 📋 Índice

1. [Roles del Sistema](#roles-del-sistema)
2. [Convención de Nomenclatura](#convención-de-nomenclatura)
3. [Matriz Completa de Permisos](#matriz-completa-de-permisos)
4. [Permisos por Módulo](#permisos-por-módulo)
5. [Permisos por Rol](#permisos-por-rol)
6. [Reglas Especiales](#reglas-especiales)
7. [Proceso de Sincronización](#proceso-de-sincronización)

---

## 🎭 Roles del Sistema

| Rol | Descripción | Nivel de Acceso |
|-----|-------------|-----------------|
| **admin** | Administrador del sistema | Acceso total a todos los módulos |
| **profesor** | Docente | Gestión de jornadas, asistencias y actividades |
| **alumno** | Estudiante | Solo visualización de su propio dashboard y horas |
| **representante** | Padre/Tutor | Solo visualización de dashboard y horas de sus representados |

---

## 📝 Convención de Nomenclatura

Los permisos siguen el formato: `{modulo}.{accion}`

**Acciones estándar**:
- `view` - Ver listados y detalles
- `create` - Crear nuevos registros
- `edit` - Modificar registros existentes
- `delete` - Eliminar registros

**Ejemplo**: `academic_years.view`, `users.create`, `attendances.edit`

---

## 📊 Matriz Completa de Permisos

### Leyenda
- ✅ = Tiene el permiso
- ❌ = NO tiene el permiso
- 🔒 = Tiene el permiso con restricciones adicionales

---

## 🗂️ Permisos por Módulo

### 1. Gestión de Usuarios (users)

**Permisos disponibles**:
- `users.view`
- `users.create`
- `users.edit`
- `users.delete`

| Permiso | Admin | Profesor | Alumno | Representante | Notas |
|---------|-------|----------|--------|---------------|-------|
| `users.view` | ✅ | ✅ | ❌ | ❌ | Profesor solo ve listado básico |
| `users.create` | ✅ | ❌ | ❌ | ❌ | Solo admin puede crear usuarios |
| `users.edit` | ✅ | ❌ | ❌ | ❌ | Solo admin puede editar usuarios |
| `users.delete` | ✅ | ❌ | ❌ | ❌ | Solo admin puede eliminar usuarios |

**Rutas y Acciones**:

| Ruta | Método | Permiso Requerido | Admin | Profesor | Alumno | Representante |
|------|--------|-------------------|-------|----------|--------|---------------|
| `/admin/users` | GET | `users.view` | ✅ | ✅ | ❌ 403 | ❌ 403 |
| `/admin/users/create` | GET | `users.create` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/users` | POST | `users.create` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/users/{id}` | GET | `users.view` | ✅ | ✅ | ❌ 403 | ❌ 403 |
| `/admin/users/{id}/edit` | GET | `users.edit` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/users/{id}` | PUT | `users.edit` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/users/{id}` | DELETE | `users.delete` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |

**Reglas especiales**:
- ✅ Solo admin puede cambiar roles de usuarios
- ✅ Solo admin puede cambiar permisos de usuarios
- ❌ **NADIE** puede cambiar sus propios roles (ni admin)
- ❌ **NADIE** puede cambiar sus propios permisos (ni admin)

---

### 2. Gestión de Roles (roles)

**Permisos disponibles**:
- `roles.view`
- `roles.create`
- `roles.edit`
- `roles.delete`

| Permiso | Admin | Profesor | Alumno | Representante | Notas |
|---------|-------|----------|--------|---------------|-------|
| `roles.view` | ✅ | ❌ | ❌ | ❌ | Solo admin gestiona roles |
| `roles.create` | ✅ | ❌ | ❌ | ❌ | Solo admin crea roles |
| `roles.edit` | ✅ | ❌ | ❌ | ❌ | Solo admin edita roles |
| `roles.delete` | ✅ | ❌ | ❌ | ❌ | Solo admin elimina roles |

**Rutas y Acciones**:

| Ruta | Método | Permiso Requerido | Admin | Profesor | Alumno | Representante |
|------|--------|-------------------|-------|----------|--------|---------------|
| `/admin/roles` | GET | `roles.view` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/roles/create` | GET | `roles.create` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/roles` | POST | `roles.create` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/roles/{id}` | GET | `roles.view` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/roles/{id}/edit` | GET | `roles.edit` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/roles/{id}` | PUT | `roles.edit` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/roles/{id}` | DELETE | `roles.delete` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |

---

### 3. Gestión de Permisos (permissions)

**Permisos disponibles**:
- `permissions.view`
- `permissions.create`
- `permissions.edit`
- `permissions.delete`

| Permiso | Admin | Profesor | Alumno | Representante | Notas |
|---------|-------|----------|--------|---------------|-------|
| `permissions.view` | ✅ | ❌ | ❌ | ❌ | Solo admin gestiona permisos |
| `permissions.create` | ✅ | ❌ | ❌ | ❌ | Solo admin crea permisos |
| `permissions.edit` | ✅ | ❌ | ❌ | ❌ | Solo admin edita permisos |
| `permissions.delete` | ✅ | ❌ | ❌ | ❌ | Solo admin elimina permisos |

**Rutas y Acciones**:

| Ruta | Método | Permiso Requerido | Admin | Profesor | Alumno | Representante |
|------|--------|-------------------|-------|----------|--------|---------------|
| `/admin/permissions` | GET | `permissions.view` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/permissions/create` | GET | `permissions.create` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/permissions` | POST | `permissions.create` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/permissions/{id}` | GET | `permissions.view` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/permissions/{id}/edit` | GET | `permissions.edit` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/permissions/{id}` | PUT | `permissions.edit` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/permissions/{id}` | DELETE | `permissions.delete` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |

---

### 4. Años Académicos (academic_years)

**Permisos disponibles**:
- `academic_years.view`
- `academic_years.create`
- `academic_years.edit`
- `academic_years.delete`

| Permiso | Admin | Profesor | Alumno | Representante | Notas |
|---------|-------|----------|--------|---------------|-------|
| `academic_years.view` | ✅ | ❌ | ❌ | ❌ | Solo admin gestiona años académicos |
| `academic_years.create` | ✅ | ❌ | ❌ | ❌ | Solo admin crea años académicos |
| `academic_years.edit` | ✅ | ❌ | ❌ | ❌ | Solo admin edita años académicos |
| `academic_years.delete` | ✅ | ❌ | ❌ | ❌ | Solo admin elimina años académicos |

**Rutas y Acciones**:

| Ruta | Método | Permiso Requerido | Admin | Profesor | Alumno | Representante |
|------|--------|-------------------|-------|----------|--------|---------------|
| `/admin/academic-years` | GET | `academic_years.view` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/academic-years/create` | GET | `academic_years.create` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/academic-years` | POST | `academic_years.create` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/academic-years/{id}` | GET | `academic_years.view` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/academic-years/{id}/edit` | GET | `academic_years.edit` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/academic-years/{id}` | PUT | `academic_years.edit` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/academic-years/{id}` | DELETE | `academic_years.delete` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |

---

### 5. Períodos Escolares (school_terms)

**Permisos disponibles**:
- `school_terms.view`
- `school_terms.create`
- `school_terms.edit`
- `school_terms.delete`

| Permiso | Admin | Profesor | Alumno | Representante | Notas |
|---------|-------|----------|--------|---------------|-------|
| `school_terms.view` | ✅ | ❌ | ❌ | ❌ | Solo admin gestiona períodos |
| `school_terms.create` | ✅ | ❌ | ❌ | ❌ | Solo admin crea períodos |
| `school_terms.edit` | ✅ | ❌ | ❌ | ❌ | Solo admin edita períodos |
| `school_terms.delete` | ✅ | ❌ | ❌ | ❌ | Solo admin elimina períodos |

**Rutas y Acciones**:

| Ruta | Método | Permiso Requerido | Admin | Profesor | Alumno | Representante |
|------|--------|-------------------|-------|----------|--------|---------------|
| `/admin/school-terms` | GET | `school_terms.view` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/school-terms/create` | GET | `school_terms.create` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/school-terms` | POST | `school_terms.create` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/school-terms/{id}` | GET | `school_terms.view` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/school-terms/{id}/edit` | GET | `school_terms.edit` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/school-terms/{id}` | PUT | `school_terms.edit` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/school-terms/{id}` | DELETE | `school_terms.delete` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |

---

### 6. Grados (grades)

**Permisos disponibles**:
- `grades.view`
- `grades.create`
- `grades.edit`
- `grades.delete`

| Permiso | Admin | Profesor | Alumno | Representante | Notas |
|---------|-------|----------|--------|---------------|-------|
| `grades.view` | ✅ | ❌ | ❌ | ❌ | Solo admin gestiona grados |
| `grades.create` | ✅ | ❌ | ❌ | ❌ | Solo admin crea grados |
| `grades.edit` | ✅ | ❌ | ❌ | ❌ | Solo admin edita grados |
| `grades.delete` | ✅ | ❌ | ❌ | ❌ | Solo admin elimina grados |

**Rutas y Acciones**:

| Ruta | Método | Permiso Requerido | Admin | Profesor | Alumno | Representante |
|------|--------|-------------------|-------|----------|--------|---------------|
| `/admin/grades` | GET | `grades.view` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/grades/create` | GET | `grades.create` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/grades` | POST | `grades.create` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/grades/{id}/edit` | GET | `grades.edit` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/grades/{id}` | PUT | `grades.edit` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/grades/{id}` | DELETE | `grades.delete` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |

---

### 7. Secciones (sections)

**Permisos disponibles**:
- `sections.view`
- `sections.create`
- `sections.edit`
- `sections.delete`

| Permiso | Admin | Profesor | Alumno | Representante | Notas |
|---------|-------|----------|--------|---------------|-------|
| `sections.view` | ✅ | ❌ | ❌ | ❌ | Solo admin gestiona secciones |
| `sections.create` | ✅ | ❌ | ❌ | ❌ | Solo admin crea secciones |
| `sections.edit` | ✅ | ❌ | ❌ | ❌ | Solo admin edita secciones |
| `sections.delete` | ✅ | ❌ | ❌ | ❌ | Solo admin elimina secciones |

**Rutas y Acciones**:

| Ruta | Método | Permiso Requerido | Admin | Profesor | Alumno | Representante |
|------|--------|-------------------|-------|----------|--------|---------------|
| `/admin/sections` | GET | `sections.view` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/sections/create` | GET | `sections.create` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/sections` | POST | `sections.create` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/sections/{id}` | GET | `sections.view` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/sections/{id}/edit` | GET | `sections.edit` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/sections/{id}` | PUT | `sections.edit` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/sections/{id}` | DELETE | `sections.delete` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |

---

### 8. Inscripciones (enrollments)

**Permisos disponibles**:
- `enrollments.view`
- `enrollments.create`
- `enrollments.edit`
- `enrollments.delete`

| Permiso | Admin | Profesor | Alumno | Representante | Notas |
|---------|-------|----------|--------|---------------|-------|
| `enrollments.view` | ✅ | ✅ | ❌ | ❌ | Profesor solo ve inscripciones |
| `enrollments.create` | ✅ | ❌ | ❌ | ❌ | Solo admin inscribe estudiantes |
| `enrollments.edit` | ✅ | ❌ | ❌ | ❌ | Solo admin modifica inscripciones |
| `enrollments.delete` | ✅ | ❌ | ❌ | ❌ | Solo admin elimina inscripciones |

**Rutas y Acciones**:

| Ruta | Método | Permiso Requerido | Admin | Profesor | Alumno | Representante |
|------|--------|-------------------|-------|----------|--------|---------------|
| `/admin/enrollments` | GET | `enrollments.view` | ✅ | ✅ | ❌ 403 | ❌ 403 |
| `/admin/enrollments/create` | GET | `enrollments.create` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/enrollments` | POST | `enrollments.create` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/enrollments/{id}` | GET | `enrollments.view` | ✅ | ✅ | ❌ 403 | ❌ 403 |
| `/admin/enrollments/{id}/edit` | GET | `enrollments.edit` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/enrollments/{id}` | PUT | `enrollments.edit` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/enrollments/{id}` | DELETE | `enrollments.delete` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |

---

### 9. Asignaciones de Profesores (assignments)

**Permisos disponibles**:
- `assignments.view`
- `assignments.create`
- `assignments.edit`
- `assignments.delete`

| Permiso | Admin | Profesor | Alumno | Representante | Notas |
|---------|-------|----------|--------|---------------|-------|
| `assignments.view` | ✅ | ✅ | ❌ | ❌ | Profesor ve sus asignaciones |
| `assignments.create` | ✅ | ❌ | ❌ | ❌ | Solo admin asigna profesores |
| `assignments.edit` | ✅ | ❌ | ❌ | ❌ | Solo admin modifica asignaciones |
| `assignments.delete` | ✅ | ❌ | ❌ | ❌ | Solo admin elimina asignaciones |

**Rutas y Acciones**:

| Ruta | Método | Permiso Requerido | Admin | Profesor | Alumno | Representante |
|------|--------|-------------------|-------|----------|--------|---------------|
| `/admin/assignments` | GET | `assignments.view` | ✅ | ✅ | ❌ 403 | ❌ 403 |
| `/admin/assignments/create` | GET | `assignments.create` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/assignments` | POST | `assignments.create` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/assignments/{id}` | GET | `assignments.view` | ✅ | ✅ | ❌ 403 | ❌ 403 |
| `/admin/assignments/{id}/edit` | GET | `assignments.edit` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/assignments/{id}` | PUT | `assignments.edit` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/assignments/{id}` | DELETE | `assignments.delete` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |

---

### 10. Información Académica (academic_info)

**Permisos disponibles**:
- `academic_info.view`

| Permiso | Admin | Profesor | Alumno | Representante | Notas |
|---------|-------|----------|--------|---------------|-------|
| `academic_info.view` | ✅ | ✅ | ❌ | ❌ | Vista general de info académica |

**Rutas y Acciones**:

| Ruta | Método | Permiso Requerido | Admin | Profesor | Alumno | Representante |
|------|--------|-------------------|-------|----------|--------|---------------|
| `/admin/academic-info` | GET | `academic_info.view` | ✅ | ✅ | ❌ 403 | ❌ 403 |

---

### 11. Condiciones de Salud (health_conditions)

**Permisos disponibles**:
- `health_conditions.view`
- `health_conditions.create`
- `health_conditions.edit`
- `health_conditions.delete`

| Permiso | Admin | Profesor | Alumno | Representante | Notas |
|---------|-------|----------|--------|---------------|-------|
| `health_conditions.view` | ✅ | ❌ | ❌ | ❌ | Solo admin gestiona catálogo |
| `health_conditions.create` | ✅ | ❌ | ❌ | ❌ | Solo admin crea condiciones |
| `health_conditions.edit` | ✅ | ❌ | ❌ | ❌ | Solo admin edita condiciones |
| `health_conditions.delete` | ✅ | ❌ | ❌ | ❌ | Solo admin elimina condiciones |

**Rutas y Acciones**:

| Ruta | Método | Permiso Requerido | Admin | Profesor | Alumno | Representante |
|------|--------|-------------------|-------|----------|--------|---------------|
| `/admin/health-conditions` | GET | `health_conditions.view` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/health-conditions/create` | GET | `health_conditions.create` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/health-conditions` | POST | `health_conditions.create` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/health-conditions/{id}` | GET | `health_conditions.view` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/health-conditions/{id}/edit` | GET | `health_conditions.edit` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/health-conditions/{id}` | PUT | `health_conditions.edit` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/health-conditions/{id}` | DELETE | `health_conditions.delete` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |

---

### 12. Salud de Estudiantes (student_health)

**Permisos disponibles**:
- `student_health.view`
- `student_health.create`
- `student_health.edit`
- `student_health.delete`

| Permiso | Admin | Profesor | Alumno | Representante | Notas |
|---------|-------|----------|--------|---------------|-------|
| `student_health.view` | ✅ | ❌ | ❌ | ❌ | Solo admin ve registros de salud |
| `student_health.create` | ✅ | ❌ | ❌ | ❌ | Solo admin crea registros |
| `student_health.edit` | ✅ | ❌ | ❌ | ❌ | Solo admin edita registros |
| `student_health.delete` | ✅ | ❌ | ❌ | ❌ | Solo admin elimina registros |

**Rutas y Acciones**:

| Ruta | Método | Permiso Requerido | Admin | Profesor | Alumno | Representante |
|------|--------|-------------------|-------|----------|--------|---------------|
| `/admin/student-health` | GET | `student_health.view` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/student-health/create` | GET | `student_health.create` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/student-health` | POST | `student_health.create` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/student-health/{id}` | GET | `student_health.view` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/student-health/{id}/edit` | GET | `student_health.edit` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/student-health/{id}` | PUT | `student_health.edit` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/student-health/{id}` | DELETE | `student_health.delete` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |

---

### 13. Categorías de Actividades (activity_categories)

**Permisos disponibles**:
- `activity_categories.view`
- `activity_categories.create`
- `activity_categories.edit`
- `activity_categories.delete`

| Permiso | Admin | Profesor | Alumno | Representante | Notas |
|---------|-------|----------|--------|---------------|-------|
| `activity_categories.view` | ✅ | ✅ | ❌ | ❌ | Profesor necesita ver categorías |
| `activity_categories.create` | ✅ | ✅ | ❌ | ❌ | Profesor puede crear categorías |
| `activity_categories.edit` | ✅ | ✅ | ❌ | ❌ | Profesor puede editar categorías |
| `activity_categories.delete` | ✅ | ✅ | ❌ | ❌ | Profesor puede eliminar categorías |

**Rutas y Acciones**:

| Ruta | Método | Permiso Requerido | Admin | Profesor | Alumno | Representante |
|------|--------|-------------------|-------|----------|--------|---------------|
| `/admin/activity-categories` | GET | `activity_categories.view` | ✅ | ✅ | ❌ 403 | ❌ 403 |
| `/admin/activity-categories/create` | GET | `activity_categories.create` | ✅ | ✅ | ❌ 403 | ❌ 403 |
| `/admin/activity-categories` | POST | `activity_categories.create` | ✅ | ✅ | ❌ 403 | ❌ 403 |
| `/admin/activity-categories/{id}` | GET | `activity_categories.view` | ✅ | ✅ | ❌ 403 | ❌ 403 |
| `/admin/activity-categories/{id}/edit` | GET | `activity_categories.edit` | ✅ | ✅ | ❌ 403 | ❌ 403 |
| `/admin/activity-categories/{id}` | PUT | `activity_categories.edit` | ✅ | ✅ | ❌ 403 | ❌ 403 |
| `/admin/activity-categories/{id}` | DELETE | `activity_categories.delete` | ✅ | ✅ | ❌ 403 | ❌ 403 |

---

### 14. Ubicaciones (locations)

**Permisos disponibles**:
- `locations.view`
- `locations.create`
- `locations.edit`
- `locations.delete`

| Permiso | Admin | Profesor | Alumno | Representante | Notas |
|---------|-------|----------|--------|---------------|-------|
| `locations.view` | ✅ | ✅ | ❌ | ❌ | Profesor necesita ver ubicaciones |
| `locations.create` | ✅ | ✅ | ❌ | ❌ | Profesor puede crear ubicaciones |
| `locations.edit` | ✅ | ✅ | ❌ | ❌ | Profesor puede editar ubicaciones |
| `locations.delete` | ✅ | ✅ | ❌ | ❌ | Profesor puede eliminar ubicaciones |

**Rutas y Acciones**:

| Ruta | Método | Permiso Requerido | Admin | Profesor | Alumno | Representante |
|------|--------|-------------------|-------|----------|--------|---------------|
| `/admin/locations` | GET | `locations.view` | ✅ | ✅ | ❌ 403 | ❌ 403 |
| `/admin/locations/create` | GET | `locations.create` | ✅ | ✅ | ❌ 403 | ❌ 403 |
| `/admin/locations` | POST | `locations.create` | ✅ | ✅ | ❌ 403 | ❌ 403 |
| `/admin/locations/{id}` | GET | `locations.view` | ✅ | ✅ | ❌ 403 | ❌ 403 |
| `/admin/locations/{id}/edit` | GET | `locations.edit` | ✅ | ✅ | ❌ 403 | ❌ 403 |
| `/admin/locations/{id}` | PUT | `locations.edit` | ✅ | ✅ | ❌ 403 | ❌ 403 |
| `/admin/locations/{id}` | DELETE | `locations.delete` | ✅ | ✅ | ❌ 403 | ❌ 403 |

---

### 15. Jornadas de Campo (field_sessions) 🔥 CRÍTICO

**Permisos disponibles**:
- `field_sessions.view`
- `field_sessions.create`
- `field_sessions.edit`
- `field_sessions.delete`

| Permiso | Admin | Profesor | Alumno | Representante | Notas |
|---------|-------|----------|--------|---------------|-------|
| `field_sessions.view` | ✅ | ✅ | ❌ | ❌ | Profesor ve sus jornadas |
| `field_sessions.create` | ✅ | ✅ | ❌ | ❌ | Profesor crea sus jornadas |
| `field_sessions.edit` | ✅ | 🔒 | ❌ | ❌ | Profesor solo edita sus jornadas |
| `field_sessions.delete` | ✅ | 🔒 | ❌ | ❌ | Profesor solo elimina sus jornadas |

**Rutas y Acciones**:

| Ruta | Método | Permiso Requerido | Admin | Profesor | Alumno | Representante |
|------|--------|-------------------|-------|----------|--------|---------------|
| `/admin/field-sessions` | GET | `field_sessions.view` | ✅ | ✅ | ❌ 403 | ❌ 403 |
| `/admin/field-sessions/create` | GET | `field_sessions.create` | ✅ | ✅ | ❌ 403 | ❌ 403 |
| `/admin/field-sessions` | POST | `field_sessions.create` | ✅ | ✅ | ❌ 403 | ❌ 403 |
| `/admin/field-sessions/{id}` | GET | `field_sessions.view` | ✅ | ✅ 🔒 | ❌ 403 | ❌ 403 |
| `/admin/field-sessions/{id}/edit` | GET | `field_sessions.edit` | ✅ | ✅ 🔒 | ❌ 403 | ❌ 403 |
| `/admin/field-sessions/{id}` | PUT | `field_sessions.edit` | ✅ | ✅ 🔒 | ❌ 403 | ❌ 403 |
| `/admin/field-sessions/{id}` | DELETE | `field_sessions.delete` | ✅ | ✅ 🔒 | ❌ 403 | ❌ 403 |

**Reglas especiales**:
- 🔒 Profesor solo puede editar/eliminar jornadas donde `field_session.user_id === auth()->id()`
- ✅ Admin puede editar/eliminar cualquier jornada

---

### 16. Asistencias (attendances) 🔥 MUY CRÍTICO

**Permisos disponibles**:
- `attendances.view`
- `attendances.create`
- `attendances.edit`
- `attendances.delete`

| Permiso | Admin | Profesor | Alumno | Representante | Notas |
|---------|-------|----------|--------|---------------|-------|
| `attendances.view` | ✅ | 🔒 | ❌ | ❌ | Profesor ve asistencias de sus jornadas |
| `attendances.create` | ✅ | 🔒 | ❌ | ❌ | Profesor registra en sus jornadas |
| `attendances.edit` | ✅ | 🔒 | ❌ | ❌ | Profesor edita en sus jornadas |
| `attendances.delete` | ✅ | 🔒 | ❌ | ❌ | Profesor elimina en sus jornadas |

**Rutas y Acciones**:

| Ruta | Método | Permiso Requerido | Admin | Profesor | Alumno | Representante |
|------|--------|-------------------|-------|----------|--------|---------------|
| `/admin/field-sessions/{session_id}/attendance` | GET | `attendances.view` | ✅ | ✅ 🔒 | ❌ 403 | ❌ 403 |
| `/admin/field-sessions/{session_id}/attendance` | POST | `attendances.create` | ✅ | ✅ 🔒 | ❌ 403 | ❌ 403 |
| `/admin/field-sessions/{session_id}/attendance/{id}` | PUT | `attendances.edit` | ✅ | ✅ 🔒 | ❌ 403 | ❌ 403 |
| `/admin/field-sessions/{session_id}/attendance/{id}` | DELETE | `attendances.delete` | ✅ | ✅ 🔒 | ❌ 403 | ❌ 403 |

**Reglas especiales**:
- 🔒 Profesor solo puede gestionar asistencias de jornadas donde `field_session.user_id === auth()->id()`
- ✅ Admin puede gestionar cualquier asistencia
- ❌ **ALUMNO NUNCA** puede registrar/modificar/eliminar asistencias (ni propias ni ajenas)
- ❌ **ALUMNO NUNCA** puede asignarse horas

---

### 17. Actividades de Asistencia (attendance_activities) 🔥 MUY CRÍTICO

**Permisos disponibles**:
- `attendance_activities.view`
- `attendance_activities.create`
- `attendance_activities.edit`
- `attendance_activities.delete`

| Permiso | Admin | Profesor | Alumno | Representante | Notas |
|---------|-------|----------|--------|---------------|-------|
| `attendance_activities.view` | ✅ | 🔒 | ❌ | ❌ | Profesor ve actividades de sus jornadas |
| `attendance_activities.create` | ✅ | 🔒 | ❌ | ❌ | Profesor crea actividades en sus jornadas |
| `attendance_activities.edit` | ✅ | 🔒 | ❌ | ❌ | Profesor edita actividades en sus jornadas |
| `attendance_activities.delete` | ✅ | 🔒 | ❌ | ❌ | Profesor elimina actividades en sus jornadas |

**Rutas y Acciones**:

| Ruta | Método | Permiso Requerido | Admin | Profesor | Alumno | Representante |
|------|--------|-------------------|-------|----------|--------|---------------|
| `/admin/attendance/{attendance_id}/activities` | GET | `attendance_activities.view` | ✅ | ✅ 🔒 | ❌ 403 | ❌ 403 |
| `/admin/attendance/{attendance_id}/activities` | POST | `attendance_activities.create` | ✅ | ✅ 🔒 | ❌ 403 | ❌ 403 |
| `/admin/attendance/{attendance_id}/activities/{id}` | PUT | `attendance_activities.edit` | ✅ | ✅ 🔒 | ❌ 403 | ❌ 403 |
| `/admin/attendance/{attendance_id}/activities/{id}` | DELETE | `attendance_activities.delete` | ✅ | ✅ 🔒 | ❌ 403 | ❌ 403 |

**Reglas especiales**:
- 🔒 Profesor solo puede gestionar actividades de asistencias de sus jornadas
- ✅ Admin puede gestionar cualquier actividad
- ❌ **ALUMNO NUNCA** puede modificar horas/actividades

---

### 18. Dashboard (dashboard)

**Permisos disponibles**:
- `dashboard.view`

| Permiso | Admin | Profesor | Alumno | Representante | Notas |
|---------|-------|----------|--------|---------------|-------|
| `dashboard.view` | ✅ | ✅ | ✅ | ✅ | Todos pueden ver su dashboard |

**Rutas y Acciones**:

| Ruta | Método | Permiso Requerido | Admin | Profesor | Alumno | Representante |
|------|--------|-------------------|-------|----------|--------|---------------|
| `/dashboard` | GET | `dashboard.view` | ✅ | ✅ | ✅ | ✅ |

---

### 19. Horas Acumuladas (accumulated_hours)

**Permisos disponibles**:
- `accumulated_hours.view`

| Permiso | Admin | Profesor | Alumno | Representante | Notas |
|---------|-------|----------|--------|---------------|-------|
| `accumulated_hours.view` | ✅ | ✅ | ✅ | ✅ | Todos pueden ver horas acumuladas |

**Rutas y Acciones**:

| Ruta | Método | Permiso Requerido | Admin | Profesor | Alumno | Representante |
|------|--------|-------------------|-------|----------|--------|---------------|
| `/accumulated-hours` | GET | `accumulated_hours.view` | ✅ | ✅ | ✅ 🔒 | ✅ 🔒 |
| `/accumulated-hours/{user_id}` | GET | `accumulated_hours.view` | ✅ | ✅ | ✅ 🔒 | ✅ 🔒 |

**Reglas especiales**:
- 🔒 Alumno solo ve sus propias horas
- 🔒 Representante solo ve horas de sus representados
- Profesor ve horas de estudiantes en sus jornadas
- Admin ve todas las horas

---

### 20. Horas Externas (external_hours)

**Permisos disponibles**:
- `external_hours.view`
- `external_hours.create`
- `external_hours.edit`
- `external_hours.delete`

| Permiso | Admin | Profesor | Alumno | Representante | Notas |
|---------|-------|----------|--------|---------------|-------|
| `external_hours.view` | ✅ | ❌ | ❌ | ❌ | Solo admin gestiona horas externas |
| `external_hours.create` | ✅ | ❌ | ❌ | ❌ | Solo admin crea horas externas |
| `external_hours.edit` | ✅ | ❌ | ❌ | ❌ | Solo admin edita horas externas |
| `external_hours.delete` | ✅ | ❌ | ❌ | ❌ | Solo admin elimina horas externas |

**Rutas y Acciones**:

| Ruta | Método | Permiso Requerido | Admin | Profesor | Alumno | Representante |
|------|--------|-------------------|-------|----------|--------|---------------|
| `/admin/external-hours` | GET | `external_hours.view` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/external-hours/create` | GET | `external_hours.create` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/external-hours` | POST | `external_hours.create` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/external-hours/{id}` | GET | `external_hours.view` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/external-hours/{id}/edit` | GET | `external_hours.edit` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/external-hours/{id}` | PUT | `external_hours.edit` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |
| `/admin/external-hours/{id}` | DELETE | `external_hours.delete` | ✅ | ❌ 403 | ❌ 403 | ❌ 403 |

---

## 👥 Permisos por Rol

### Admin (Administrador)

**Total de permisos**: TODOS (76 permisos)

El rol admin tiene acceso completo a todos los módulos del sistema sin restricciones.

**Excepciones**:
- ❌ No puede cambiar sus propios roles
- ❌ No puede cambiar sus propios permisos

---

### Profesor (Docente)

**Total de permisos**: 26 permisos

```
users.view
enrollments.view
assignments.view
academic_info.view
activity_categories.view
activity_categories.create
activity_categories.edit
activity_categories.delete
locations.view
locations.create
locations.edit
locations.delete
field_sessions.view
field_sessions.create
field_sessions.edit (solo sus jornadas)
field_sessions.delete (solo sus jornadas)
attendances.view (solo sus jornadas)
attendances.create (solo sus jornadas)
attendances.edit (solo sus jornadas)
attendances.delete (solo sus jornadas)
attendance_activities.view (solo sus jornadas)
attendance_activities.create (solo sus jornadas)
attendance_activities.edit (solo sus jornadas)
attendance_activities.delete (solo sus jornadas)
dashboard.view
accumulated_hours.view
```

**Restricciones**:
- Solo puede gestionar jornadas donde es el profesor asignado
- Solo puede gestionar asistencias de sus propias jornadas
- No puede modificar estructura académica (años, grados, secciones)
- No puede inscribir estudiantes

---

### Alumno (Estudiante)

**Total de permisos**: 2 permisos

```
dashboard.view
accumulated_hours.view
```

**Restricciones**:
- Solo puede ver su propio dashboard
- Solo puede ver sus propias horas acumuladas
- **NO puede registrar/modificar/eliminar asistencias**
- **NO puede asignarse horas**
- **NO puede modificar ningún dato del sistema**

---

### Representante (Padre/Tutor)

**Total de permisos**: 2 permisos

```
dashboard.view
accumulated_hours.view
```

**Restricciones**:
- Solo puede ver dashboard de sus representados
- Solo puede ver horas acumuladas de sus representados
- **NO puede modificar ningún dato del sistema**

---

## 🔒 Reglas Especiales

### 1. Gestión de Roles y Permisos

- ✅ Solo admin puede ver/crear/editar/eliminar roles
- ✅ Solo admin puede ver/crear/editar/eliminar permisos
- ✅ Solo admin puede asignar roles a usuarios
- ✅ Solo admin puede asignar permisos a usuarios
- ❌ **NADIE** puede cambiar sus propios roles (ni admin)
- ❌ **NADIE** puede cambiar sus propios permisos (ni admin)

### 2. Jornadas de Campo

- ✅ Admin puede gestionar cualquier jornada
- 🔒 Profesor solo puede gestionar jornadas donde `field_session.user_id === auth()->id()`
- ❌ Alumno NO puede crear/modificar/eliminar jornadas

### 3. Asistencias y Horas

- ✅ Admin puede gestionar cualquier asistencia
- 🔒 Profesor solo puede gestionar asistencias de sus jornadas
- ❌ **ALUMNO NUNCA** puede registrar/modificar/eliminar asistencias
- ❌ **ALUMNO NUNCA** puede asignarse horas
- ❌ **ALUMNO NUNCA** puede modificar horas de otros estudiantes

### 4. Visualización de Datos

- Alumno solo ve sus propios datos
- Representante solo ve datos de sus representados
- Profesor ve datos relacionados con sus jornadas
- Admin ve todos los datos

---

## 🔄 Proceso de Sincronización

Cuando se modifica la matriz de permisos:

### 1. Actualizar este documento (PERMISSIONS_MATRIX.md)
- Agregar/modificar/eliminar permisos en las tablas
- Documentar reglas especiales si aplica

### 2. Actualizar el Seeder
```bash
# Editar: database/seeders/RoleAndPermissionSeeder.php
# Agregar el nuevo permiso al array $permissions
# Asignarlo a los roles correspondientes
```

### 3. Ejecutar el Seeder en Desarrollo
```bash
php artisan db:seed --class=RoleAndPermissionSeeder
```

### 4. Actualizar Tests
```bash
# Crear/actualizar tests en: tests/Browser/Admin/[Modulo]Test.php
# Verificar que los tests cubran el nuevo permiso
```

### 5. Implementar Validaciones
```bash
# Actualizar controlador: app/Http/Controllers/Admin/[Modulo]Controller.php
# Actualizar policy: app/Policies/[Modulo]Policy.php
```

### 6. Ejecutar Tests
```bash
php artisan test --env=testing
```

### 7. Documentar en CHANGELOG
```bash
# Agregar entrada en CHANGELOG.md
```

---

## 🧪 Estrategia de Testing Basada en Permisos

### ⚠️ Principio Fundamental

**Los tests deben verificar PERMISOS, NO ROLES.**

Los roles son solo contenedores de permisos. Lo que realmente controla el acceso son los permisos individuales.

### ❌ INCORRECTO - Basado en Roles

```php
test('alumno NO puede crear año académico', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno'); // ❌ Probando el ROL
    
    $this->actingAs($alumno);
    // ...
});
```

**Problema**: Si mañana le damos el permiso `academic_years.create` al rol `alumno`, el test seguirá pasando pero la lógica de negocio cambió.

### ✅ CORRECTO - Basado en Permisos

```php
test('usuario sin permiso academic_years.create NO puede crear año académico', function () {
    $usuario = User::factory()->create();
    $usuario->assignRole('alumno'); // ✅ Usamos el rol solo para asignar permisos conocidos
    // alumno NO tiene academic_years.create según el seeder
    
    $this->actingAs($usuario);
    
    $response = $this->post('/admin/academic-years', [/* datos */]);
    
    $response->assertStatus(403);
});
```

**Ventaja**: El test verifica que sin el permiso específico, la acción es bloqueada.

---

## 📋 Plantillas de Tests

### 1. Tests Negativos (Sin Permiso)

```php
// Para cada permiso del módulo (view, create, edit, delete)

test('usuario sin permiso [modulo].[accion] NO puede [accion] mediante [metodo]', function () {
    $usuario = User::factory()->create();
    $usuario->assignRole('[rol_sin_permiso]'); // Usar rol que NO tiene el permiso
    
    $this->actingAs($usuario);
    
    $response = $this->[metodo]('[ruta]', [/* datos si aplica */]);
    
    $response->assertStatus(403);
    
    // Verificar que NO se realizó la acción
    $this->assertDatabase[Has/Missing](...);
});
```

**Ejemplo real**:

```php
test('usuario sin permiso academic_years.create NO puede crear mediante POST', function () {
    $usuario = User::factory()->create();
    $usuario->assignRole('profesor'); // profesor NO tiene academic_years.create
    
    $this->actingAs($usuario);
    
    $response = $this->post('/admin/academic-years', [
        'name' => '2025-2026',
        'start_date' => '2025-09-01',
        'end_date' => '2026-07-15',
        'required_hours' => 600,
        'is_active' => true,
    ]);
    
    $response->assertStatus(403);
    
    $this->assertDatabaseMissing('academic_years', [
        'name' => '2025-2026',
    ]);
});
```

### 2. Tests Positivos (Con Permiso)

```php
test('usuario CON permiso [modulo].[accion] SÍ puede [accion]', function () {
    $usuario = User::factory()->create();
    $usuario->assignRole('[rol_con_permiso]'); // Usar rol que SÍ tiene el permiso
    
    $this->actingAs($usuario);
    
    $response = $this->[metodo]('[ruta]', [/* datos */]);
    
    $response->assertStatus(302); // o 200 según el caso
    
    // Verificar que SÍ se realizó la acción
    $this->assertDatabase[Has/Missing](...);
});
```

### 3. Checklist de Tests por Módulo

Para cada módulo administrativo:

#### Permiso: `[modulo].view`

- [ ] Usuario sin permiso NO puede ver listado (GET /admin/[modulo])
- [ ] Usuario sin permiso NO puede ver detalle (GET /admin/[modulo]/{id})
- [ ] Usuario CON permiso SÍ puede ver listado
- [ ] Usuario CON permiso SÍ puede ver detalle

#### Permiso: `[modulo].create`

- [ ] Usuario sin permiso NO puede acceder a formulario (GET /admin/[modulo]/create)
- [ ] Usuario sin permiso NO puede crear (POST /admin/[modulo])
- [ ] Usuario CON permiso SÍ puede crear

#### Permiso: `[modulo].edit`

- [ ] Usuario sin permiso NO puede acceder a formulario (GET /admin/[modulo]/{id}/edit)
- [ ] Usuario sin permiso NO puede editar (PUT /admin/[modulo]/{id})
- [ ] Usuario CON permiso SÍ puede editar

#### Permiso: `[modulo].delete`

- [ ] Usuario sin permiso NO puede eliminar (DELETE /admin/[modulo]/{id})
- [ ] Usuario CON permiso SÍ puede eliminar

---

## 🎓 Ejemplos de Implementación

### Ejemplo 1: Academic Years (Solo Admin)

```php
// Usuario sin permiso NO puede ver
test('usuario sin permiso academic_years.view NO puede ver listado', function () {
    $usuario = User::factory()->create();
    $usuario->assignRole('alumno'); // alumno NO tiene academic_years.view
    
    $this->actingAs($usuario);
    
    $this->visit('/admin/academic-years')
        ->wait(2)
        ->assertSee('403');
});

// Usuario CON permiso SÍ puede ver
test('usuario CON permiso academic_years.view SÍ puede ver listado', function () {
    $usuario = User::factory()->create();
    $usuario->assignRole('admin'); // admin SÍ tiene academic_years.view
    
    $this->actingAs($usuario);
    
    $this->visit('/admin/academic-years')
        ->wait(2)
        ->assertPathIs('/admin/academic-years')
        ->assertSee('Años Escolares');
});
```

### Ejemplo 2: Attendances (Admin y Profesor)

```php
// Usuario sin permiso NO puede crear
test('usuario sin permiso attendances.create NO puede crear asistencia', function () {
    $usuario = User::factory()->create();
    $usuario->assignRole('alumno'); // alumno NO tiene attendances.create
    
    $this->actingAs($usuario);
    
    $response = $this->post("/admin/field-sessions/{$fieldSession->id}/attendance", [
        'user_id' => $usuario->id,
        'attended' => true,
    ]);
    
    $response->assertStatus(403);
});

// Usuario CON permiso SÍ puede crear
test('usuario CON permiso attendances.create SÍ puede crear asistencia', function () {
    $usuario = User::factory()->create();
    $usuario->assignRole('profesor'); // profesor SÍ tiene attendances.create
    
    $this->actingAs($usuario);
    
    // Profesor debe ser dueño de la jornada
    $fieldSession = FieldSession::factory()->create(['user_id' => $usuario->id]);
    
    $response = $this->post("/admin/field-sessions/{$fieldSession->id}/attendance", [
        'user_id' => $student->id,
        'attended' => true,
    ]);
    
    $response->assertStatus(302);
});
```

---

## 🔐 Validaciones Críticas en Código

### En UserController

```php
public function update(UpdateUserRequest $request, User $user)
{
    // ✅ VALIDACIÓN CRÍTICA #1: Solo admin puede cambiar roles
    if ($request->has('roles')) {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Solo administradores pueden asignar roles.');
        }
        
        // ✅ VALIDACIÓN CRÍTICA #2: Ni admin puede cambiar sus propios roles
        if (auth()->id() === $user->id) {
            abort(403, 'No puedes cambiar tus propios roles.');
        }
    }
    
    // ✅ VALIDACIÓN CRÍTICA #3: Solo admin puede cambiar permisos
    if ($request->has('permissions')) {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Solo administradores pueden asignar permisos.');
        }
        
        if (auth()->id() === $user->id) {
            abort(403, 'No puedes cambiar tus propios permisos.');
        }
    }
    
    // Resto del código...
}
```

### En RoleController (si existe)

```php
public function assignRole(Request $request, User $user)
{
    // ✅ VALIDACIÓN: Solo admin puede asignar roles
    $this->authorize('assign-roles');
    
    // ✅ VALIDACIÓN: No puede asignar roles a sí mismo
    if (auth()->id() === $user->id) {
        abort(403, 'No puedes asignar roles a ti mismo.');
    }
    
    // Resto del código...
}
```

### Validación en Controladores

```php
// Ejemplo: Validar permiso básico
Gate::authorize('view', AcademicYear::class);

// Ejemplo: Validar permiso con restricción
if (!auth()->user()->hasRole('admin') && $fieldSession->user_id !== auth()->id()) {
    abort(403, 'No tienes permiso para gestionar esta jornada.');
}
```

### Validación en Policies

```php
// Ejemplo: Policy con restricción
public function update(User $user, FieldSession $fieldSession): bool
{
    return $user->hasRole('admin') || $fieldSession->user_id === $user->id;
}
```

---

## 🛡️ Filosofía de Seguridad

### Principio de Defensa en Profundidad

```
┌─────────────────────────────────────────┐
│  1. Frontend (Ocultar opciones)        │ ← Primera línea
├─────────────────────────────────────────┤
│  2. Rutas (Middleware de autenticación)│ ← Segunda línea
├─────────────────────────────────────────┤
│  3. Middleware (Verificar permisos)    │ ← Tercera línea
├─────────────────────────────────────────┤
│  4. Controlador (Validar acción)       │ ← Cuarta línea
├─────────────────────────────────────────┤
│  5. Policy (Autorización centralizada) │ ← Quinta línea
└─────────────────────────────────────────┘
```

**Cada capa debe validar independientemente.**  
**Si una capa falla, las demás deben prevenir el ataque.**

### Principio de Menor Privilegio

- Cada rol tiene SOLO los permisos que necesita
- No hay roles "super usuario" excepto admin
- Los permisos son granulares y específicos

### Reglas Inmutables

1. **Solo Admin Puede Gestionar Roles**
   - ✅ Solo usuarios con permiso `roles.assign` pueden asignar roles
   - ✅ Solo usuarios con permiso `roles.edit` pueden modificar roles
   - ✅ Solo usuarios con permiso `roles.delete` pueden eliminar roles
   - ❌ **NUNCA** un usuario puede cambiar sus propios roles
   - ❌ **NUNCA** un usuario puede asignarse permisos adicionales

2. **Ni Siquiera Admin Puede Cambiar Sus Propios Roles**
   - ❌ Admin NO puede cambiar sus propios roles
   - ❌ Admin NO puede quitarse el rol de admin
   - ✅ Solo OTRO admin puede modificar roles de un admin
   - **Razón**: Prevenir escalación accidental o maliciosa

3. **Validación en TODAS las Capas**
   - Frontend: Ocultar opciones (UI/UX)
   - Middleware: Verificar permisos antes de llegar al controlador
   - Controlador: Validar permisos en cada acción
   - Policy: Reglas de autorización centralizadas

---

## 🚨 Errores Comunes y Lecciones Aprendidas

### Error 1: Probar el Rol en Lugar del Permiso

```php
// ❌ MAL
test('alumno NO puede crear', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');
    // ...
});

// ✅ BIEN
test('usuario sin permiso [modulo].create NO puede crear', function () {
    $usuario = User::factory()->create();
    $usuario->assignRole('alumno'); // alumno NO tiene [modulo].create
    // ...
});
```

### Error 2: No Verificar Todas las Acciones

```php
// ❌ MAL - Solo prueba POST
test('usuario sin permiso NO puede crear', function () {
    // Solo prueba POST
});

// ✅ BIEN - Prueba GET y POST
test('usuario sin permiso NO puede acceder a formulario de creación', function () {
    // Prueba GET /create
});

test('usuario sin permiso NO puede crear mediante POST', function () {
    // Prueba POST /
});
```

### Error 3: No Documentar Qué Rol Tiene Qué Permiso

```php
// ❌ MAL
$usuario->assignRole('profesor');

// ✅ BIEN
$usuario->assignRole('profesor'); // profesor NO tiene academic_years.create según seeder
```

### Lecciones Aprendidas

1. **Nunca confiar en el frontend**: Un atacante puede hacer POST directo
2. **Validar en CADA acción**: No asumir que el middleware es suficiente
3. **Tests robustos encuentran bugs reales**: Los tests superficiales dan falsa seguridad
4. **Roles y permisos son el pilar**: Si se comprometen, todo cae
5. **Principio de menor privilegio**: Solo dar los permisos necesarios
6. **Ni admin es omnipotente**: Ni admin puede cambiar sus propios roles

---

## 🔥 Checklist de Seguridad por Módulo

Para cada módulo administrativo, verificar:

- [ ] ✅ Admin puede realizar todas las acciones (con permisos)
- [ ] ❌ Profesor NO puede realizar acciones sin permiso (GET)
- [ ] ❌ Profesor NO puede realizar acciones sin permiso (POST)
- [ ] ❌ Profesor NO puede realizar acciones sin permiso (PUT)
- [ ] ❌ Profesor NO puede realizar acciones sin permiso (DELETE)
- [ ] ❌ Alumno NO puede realizar acciones sin permiso (GET)
- [ ] ❌ Alumno NO puede realizar acciones sin permiso (POST)
- [ ] ❌ Alumno NO puede realizar acciones sin permiso (PUT)
- [ ] ❌ Alumno NO puede realizar acciones sin permiso (DELETE)
- [ ] ❌ Representante NO puede realizar acciones sin permiso (GET)
- [ ] ❌ Representante NO puede realizar acciones sin permiso (POST)
- [ ] ❌ Representante NO puede realizar acciones sin permiso (PUT)
- [ ] ❌ Representante NO puede realizar acciones sin permiso (DELETE)

---

## 🔐 Compromiso de Seguridad

> "La seguridad no es un feature, es un requisito fundamental.  
> Cada línea de código debe ser escrita asumiendo que un atacante  
> intentará explotarla. Los tests de seguridad no son opcionales."

---

## 📊 Resumen Estadístico

| Rol | Total Permisos | % del Total |
|-----|----------------|-------------|
| Admin | 76 | 100% |
| Profesor | 26 | 34% |
| Alumno | 2 | 3% |
| Representante | 2 | 3% |

**Total de permisos en el sistema**: 76

**Módulos con restricciones especiales**: 3
- Jornadas de Campo (field_sessions)
- Asistencias (attendances)
- Actividades de Asistencia (attendance_activities)

---

## 🚨 Módulos Críticos

### Nivel CRÍTICO 🔴

1. **Gestión de Usuarios y Roles**
   - Si se compromete, todo el sistema cae
   - Validaciones especiales: nadie puede cambiar sus propios roles

2. **Asistencias y Horas**
   - Manipulación de horas = fraude académico
   - Alumno NUNCA puede modificar asistencias/horas

3. **Jornadas de Campo**
   - Control de actividades académicas
   - Profesor solo gestiona sus propias jornadas

---

## 📝 Notas de Implementación

### Validación en Controladores

```php
// Ejemplo: Validar permiso básico
Gate::authorize('view', AcademicYear::class);

// Ejemplo: Validar permiso con restricción
if (!auth()->user()->hasRole('admin') && $fieldSession->user_id !== auth()->id()) {
    abort(403, 'No tienes permiso para gestionar esta jornada.');
}
```

### Validación en Policies

```php
// Ejemplo: Policy con restricción
public function update(User $user, FieldSession $fieldSession): bool
{
    return $user->hasRole('admin') || $fieldSession->user_id === $user->id;
}
```

### Tests de Permisos

```php
// Ejemplo: Test basado en permiso
test('usuario sin permiso academic_years.create NO puede crear', function () {
    $usuario = User::factory()->create();
    $usuario->assignRole('profesor'); // profesor NO tiene academic_years.create
    
    $this->actingAs($usuario);
    
    $response = $this->post('/admin/academic-years', [/* datos */]);
    
    $response->assertStatus(403);
});
```

---

**Última Actualización**: Abril 30, 2026  
**Actualizado por**: Kiro AI  
**Versión**: 1.0  
**Estado**: 📋 DOCUMENTO MAESTRO - FUENTE ÚNICA DE VERDAD


---

## 📖 Resumen Rápido de Rutas por Rol

### ✅ Admin - Acceso Total

**Puede acceder a TODAS las rutas del sistema** (76 permisos)

### 🔒 Profesor - Acceso Limitado

**Puede acceder a** (26 permisos):
- ✅ `/admin/users` (solo ver)
- ✅ `/admin/enrollments` (solo ver)
- ✅ `/admin/assignments` (solo ver)
- ✅ `/admin/academic-info` (solo ver)
- ✅ `/admin/activity-categories` (CRUD completo)
- ✅ `/admin/locations` (CRUD completo)
- ✅ `/admin/field-sessions` (CRUD completo, solo sus jornadas)
- ✅ `/admin/field-sessions/{id}/attendance` (CRUD completo, solo sus jornadas)
- ✅ `/admin/attendance/{id}/activities` (CRUD completo, solo sus jornadas)
- ✅ `/dashboard` (ver)
- ✅ `/accumulated-hours` (ver)

**NO puede acceder a**:
- ❌ Gestión de usuarios, roles y permisos
- ❌ Estructura académica (años, períodos, grados, secciones)
- ❌ Inscripciones (crear/editar/eliminar)
- ❌ Asignaciones de profesores (crear/editar/eliminar)
- ❌ Salud de estudiantes
- ❌ Horas externas

### 🔒 Alumno - Solo Visualización

**Puede acceder a** (2 permisos):
- ✅ `/dashboard` (solo su dashboard)
- ✅ `/accumulated-hours` (solo sus horas)

**NO puede acceder a**:
- ❌ NINGUNA ruta administrativa (`/admin/*`)
- ❌ NO puede registrar/modificar/eliminar asistencias
- ❌ NO puede asignarse horas

### 🔒 Representante - Solo Visualización

**Puede acceder a** (2 permisos):
- ✅ `/dashboard` (solo dashboard de sus representados)
- ✅ `/accumulated-hours` (solo horas de sus representados)

**NO puede acceder a**:
- ❌ NINGUNA ruta administrativa (`/admin/*`)
- ❌ NO puede modificar ningún dato del sistema

---

## 🎯 Guía Rápida para Tests

Para cada módulo, verificar:

1. **Tests Negativos (Sin Permiso)**:
   - Usuario sin permiso NO puede acceder a formularios (GET)
   - Usuario sin permiso NO puede crear (POST)
   - Usuario sin permiso NO puede editar (PUT)
   - Usuario sin permiso NO puede eliminar (DELETE)

2. **Tests Positivos (Con Permiso)**:
   - Usuario CON permiso SÍ puede realizar todas las acciones

3. **Verificación en Base de Datos**:
   - Confirmar que la acción se realizó o no según el permiso

**Ejemplo de cobertura completa**:
```
✅ Alumno: NO puede VER, CREAR, ACTUALIZAR ni ELIMINAR (todos bloqueados con 403)
✅ Profesor: NO puede VER, CREAR, ACTUALIZAR ni ELIMINAR (todos bloqueados con 403)
✅ Representante: NO puede VER, CREAR, ACTUALIZAR ni ELIMINAR (todos bloqueados con 403)
✅ Admin: SÍ puede realizar TODAS las acciones
```

---

**Versión**: 2.0 (con rutas y métodos HTTP)  
**Última Actualización**: Abril 30, 2026
