# BITÁCORA SOCIOPRODUCTIVA

## Especificaciones del Sistema

**Versión 8.0** (Hitos 0-9 Completados)

_Sistema de registro, seguimiento y reporte de horas prácticas acumuladas en la asignatura Socioproductiva._

> **Estado de implementación (2026-04-05):**
>
> - ✅ Hitos 0-9 completados (Usuarios, Auth, RBAC, Estructura Académica, Inscripciones, Representantes, Salud, Catálogos)
> - 📋 Hitos 10-15 pendientes (Jornadas, Asistencia, Horas, Reportes)
>
> **Estadísticas del proyecto:**
>
> - 15 modelos, 20 controladores, 26 form requests, 1 policy, 3 middleware custom
> - 25 migraciones, 15 seeders, 11 factories, 30 feature tests
> - 43 páginas React, 28 componentes UI, 54 permisos, 4 roles

---

### 1. Descripción General

El sistema Bitácora Socioproductiva es una aplicación web orientada exclusivamente al registro, seguimiento y reporte de las horas prácticas que los estudiantes acumulan en la asignatura Socioproductiva a lo largo de su bachillerato. Su razón de ser es reemplazar registros manuales en papel o en hojas de cálculo, centralizando toda la información en una plataforma consultable por todos los actores involucrados: administradores, profesores, estudiantes y representantes.

El sistema no es un gestor escolar general. Está deliberadamente acotado a una sola asignatura y a una sola función: saber cuántas horas ha acumulado cada estudiante, en qué actividades, con qué desempeño, y cuánto le falta para cumplir el cupo requerido por la institución. Esta decisión de alcance es intencional y debe respetarse en futuras iteraciones para evitar que el sistema crezca hacia responsabilidades que no le corresponden.

> **Fuera de alcance:** calificaciones formales de otras materias, comunicación entre usuarios, planificación de actividades futuras, gestión de representantes como sistema independiente, y cualquier funcionalidad propia de un sistema de gestión escolar completo.

### 2. Identidad Visual y Excelencia en UI

El sistema no solo debe ser funcional, sino que debe proyectar una imagen de software premium y moderno. Se han establecido los siguientes pilares de diseño que deben respetarse en todos los módulos:

- **Estructura Basada en Cards**: La información se organiza en tarjetas claras, con cabeceras que contienen la acción principal (ej: editar) y el contexto (ej: Año Académico 2025).
- **Consistencia de Elementos**: El uso de badges para estados, íconos de Lucide para clarificar acciones, y tipografías consistentes (Inter/Outfit) es obligatorio.
- **Micro-interacciones**: Se deben incluir transiciones suaves y estados de carga (skeletons) para mejorar la percepción de velocidad.
- **Paletas de Color Curadas**: Se evitan los colores básicos, usando en su lugar paletas HSL armoniosas y respetando el modo oscuro del sistema.
- **Navegación Intuitiva**: Menús laterales con indicadores claros de la sección activa y migas de pan (breadcrumbs) precisas.

---

### 3. Roles y Autenticación

El sistema maneja cuatro roles: Administrador, Profesor, Alumno y Representante. Estos roles no son categorías arbitrarias, sino que reflejan los cuatro tipos de actores reales que interactúan con la información del sistema, cada uno con necesidades y responsabilidades distintas.

#### 2.1 Autenticación

El sistema utiliza correo electrónico y contraseña como mecanismo de login. El manejo de roles y permisos se implementa mediante Spatie Laravel Permissions, una librería madura y ampliamente adoptada en el ecosistema Laravel que permite asignar múltiples roles a un mismo usuario y definir permisos granulares por rol. Esta elección técnica es deliberada: un ENUM simple en la tabla `users` no podría manejar el caso de un usuario con múltiples roles simultáneos, como un profesor que también es administrador o representante.

Desde el panel de Administración, los administradores pueden gestionar la asignación de permisos a cada rol y otorgar permisos directos a usuarios específicos para casos excepcionales, garantizando una flexibilidad total en el control de acceso sin comprometer la integridad de los roles base.

El endpoint de login acepta un parámetro opcional llamado `context`. Este parámetro existe para resolver un caso de uso real y concreto: un profesor que además es representante de un estudiante necesita poder elegir con qué capacidad desea operar en el sistema en cada sesión. Si no se envía `context`, el backend aplica automáticamente una jerarquía de prioridad:

> **Prioridad automática de rol al login:**
>
> - `admin` (máxima prioridad)
> - `profesor`
> - `alumno`
> - `representante` (mínima prioridad)
>
> Si se envía `context`, el backend valida que el usuario tenga ese rol. Si no lo tiene, retorna error. El contexto se fija para toda la sesión.

La decisión de fijar el contexto para toda la sesión, en lugar de permitir cambio de rol en caliente, es una decisión de simplicidad arquitectónica. El sistema asegura que si el usuario pierde la sesión física o recarga la página, el contexto se recupera mediante un middleware dedicado (`EnsureRoleContext`), garantizando que la experiencia de usuario sea consistente y que los permisos se evalúen siempre contra el rol activo.

#### 2.2 Definición de Roles

| Rol           | Descripción                                                                       | Puede coexistir con          |
| ------------- | --------------------------------------------------------------------------------- | ---------------------------- |
| Administrador | Control total del sistema. Gestiona configuración, usuarios, jornadas y reportes. | Profesor                     |
| Profesor      | Registra jornadas, toma asistencia, carga horas. Responsable de jornadas propias. | Administrador, Representante |
| Alumno        | Solo lectura. Ve su propio historial, horas y novedades.                          | No coexiste                  |
| Representante | Solo lectura. Ve la información del alumno que representa.                        | Profesor, Administrador      |

#### 2.3 Matriz de Permisos

| Acción                                  | Alumno | Representante     | Profesor        | Administrador |
| --------------------------------------- | ------ | ----------------- | --------------- | ------------- |
| Ver propio perfil y horas               | Sí     | Sí (representado) | Sí              | Sí            |
| Ver perfil y horas de otros alumnos     | No     | No                | Sí              | Sí            |
| Editar datos propios (email, tel, foto) | Sí     | Sí                | Sí              | Sí            |
| Editar datos académicos del alumno      | No     | No                | Sí              | Sí            |
| Crear y editar profesores               | No     | No                | No              | Sí            |
| Crear jornadas                          | No     | No                | Sí              | Sí            |
| Editar jornada propia                   | No     | No                | Sí              | Sí            |
| Ver jornada ajena                       | No     | No                | Sí              | Sí            |
| Editar jornada ajena                    | No     | No                | No              | Sí            |
| Cargar horas externas                   | No     | No                | No              | Sí            |
| Ver propias novedades                   | Sí     | Sí (representado) | Sí              | Sí            |
| Ver novedades de otros alumnos          | No     | No                | Sí              | Sí            |
| Ver información de salud propia         | Sí     | Sí (representado) | Sí              | Sí            |
| Registrar información de salud          | No     | No                | Sí (a su cargo) | Sí            |
| Generar reportes                        | No     | No                | Parcial         | Sí            |
| Gestión de configuración / Institución  | No     | No                | No              | Sí            |
| Gestión de Roles y Permisos             | No     | No                | No              | Sí            |

> [!IMPORTANT]
> A partir de la Versión 6.1, la seguridad se ha reforzado con una **doble capa de validación**:
>
> 1.  **Backend (Middleware + Gates/Policies):** Todas las acciones de escritura (`store`, `update`, `delete`) en el panel administrativo están protegidas por `Gate::authorize`.
> 2.  **Frontend (Conditional Rendering):** La interfaz de usuario oculta proactivamente enlaces de navegación (Sidebar) y botones de acción (Editar, Borrar) para usuarios que no poseen el permiso correspondiente, evitando intentos de acceso fallidos y mejorando la UX.

### 4. Gestión de Usuarios

La tabla `users` es la entidad central de autenticación del sistema. Se decidió mantener una única tabla para todos los tipos de usuario en lugar de tablas separadas por rol (Usuario, Profesor, Alumno), porque la diferencia de datos entre roles es mínima: solo dos campos son exclusivos de alumnos transferidos (`institution_origin` e `is_transfer`). Crear tablas separadas solo para sostener estos dos campos introduciría JOINs innecesarios en consultas frecuentes sin ningún beneficio arquitectónico real. La integridad de que esos campos solo apliquen a alumnos se garantiza por código. Los campos `phone` y `address` son datos de contacto personal presentes en todos los usuarios, aunque su obligatoriedad en el momento del registro depende de quién cree la cuenta, como se detalla en la sección 3.1.

#### 3.1 Registro de Usuarios

El sistema diferencia estrictamente entre el autoregistro público y la gestión administrativa de usuarios para equilibrar la usabilidad del alumno con el control institucional.

##### 3.1.1 — Autoregistro Público (`/register`)

Este canal es **exclusivo para alumnos**. Los roles de profesor, administrador y representante se gestionan internamente para prevenir el acceso no autorizado de personal no verificado.

| Campo                | Regla               | Razón de Negocio                                                                                                                                                                                                                                                           |
| :------------------- | :------------------ | :------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Rol por defecto**  | `alumno`            | El sistema está diseñado centrífugaMENTE alrededor del alumno; cualquier acceso externo inicial se presume como tal.                                                                                                                                                       |
| **Datos Personales** | **Obligatorios**    | Al ser un autoregistro, el usuario conoce sus datos. Exigirlos desde el inicio **evita la existencia de "perfiles fantasma"** que requerirían labor administrativa posterior para completarlos. El alumno es responsable de la integridad de su ficha de contacto inicial. |
| **Estado inicial**   | `is_active = true`  | Permite la usabilidad inmediata del sistema tras el registro.                                                                                                                                                                                                              |
| **Transferencias**   | Lógica condicionada | Si `is_transfer` es `true`, la `institution_origin` es obligatoria. Si es `false`, el sistema **automatiza** el nombre de la institución local desde la tabla `institution` para reducir errores de digitación y simplificar el flujo.                                     |

##### 3.1.2 — Registro Administrativo (`/admin/users/create`)

Permite al Administrador (o Profesor con permisos) crear usuarios con cualquier rol, adaptándose a la realidad operativa del plantel.

| Rol a Crear         | Reglas de Validación               | Justificación Operativa                                                                                                                                                                                                                                                                      |
| :------------------ | :--------------------------------- | :------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Alumno**          | **Flexibilidad total** (Nullables) | Es común que al momento de la inscripción académica la institución no disponga de todos los datos personales (teléfono, dirección) del estudiante. El admin puede crear la ficha básica para **facilitar la carga rápida** y el alumno tiene la responsabilidad de completarla al loguearse. |
| **Docente / Admin** | **Datos Obligatorios**             | Debido a su alto nivel de responsabilidad y contacto con menores, **no deben existir perfiles de personal institucional sin datos verificados**. La institución debe garantizar que sabe cómo contactar a su personal de forma inmediata.                                                    |
| **Representante**   | **Datos Obligatorios**             | Similar a los docentes, se requiere información de contacto completa para el seguimiento de los representados. La responsabilidad legal del representante exige trazabilidad absoluta desde su creación.                                                                                     |

> [!NOTE]
> Esta arquitectura de registro asegura que la base de datos mantenga integridad referencial y de datos sin sacrificar la agilidad administrativa necesaria durante los periodos de inscripción escolar.

#### 3.2 División de Datos por Responsabilidad

Los campos del perfil de usuario están divididos en dos grupos según quién puede editarlos. Esta división no es arbitraria, responde a la naturaleza de cada dato:

| Campo                             | Gestionado por                    | Razón                                                                                                           |
| --------------------------------- | --------------------------------- | --------------------------------------------------------------------------------------------------------------- |
| Nombre completo                   | Profesor / Admin                  | Es un dato oficial que no debe ser alterado por el alumno.                                                      |
| Cédula / ID                       | Profesor / Admin                  | Identificador institucional oficial, no modificable por el alumno.                                              |
| Institución de procedencia        | Profesor / Admin / Automático     | Dato verificado por la institución, especialmente en transferidos.                                              |
| Clasificación interno/transferido | Profesor / Admin / Propio usuario | Determina si aplican horas externas, decisión institucional.                                                    |
| Grado y sección por año escolar   | Profesor / Admin                  | Gestión académica exclusiva de la institución.                                                                  |
| Correo electrónico                | Propio usuario                    | Dato de contacto personal, el usuario es dueño de él.                                                           |
| Contraseña                        | Propio usuario                    | Dato de seguridad personal.                                                                                     |
| Teléfono                          | Propio usuario                    | Dato de contacto personal.                                                                                      |
| Dirección de residencia           | Propio usuario                    | Dato de residencia del estudiante. Necesario para contacto de emergencia y seguimiento de incidencias de salud. |
| Foto de perfil                    | Propio usuario                    | Dato personal, gestionado vía Spatie Media Library.                                                             |

> La foto de perfil no se almacena como campo en la tabla `users`. Se gestiona mediante Spatie Laravel Media Library, que maneja el almacenamiento físico y la relación polimórfica a través de su propia tabla `media`. Esto evita rutas de archivos en la base de datos y unifica el manejo de todos los adjuntos del sistema bajo una sola estrategia.

#### 3.3 Entidad Institution (Datos Institucionales)

Para gestionar la información núcleo del sistema, la configuración general fue reemplazada por una entidad sólida y dedicada a nivel principal llamada `Institution`. Su objetivo es centralizar la gestión de datos de la sede actual (que será tratada al mismo nivel que `users`).

La arquitectura de la entidad está conformada por:

- **Modelo:** `App\Models\Institution`.
- **Base de Datos:** Tabla `institution` (en singular, porque representa a la única sede organizativa que gestiona el sistema).
- **Campos:** `name`, `address`, `email`, `phone`, `code`, `timestamps`.
- **Seeder:** `InstitutionSeeder` (crea el registro por defecto "Amantina de Sucre").
- **Controlador:** Controlador independiente `InstitutionController` con acciones de edición/actualización usando el patrón _wayfinder_.
- **Interfaz (UI):** Menú lateral del Frontend listado bajo "Datos Institucionales".

> **Consideración de Seguridad Crítica (Futuro Hito 2):** Debido a que la tabla `institution` almacena los identificadores globales de la aplicación, **esta información es altamente crítica**. Para el Hito 2, esta sección de Datos Institucionales deberá ser restringida estrictamente a través de `Spatie Permissions`, asegurando que su visualización y edición solo pueda ser realizada por usuarios con rol de `Administrador`. No abordar esto genera una vulnerabilidad organizativa grave.

### 5. Representantes

La incorporación del rol de representante responde a una solicitud directa del cliente: los representantes deben poder consultar en el sistema el historial y estado de horas de sus representados. Es un actor de solo lectura cuya única capacidad es ver la información de su representado, sin poder modificar nada.

Un representante puede tener más de un estudiante a su cargo. Esto refleja una situación real y común: dos hermanos en la misma institución con el mismo representante. Por ello la relación es de muchos a muchos entre usuarios de rol representante y usuarios de rol estudiante, materializada en la tabla `student_representatives`.

#### 4.1 Tabla `student_representatives`

Esta tabla pivot existe para modelar la relación entre representante y estudiante. Incluye el tipo de parentesco porque la institución necesita saber en qué calidad actúa cada representante respecto al estudiante. Esta información puede ser relevante en situaciones administrativas o legales.

```text
student_representatives
  id
  student_id              FK -> users (alumno)
  representative_id       FK -> users (representante)
  relationship_type_id    FK -> relationship_types
  timestamps + deleted_at

  Índice único: student_id + representative_id
  Evita vincular al mismo representante con el mismo
  estudiante más de una vez.
```

#### 4.2 Tabla `relationship_types`

Es un catálogo configurable de tipos de parentesco (Padre, Madre, Tutor legal, Otro). Se decidió mantenerlo como tabla independiente en lugar de un campo de texto libre o un ENUM por dos razones: permite que la institución gestione y extienda el catálogo sin modificar el código, y garantiza consistencia en los valores almacenados al ser una FK en lugar de un texto arbitrario.

#### 4.3 Caso de usuario administrador-profesor-representante (usuario con roles múltiples)

Un profesor activo puede ser simultáneamente representante de un estudiante. Este caso es real: uno de los directivos de la institucion es a la vez representante de un estudiante. El sistema lo resuelve permitiendo que un usuario tenga ambos roles asignados en Spatie, y gestionando el contexto de sesión al momento del login mediante el parámetro `context` descrito en la sección de autenticación, el cual permitira a ese mismo usuario loguearse como desee en el momento, como administrador, como profesor o como representante, en el caso de que tenga asignados esos roles.

### 6. Información de Salud del Estudiante

La información de salud se incorporó porque los estudiantes realizan actividades físicas en campo, y el profesor necesita conocer condiciones médicas relevantes para asignar actividades acordes a cada alumno. No es para eximir al estudiante de cumplir sus horas, sino para garantizar que las actividades asignadas no atenten contra su condición médica.

> La información de salud no reduce ni modifica el cupo de horas requeridas. No genera alertas automáticas en el registro de jornadas. Es información consultable en el perfil del estudiante para que el profesor la considere al planificar actividades.

#### 5.1 Tabla `health_conditions`

Catálogo de condiciones de salud conocidas. Se eligió un catálogo configurable más un campo de observaciones libres porque cubre dos necesidades: estandarizar las condiciones más comunes para facilitar búsquedas y reportes, y permitir descripción libre para condiciones no contempladas en el catálogo o detalles específicos del caso del estudiante.

#### 5.2 Tabla `student_health_records`

Cada registro vincula a un estudiante con una condición de salud. Incluye los siguientes campos adicionales que responden a un proceso formal institucional:

| Campo                            | Razón de existir                                                                                             |
| -------------------------------- | ------------------------------------------------------------------------------------------------------------ |
| `received_by` (FK -> users)      | Registra quién en la institución recibió la documentación médica. Es parte del proceso formal de validación. |
| `received_at` (DATETIME)         | Registra cuándo se recibió la documentación. Genera trazabilidad y evita registros arbitrarios sin fecha.    |
| `received_at_location` (VARCHAR) | Registra dónde se realizó la entrega. Refuerza el carácter formal del proceso.                               |
| `observations` (TEXT nullable)   | Campo libre para detalles específicos de la condición del estudiante que el catálogo no cubre.               |

Los archivos de soporte médico (certificados, informes médicos, etc.) se adjuntan mediante Spatie Laravel Media Library en el modelo `StudentHealthRecord`, sin necesidad de campos adicionales en la tabla.

Solo administradores y profesores pueden registrar y editar esta información. La restricción existe porque es un proceso formal que requiere verificación de documentación física en la institución. Un representante o estudiante no puede cargar esta información por cuenta propia.

### 7. Estructura Académica

La jerarquía académica del sistema refleja la organización real de la institución educativa. No se inventó una estructura arbitraria, sino que se modeló exactamente como la institución organiza a sus estudiantes y profesores. Entender esta jerarquía es fundamental para entender cómo fluyen los datos en todo el sistema.

```text
Jerarquía académica:

  AcademicYear  (ej: 2025-2026)
    └── SchoolTerm  (1er lapso, 2do lapso, 3er lapso)
    └── Grade  (1er año, 2do año, ..., 5to año)
          └── Section  (A, B, C...)
                ├── Enrollment  -> User (estudiante)
                └── TeacherAssignment -> User (profesor)
```

#### 6.1 Tabla `academic_years`

Cada año escolar es un registro independiente con sus propias fechas y su propio cupo de horas requeridas. El cupo no es global porque puede variar entre promociones: una generación puede tener un cupo de 500 horas y la siguiente de 600, según las políticas institucionales vigentes en cada periodo.

| Campo                      | Razón de existir                                                                                                                          |
| -------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------- |
| `name` (VARCHAR)           | Almacenado explícitamente (no calculado) para evitar formateo en cada consulta y permitir flexibilidad si la convención de nombre cambia. |
| `is_active` (BOOLEAN)      | Indica cuál es el año escolar vigente institucionalmente. Solo uno puede estar activo a la vez, gestionado por código.                    |
| `required_hours` (DECIMAL) | Cupo de horas requeridas para esta promoción. Configurable por año porque puede variar entre generaciones.                                |

#### 6.2 Tabla `school_terms` (Lapsos)

La institución divide cada año escolar en 3 lapsos. Esta segmentación es necesaria porque los reportes y estadísticas deben poder consultarse por lapso: cuántas jornadas hubo en el primer lapso, cuántas horas acumuló un estudiante en el segundo, etc. Sin esta tabla, no existiría forma de segmentar temporalmente la información dentro de un año escolar.

| Campo                     | Razón de existir                                                                                                                                                             |
| ------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `term_number` (TINYINT)   | Asignado automáticamente por el backend contando los lapsos existentes del año escolar. Evita errores de numeración manual. No puede superar 3.                              |
| `start_date` / `end_date` | Permiten asignación automática del lapso a cada jornada. Al crear una sesión, el backend compara `start_datetime` contra estas fechas para determinar a qué lapso pertenece. |

> Los lapsos deben ser configurados por el administrador **ANTES** de que los profesores comiencen a registrar jornadas. Si no existen lapsos configurados para el año escolar activo, el sistema retornará error al intentar crear una jornada.

#### 6.3 Tabla `grades`

Los grados no son un catálogo global reutilizable. Cada año escolar tiene sus propios registros de grado. Esto permite que en el futuro la institución pueda tener configuraciones distintas por año si lo requiere, y garantiza que cada grado esté inequívocamente asociado a un periodo académico específico.

| Campo             | Razón de existir                                                                                                                                                                       |
| ----------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `order` (TINYINT) | El ordenamiento correcto de grados no puede derivarse del nombre si este es texto libre. Este campo garantiza que '1er año' siempre aparezca antes que '2do año' en cualquier listado. |

#### 6.4 Tabla `sections`

Las secciones son el nivel más bajo de la jerarquía académica y el punto de convergencia entre estudiantes y profesores. Una sección pertenece a un grado dentro de un año escolar específico.

Esta tabla incluye desnormalización selectiva del campo `academic_year_id` aunque ya es derivable a través de la FK a `grades`. La razón es pragmática: consultas frecuentes como 'todas las secciones del año escolar activo' se ejecutan sin JOINs adicionales. El dato es estable: una sección no cambia de año escolar una vez creada, por lo que el riesgo de inconsistencia es mínimo.

```text
Índice único: academic_year_id + grade_id + name
Garantiza que no existan dos secciones 'A' en el
mismo grado dentro del mismo año escolar.
```

#### 6.5 Tabla `enrollments`

Registra en qué sección está inscrito cada estudiante durante cada año escolar. Esta tabla es lo que permite rastrear al estudiante como identidad continua: el perfil del usuario es permanente, pero su inscripción cambia cada año escolar (diferente grado, diferente sección).

Si un estudiante reprueba, tendrá dos registros en `enrollments` con el mismo `grade_id` pero diferente `academic_year_id`. Esto es correcto y esperado: refleja que el estudiante cursó el mismo grado en dos periodos distintos.

| Campo                         | Razón de existir                                                                                                        |
| ----------------------------- | ----------------------------------------------------------------------------------------------------------------------- |
| `academic_year_id` (desnorm.) | Evita navegar enrollment -> section -> grade -> academic_year en consultas como 'todos los estudiantes del año activo'. |
| `grade_id` (desnorm.)         | Evita el mismo JOIN para consultas como 'todos los estudiantes de 3er año'.                                             |

```text
Índice único: user_id + academic_year_id
Un estudiante no puede tener dos inscripciones en
el mismo año escolar.
```

#### 6.6 Tabla `teacher_assignments`

Registra qué profesor es responsable de la materia Socioproductiva en cada sección de cada año escolar. La asignación es por sección (no por grado) porque en la práctica puede haber profesores diferentes por sección: sección A la lleva el profesor González y sección B el profesor Martínez, aunque pertenezcan al mismo grado.

Esta tabla es estructuralmente similar a `enrollments`, con la misma lógica de desnormalización. La asignación cambia cada año escolar y eso es esperado: los profesores responsables de Socioproductiva pueden rotar completamente de un periodo a otro.

```text
Índice único: academic_year_id + section_id + user_id
Un profesor no puede estar asignado dos veces a la
misma sección en el mismo año escolar.
Sin embargo, una sección puede tener más de un
profesor asignado (sin restricción en ese sentido).
```

#### 6.7 Proceso de Inscripción y Asignación

El proceso de inscripción de alumnos y asignación de profesores a secciones está diseñado para cubrir dos escenarios operativos reales del plantel, cada uno con su propio flujo:

##### 6.7.1 Reglas de Negocio

Las siguientes reglas son invariantes del sistema y deben ser validadas tanto en el backend (Form Requests) como reflejadas en el frontend (UI deshabilitada o mensajes de advertencia):

| Regla                                              | Descripción                                                                                                                                                                                                                   | Motivo                                                                                                                         |
| -------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| **RN-1: Solo año activo**                          | Las inscripciones y asignaciones únicamente se realizan contra el año escolar cuyo campo `is_active = true`.                                                                                                                  | Evita inscripciones accidentales en años pasados o futuros no configurados.                                                    |
| **RN-2: Estructura previa obligatoria**            | El año activo debe tener al menos un grado con al menos una sección configurados antes de permitir inscripciones. Si no se cumple, el sistema muestra un banner de advertencia con enlace a la gestión de grados y secciones. | Un administrador despistado podría activar un año sin haber configurado su estructura. El sistema debe guiarlo explícitamente. |
| **RN-3: Unicidad por año**                         | Un alumno solo puede tener una inscripción activa por año escolar (índice único `user_id + academic_year_id`).                                                                                                                | Refleja la realidad: un estudiante pertenece a una sola sección por año.                                                       |
| **RN-4: Solo rol alumno en enrollments**           | El campo `user_id` en `enrollments` debe corresponder a un usuario con el rol `alumno`.                                                                                                                                       | Evita inscribir accidentalmente a profesores o representantes como estudiantes.                                                |
| **RN-5: Solo rol profesor en teacher_assignments** | El campo `user_id` en `teacher_assignments` debe corresponder a un usuario con el rol `profesor`.                                                                                                                             | Evita asignar a un alumno como docente responsable de una sección.                                                             |
| **RN-6: Integridad jerárquica**                    | El `grade_id` debe pertenecer al `academic_year_id` indicado, y el `section_id` debe pertenecer al `grade_id` indicado.                                                                                                       | Evita inconsistencias entre la estructura académica y las inscripciones.                                                       |

##### 6.7.2 Flujo "Promoción Masiva" (Panel de Promoción)

Este es el flujo principal y más frecuente. Se usa al inicio de cada año escolar para trasladar a los alumnos del año anterior al siguiente. Opera con un layout de **dos paneles** lado a lado:

**Panel Izquierdo (Origen):** Muestra los alumnos inscritos en el año escolar anterior, filtrados por grado y sección. Permite selección múltiple mediante checkboxes.

**Panel Derecho (Destino):** Muestra las secciones disponibles del año activo agrupadas por el grado sugerido. Cada sección se presenta como una tarjeta con un botón "Promover aquí" y un contador de alumnos ya inscritos.

**Flujo operativo detallado:**

1. **Selección de origen**: El administrador elige el año escolar anterior, un grado y una sección de origen. El sistema carga la lista de alumnos inscritos en esa combinación.

2. **Selección de alumnos**: La lista presenta cada alumno con checkbox, nombre y cédula. Los alumnos que ya están inscritos en el año activo aparecen con un badge "Ya inscrito" y su checkbox está deshabilitado. Un botón "Seleccionar Todos" marca únicamente a los alumnos elegibles. Un contador muestra "X alumnos seleccionados".

3. **Sugerencia inteligente de grado**: El panel derecho pre-carga las secciones del grado cuyo campo `order` sea igual a `order_del_grado_origen + 1` dentro del año activo. Ejemplo: si el origen es "1er Año" (`order: 1`), el destino sugiere "2do Año" (`order: 2`). Si ese grado no existe en el año activo (porque el administrador no lo ha creado), el panel muestra un selector de grado para elección manual. La sugerencia se señala con un badge visual "(Grado sugerido)".

4. **Ejecución de la promoción**: Al hacer clic en "Promover aquí" en una sección destino, se presenta un diálogo de confirmación con un resumen: origen (grado/sección/año), destino (grado/sección/año), y cantidad de alumnos. Al confirmar, el backend crea un registro en `enrollments` por cada alumno seleccionado con los datos del destino. Los alumnos promovidos desaparecen del panel izquierdo y el contador de la sección destino se actualiza sin recargar la página.

5. **Repetición**: El administrador puede seleccionar otro subconjunto de alumnos del mismo origen y promoverlos a una sección diferente (ej: parte a Sección A, parte a Sección B, resto a Sección C), cubriendo el escenario real de redistribución de alumnos entre secciones.

> **Ejemplo operativo completo:** El adminstrador abre el Panel de Promoción. Selecciona como origen "2024-2025 → 1er Año → Sección A" (30 alumnos). Selecciona 10 alumnos y los promueve a "2do Año → Sección A". Selecciona otros 10 y los promueve a "2do Año → Sección B". Selecciona los 10 restantes y los promueve a "2do Año → Sección C". Luego cambia el origen a "1er Año → Sección B" y repite el proceso. Al finalizar, todos los alumnos del 1er año del año anterior están inscritos en el 2do año del año activo, distribuidos en las secciones correspondientes.

##### 6.7.3 Flujo "Nuevo Ingreso" (Inscripción Individual)

Este flujo es para alumnos que no estaban en el sistema el año anterior: nuevos ingresos, alumnos transferidos de otras instituciones, o cualquier alumno sin inscripción previa.

Consiste en un formulario individual con:

1. **Buscar Alumno**: Campo de búsqueda con autocompletado que filtra por nombre o cédula entre los usuarios con rol `alumno` que NO tengan inscripción en el año activo.
2. **Grado**: Selector con los grados del año activo. No aplica sugerencia (no hay historial).
3. **Sección**: Selector filtrado dinámicamente por el grado elegido.

Este formulario no incluye sugerencia de grado porque, al tratarse de un nuevo ingreso, no existe historial académico previo en el sistema para inferir el grado correspondiente. El administrador asigna el grado basándose en la documentación administrativa externa del alumno.

##### 6.7.4 Asignación de Profesores

La asignación de profesores a secciones es un proceso más sencillo que la inscripción de alumnos, dado que la cantidad de profesores es significativamente menor. Se implementa como un formulario individual con:

1. **Profesor**: Selector o búsqueda entre usuarios con rol `profesor`.
2. **Grado**: Selector con grados del año activo.
3. **Sección**: Selector filtrado por grado.

Un profesor puede estar asignado a múltiples secciones en el mismo año. Una sección puede tener múltiples profesores asignados. Ambas situaciones son válidas operativamente.

### 8. Jornadas de Campo

Las jornadas son el evento central del sistema. Todo gira alrededor de ellas: la asistencia se registra en jornadas, las horas se acreditan a partir de jornadas, los reportes se construyen sobre jornadas. Una jornada es una actividad de campo realizada en una fecha y lugar específicos, liderada por un profesor responsable.

#### 7.1 Tabla `field_sessions`

El nombre `field_sessions` fue definido para evitar el conflicto con la tabla `sessions` que Laravel reserva para el manejo de sesiones HTTP. Las alternativas evaluadas fueron: `workdays` (descartada por connotación de oficina), `field_days` (descartada por ambigua) y `work_sessions` (descartada por similar motivo a workdays). `field_sessions` es el nombre que mejor describe el concepto: sesiones de trabajo de campo.

| Campo                                 | Razón de existir                                                                                                                                                                                                                                                     |
| ------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `academic_year_id` (desnorm.)         | Consultas por año escolar son las más frecuentes del sistema. Sin este campo habría que navegar session -> attendance -> enrollment -> academic_year en cada reporte.                                                                                                |
| `school_term_id`                      | Indica a qué lapso pertenece la jornada. Se asigna automáticamente comparando `start_datetime` contra las fechas de los lapsos del año escolar.                                                                                                                      |
| `user_id` (profesor responsable)      | Define quién creó y es dueño de la jornada. Solo este profesor (o un admin) puede editarla. Es la base de la regla de propiedad.                                                                                                                                     |
| `activity_category_id` (nullable)     | Categoría general opcional de la jornada. Es nullable porque el profesor puede preferir categorizar a nivel individual en cada subactividad.                                                                                                                         |
| `location_id` (nullable)              | La jornada puede realizarse en la institución o en un lugar externo. Es nullable porque no siempre se registra la ubicación.                                                                                                                                         |
| `start_datetime` / `end_datetime`     | Reemplazan el concepto de 'turno' (mañana, tarde). La fecha y hora exacta de inicio y fin es más precisa y útil que una etiqueta.                                                                                                                                    |
| `base_hours` (DECIMAL)                | Calculado automáticamente desde la diferencia entre start y end datetime. Sirve como referencia de horas acreditables planificadas y como tope de validación al acreditar horas a estudiantes. Se almacena explícitamente para evitar recalcularlo en cada consulta. |
| `status` (ENUM: realized, cancelled)  | Una jornada cancelada debe existir en el sistema con su motivo documentado, aunque no acredite horas a nadie. Sin este campo una jornada cancelada simplemente desaparecería.                                                                                        |
| `cancellation_reason` (TEXT nullable) | Obligatorio si status = cancelled. Documenta el motivo de la cancelación como antecedente.                                                                                                                                                                           |

> La tabla `shifts` (turnos) fue evaluada y descartada. Inicialmente se consideró para categorizar jornadas como mañana/tarde/noche, pero resultó redundante e incluso contradictoria: si la jornada tiene `start_datetime` y `end_datetime`, el turno se puede inferir. Mantenerla generaría inconsistencias si el turno no coincide con el horario registrado.

#### 7.2 Regla de Propiedad de Jornada

Esta es una regla de negocio crítica: solo el profesor responsable de una jornada (`user_id` en `field_sessions`) o un administrador puede editar sus registros. Un profesor puede ver las jornadas de otros profesores pero no modificarlas. Esta regla existe para mantener la integridad de los datos: cada profesor es responsable de la veracidad de lo que registró, y no debe poder alterar lo que otro profesor registró.

### 9. Registro de Asistencia y Horas

El registro de asistencia y horas es el núcleo funcional del sistema. Aquí es donde se determina cuántas horas acumula cada estudiante. El modelo se estructuró en dos niveles de granularidad para cubrir tanto profesores con registros detallados como profesores con registros más generales.

#### 8.1 Tabla `attendances`

Registra si un estudiante asistió o no a una jornada, y permite agregar notas generales sobre su desempeño. Las horas acreditadas **NO** se almacenan en esta tabla: son la suma de las horas de todas sus subactividades en `attendance_activities`. Esta decisión separa claramente la asistencia (hecho binario) de las horas (resultado de actividades).

| Campo                         | Razón de existir                                                                                                                                                                                                                                                                                   |
| ----------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `academic_year_id` (desnorm.) | El cálculo de horas acumuladas por estudiante en un año escolar es la consulta más frecuente del sistema. Con este campo: `SELECT SUM(aa.hours) FROM attendance_activities aa JOIN attendances a ON aa.attendance_id = a.id WHERE a.user_id = X AND a.academic_year_id = Y AND a.attended = true`. |
| `attended` (BOOLEAN)          | Si es false, no se acreditan horas independientemente de lo que haya en `attendance_activities`.                                                                                                                                                                                                   |
| `notes` (TEXT nullable)       | Novedades o incidencias generales del estudiante en la jornada. Sirve como antecedente para justificar variaciones en las horas acreditadas.                                                                                                                                                       |

```text
Índice único: session_id + user_id
Un estudiante no puede tener dos registros de
asistencia en la misma jornada.
```

#### 8.2 Tabla `attendance_activities` (Subactividades)

Esta tabla existe para soportar diferentes niveles de granularidad en el registro. La realidad operativa es que algunos profesores llevan un registro riguroso de qué hizo cada estudiante en cada hora de la jornada, y otros simplemente registran el total de horas sin detalle de actividades. El modelo cubre ambos escenarios: el profesor riguroso registra múltiples subactividades con horas distribuidas; el profesor laxo registra una sola subactividad con el total.

Un caso concreto que motivó esta tabla: en una jornada de 4 horas, un estudiante pudo haber dedicado 1 hora a desmalezamiento, 1 hora a limpieza, 1 hora a siembra y 1 hora a riego. Sin esta tabla, solo se podría categorizar la jornada con una sola actividad general, perdiendo el detalle.

| Campo                       | Razón de existir                                                                                                                                                 |
| --------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `attendance_id` (FK)        | Vincula la subactividad con el registro de asistencia del estudiante específico en la jornada específica.                                                        |
| `activity_category_id` (FK) | Categoriza la subactividad. Permite reportes y estadísticas por tipo de actividad.                                                                               |
| `hours` (DECIMAL)           | Horas dedicadas a esta subactividad específica. La suma de todos los registros con el mismo `attendance_id` es el total acreditado al estudiante en esa jornada. |
| `notes` (TEXT nullable)     | Observaciones específicas de esta subactividad. Más granular que las notas del `attendance`.                                                                     |

Los archivos de evidencia (fotos, videos, documentos) de cada subactividad se adjuntan mediante Spatie Laravel Media Library en el modelo `AttendanceActivity`. Esto permite vincular evidencia específica a cada actividad realizada, no solo a la jornada en general.

> El sistema alerta cuando la suma de horas de subactividades supera `base_hours` de la jornada, pero no bloquea. La integridad del dato es responsabilidad del profesor. Esta decisión es intencional: un alumno puede haber llegado puntual a una jornada que se retrasó, y el profesor puede querer acreditarle las horas completas planificadas aunque la duración real haya sido menor.

#### 8.3 Escenarios de Acreditación

| Escenario                            | Resultado                                                               | Requiere novedad           |
| ------------------------------------ | ----------------------------------------------------------------------- | -------------------------- |
| Alumno asistió y trabajó normalmente | Se acreditan las horas de sus subactividades                            | No                         |
| Alumno asistió pero no trabajó       | Horas acreditadas = 0 (`attendance_activities` vacía o con 0)           | Sí, obligatoria            |
| Alumno con desempeño excepcional     | Horas superiores a `base_hours`. Sistema alerta, no bloquea             | Sí, justificatoria         |
| Jornada cancelada                    | Ningún estudiante acumula horas. La sesión queda con `status=cancelled` | Sí (motivo de cancelación) |
| Alumno no asistió                    | `attended=false`. No se procesan subactividades.                        | No                         |

### 10. Horas Externas

Las horas externas existen para cubrir un caso real e importante: estudiantes que cursaron parte de su bachillerato en otra institución educativa y necesitan que esas horas de Socioproductiva sean reconocidas. Por ejemplo, un alumno que cursó 1ro a 4to en otra institución y llega al 5to año con 400 horas ya acumuladas.

Solo un administrador puede cargar horas externas porque es un proceso formal que requiere verificación de documentación. El administrador adjunta el documento de respaldo mediante Spatie Media Library y las horas se suman directamente al acumulado del estudiante sin flujo adicional de validación, dado que quien las carga ya posee el rol de mayor confianza en el sistema.

| Campo                            | Razón de existir                                                                                                                                                                        |
| -------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `academic_year_id` (FK)          | Necesario por diseño, no solo desnormalización. Las horas externas no se originan de una jornada del sistema, por lo que es la única forma de saber a qué periodo académico imputarlas. |
| `user_id` (FK -> estudiante)     | El estudiante beneficiado por las horas.                                                                                                                                                |
| `admin_id` (FK -> usuario admin) | El administrador que cargó las horas. Ambos apuntan a `users` pero con roles distintos. Permite trazabilidad de quién autorizó la acreditación.                                         |
| `institution_name` (VARCHAR)     | Nombre de la institución de origen. Junto al documento adjunto, sustenta la validez de las horas.                                                                                       |

### 11. Cálculo de Horas Acumuladas

El cálculo del acumulado de horas de un estudiante es la consulta más crítica del sistema. Debe ser preciso, eficiente y siempre actualizado. La desnormalización selectiva de `academic_year_id` en `attendances` fue motivada principalmente por optimizar este cálculo específico.

```sql
Horas por año escolar:
  SUM(attendance_activities.hours)
    JOIN attendances WHERE user_id = X
      AND academic_year_id = Y
      AND attended = true
  + SUM(external_hours.hours)
      WHERE user_id = X AND academic_year_id = Y

Horas por lapso:
  SUM(attendance_activities.hours)
    JOIN attendances WHERE user_id = X
    JOIN field_sessions WHERE school_term_id = Z
      AND attended = true

Total histórico:
  SUM de todos los años escolares cursados

Horas restantes:
  academic_years.required_hours - Total histórico
```

La vista del estudiante al loguearse muestra permanentemente: horas acumuladas en el año activo, horas acumuladas por lapso dentro del año activo, horas acumuladas en años anteriores, total histórico acumulado y horas restantes para completar el cupo. Esta información es el propósito final del sistema y debe ser siempre visible y precisa.

### 12. Configuración del Sistema

El módulo de configuración gestiona todos los catálogos y parámetros que definen el comportamiento del sistema. Solo los administradores tienen acceso a este módulo.

| Entidad configurable              | Tabla                 | Observación                                                                                              |
| --------------------------------- | --------------------- | -------------------------------------------------------------------------------------------------------- |
| Años escolares                    | `academic_years`      | CRUD + activación del año vigente. El cupo de horas se configura aquí.                                   |
| Lapsos                            | `school_terms`        | Hasta 3 por año escolar. Número asignado automáticamente. Validación de no solapamiento.                 |
| Grados                            | `grades`              | Por año escolar. Con campo `order` para ordenamiento correcto.                                           |
| Secciones                         | `sections`            | Por grado. Nombre único dentro del mismo grado y año escolar.                                            |
| Categorías de actividad           | `activity_categories` | CRUD con activación/desactivación. Desactivar no elimina el histórico.                                   |
| Ubicaciones                       | `locations`           | CRUD. Nombre y dirección. Opcional en jornadas.                                                          |
| Tipos de parentesco               | `relationship_types`  | CRUD con activación/desactivación. Para vincular representantes a estudiantes.                           |
| Condiciones de salud              | `health_conditions`   | CRUD con activación/desactivación. Catálogo de condiciones médicas conocidas.                            |
| **Datos Institucionales (Nuevo)** | `institution`         | Datos de la sede institucional (`name`, `address`, `email`, `phone`, `code`). **(Hito 1 / RBAC Hito 2)** |

### 13. Reportes y Estadísticas

Los reportes son generados automáticamente en esta primera fase, sin configuración por parte del usuario. La decisión de empezar con reportes predefinidos en lugar de reportes dinámicos es de alcance: los reportes dinámicos requieren una capa de UI significativamente más compleja y no son necesarios para la funcionalidad core del sistema.

Todos los reportes pueden segmentarse por lapso además de por año escolar, gracias a la tabla `school_terms` y la FK `school_term_id` en `field_sessions`. Esto fue un requerimiento explícito del cliente: la información debe poder consultarse y reportarse por lapso de forma independiente.

| Reporte                            | Segmentación           | Destinatario                |
| ---------------------------------- | ---------------------- | --------------------------- |
| Resumen de horas del estudiante    | Año escolar / Lapso    | Estudiante, Profesor, Admin |
| Asistencia por jornada             | Por jornada específica | Profesor, Admin             |
| Historial completo de horas en PDF | Año escolar / Lapso    | Profesor, Admin             |
| Horas por grado y sección          | Año escolar / Lapso    | Admin                       |
| Novedades e incidencias            | Por periodo / Lapso    | Admin                       |
| Analítica de cumplimiento          | Año escolar / Lapso    | Admin                       |

> **Fase 2 (fuera de alcance actual):** Reportes dinámicos con filtros configurables por el usuario.

### 14. Modelo de Datos

El modelo de datos fue definido iterativamente y cada decisión tiene un razonamiento detallado en las secciones anteriores. A continuación se presenta la estructura final de cada tabla con sus campos y la justificación de los más relevantes.

#### 13.1 Convenciones Generales

| Convención          | Descripción                                                                |
| ------------------- | -------------------------------------------------------------------------- |
| Framework           | Laravel (PHP)                                                              |
| Nomenclatura        | snake_case, plural, en inglés                                              |
| Soft deletes        | `deleted_at` en todas las tablas. Ningún dato se elimina físicamente.      |
| Timestamps          | `created_at` y `updated_at` en todas las tablas, gestionados por Laravel.  |
| Gestión de archivos | Spatie Laravel Media Library. Tabla media polimórfica centralizada.        |
| Roles y permisos    | Spatie Laravel Permissions. Soporta múltiples roles por usuario.           |
| Desnormalización    | Aplicada selectivamente en campos estables de alta frecuencia de consulta. |

#### 13.2 Tablas con Archivos Adjuntos (Spatie Media Library)

| Modelo                | Propósito del adjunto                                               |
| --------------------- | ------------------------------------------------------------------- |
| `User`                | Foto de perfil del usuario                                          |
| `AttendanceActivity`  | Evidencias fotográficas/video de cada subactividad realizada        |
| `ExternalHour`        | Documento de respaldo de horas externas de otra institución         |
| `StudentHealthRecord` | Soportes médicos que respaldan la condición de salud del estudiante |

#### 13.3 Estructura de Tablas

```sql
users
  id                   BIGINT UNSIGNED PK
  name                 VARCHAR(255) NOT NULL
  cedula               VARCHAR(20)  NOT NULL UNIQUE
  email                VARCHAR(255) NOT NULL UNIQUE
  password             VARCHAR(255) NOT NULL
  phone                VARCHAR(20)  NULL
  address              TEXT         NULL
  institution_origin   VARCHAR(255) NULL   -- Solo estudiantes transferidos
  is_transfer          TINYINT(1)   NULL   -- Solo estudiantes
  is_active            TINYINT(1)   NOT NULL DEFAULT 1
  remember_token       VARCHAR(100) NULL
  timestamps + deleted_at

institution  -- (Nueva Entidad - Hito 1)
  id                   BIGINT UNSIGNED PK
  name                 VARCHAR(255) NOT NULL
  address              TEXT         NULL
  email                VARCHAR(255) NULL
  phone                VARCHAR(20)  NULL
  code                 VARCHAR(50)  NULL
  timestamps + deleted_at

academic_years
  id               BIGINT UNSIGNED PK
  name             VARCHAR(20)   NOT NULL UNIQUE  -- ej: 2025-2026
  start_date       DATE          NOT NULL
  end_date         DATE          NOT NULL
  is_active        TINYINT(1)    NOT NULL DEFAULT 0
  required_hours   DECIMAL(8,2)  NOT NULL
  timestamps + deleted_at

school_terms
  id                BIGINT UNSIGNED PK
  academic_year_id  FK -> academic_years
  term_number       TINYINT UNSIGNED NOT NULL  -- 1, 2 o 3 (auto)
  start_date        DATE NOT NULL
  end_date          DATE NOT NULL
  timestamps + deleted_at
  UNIQUE: academic_year_id + term_number

grades
  id                BIGINT UNSIGNED PK
  academic_year_id  FK -> academic_years
  name              VARCHAR(50)       NOT NULL  -- ej: 1er año
  order             TINYINT UNSIGNED  NOT NULL
  timestamps + deleted_at
  UNIQUE: academic_year_id + order

sections
  id                BIGINT UNSIGNED PK
  academic_year_id  FK -> academic_years  -- desnormalizado
  grade_id          FK -> grades
  name              VARCHAR(10) NOT NULL  -- ej: A, B, C
  timestamps + deleted_at
  UNIQUE: academic_year_id + grade_id + name

locations
  id           BIGINT UNSIGNED PK
  name         VARCHAR(255) NOT NULL
  address      TEXT NULL
  timestamps + deleted_at

activity_categories
  id          BIGINT UNSIGNED PK
  name        VARCHAR(100) NOT NULL UNIQUE
  is_active   TINYINT(1)   NOT NULL DEFAULT 1
  timestamps + deleted_at

health_conditions
  id          BIGINT UNSIGNED PK
  name        VARCHAR(100) NOT NULL UNIQUE
  is_active   TINYINT(1)   NOT NULL DEFAULT 1
  timestamps + deleted_at

relationship_types
  id          BIGINT UNSIGNED PK
  name        VARCHAR(100) NOT NULL UNIQUE  -- ej: Padre, Madre
  is_active   TINYINT(1)   NOT NULL DEFAULT 1
  timestamps + deleted_at

enrollments
  id                BIGINT UNSIGNED PK
  academic_year_id  FK -> academic_years  -- desnormalizado
  grade_id          FK -> grades           -- desnormalizado
  section_id        FK -> sections
  user_id           FK -> users (estudiante)
  timestamps + deleted_at
  UNIQUE: user_id + academic_year_id

teacher_assignments
  id                BIGINT UNSIGNED PK
  academic_year_id  FK -> academic_years  -- desnormalizado
  grade_id          FK -> grades           -- desnormalizado
  section_id        FK -> sections
  user_id           FK -> users (profesor)
  timestamps + deleted_at
  UNIQUE: academic_year_id + section_id + user_id

student_representatives
  id                      BIGINT UNSIGNED PK
  student_id              FK -> users (estudiante)
  representative_id       FK -> users (representante)
  relationship_type_id    FK -> relationship_types
  timestamps + deleted_at
  UNIQUE: student_id + representative_id

student_health_records
  id                    BIGINT UNSIGNED PK
  student_id            FK -> users (estudiante)
  health_condition_id   FK -> health_conditions
  received_by           FK -> users (profesor/admin)
  received_at           DATETIME     NOT NULL
  received_at_location  VARCHAR(255) NOT NULL
  observations          TEXT NULL
  timestamps + deleted_at
  -- Archivos adjuntos vía Spatie Media Library

field_sessions
  id                    BIGINT UNSIGNED PK
  academic_year_id      FK -> academic_years      -- desnormalizado
  school_term_id        FK -> school_terms        -- asignado automáticamente
  user_id               FK -> users (profesor responsable)
  activity_category_id  FK -> activity_categories  NULL
  location_id           FK -> locations            NULL
  start_datetime        DATETIME      NOT NULL
  end_datetime          DATETIME      NOT NULL
  base_hours            DECIMAL(6,2)  NOT NULL  -- calculado automáticamente
  status                ENUM(realized, cancelled) DEFAULT realized
  cancellation_reason   TEXT NULL
  general_observations  TEXT NULL
  timestamps + deleted_at

attendances
  id                BIGINT UNSIGNED PK
  academic_year_id  FK -> academic_years  -- desnormalizado
  session_id        FK -> field_sessions
  user_id           FK -> users (estudiante)
  attended          TINYINT(1) NOT NULL DEFAULT 0
  notes             TEXT NULL
  timestamps + deleted_at
  UNIQUE: session_id + user_id

attendance_activities
  id                    BIGINT UNSIGNED PK
  attendance_id         FK -> attendances
  activity_category_id  FK -> activity_categories
  hours                 DECIMAL(6,2) NOT NULL
  notes                 TEXT NULL
  timestamps + deleted_at
  -- Archivos adjuntos vía Spatie Media Library

external_hours
  id                BIGINT UNSIGNED PK
  academic_year_id  FK -> academic_years
  user_id           FK -> users (estudiante)
  admin_id          FK -> users (administrador que carga)
  hours             DECIMAL(8,2)  NOT NULL
  institution_name  VARCHAR(255)  NOT NULL
  description       TEXT NULL
  timestamps + deleted_at
  -- Archivos adjuntos vía Spatie Media Library
```

#### 13.4 Mapa de Relaciones

```text
academic_years
  └── school_terms
  └── grades
        └── sections
              ├── enrollments       -> users (estudiante)
              └── teacher_assignments -> users (profesor)

field_sessions -> academic_years
field_sessions -> school_terms
field_sessions -> users (profesor)
field_sessions -> activity_categories (nullable)
field_sessions -> locations (nullable)
  └── attendances -> users (estudiante)
        └── attendance_activities -> activity_categories
              └── [media: evidencias vía Spatie]

external_hours -> academic_years
external_hours -> users (estudiante)
external_hours -> users (admin)
  └── [media: soportes vía Spatie]

student_representatives -> users (estudiante)
student_representatives -> users (representante)
student_representatives -> relationship_types

student_health_records -> users (estudiante)
student_health_records -> health_conditions
student_health_records -> users (recibido por)
  └── [media: soportes médicos vía Spatie]

users -> [media: foto de perfil vía Spatie]
```

### 15. Stack Tecnológico

El stack fue seleccionado priorizando tres criterios: madurez del ecosistema, coherencia entre capas y capacidad de operar en entornos sin conexión a internet, ya que el sistema será desplegado inicialmente en una red local sin acceso externo. Cada decisión tecnológica tiene su argumento a continuación.

#### 14.1 Tabla Resumen

| Capa                       | Tecnología                      | Observación                                                          |
| -------------------------- | ------------------------------- | -------------------------------------------------------------------- |
| Backend                    | Laravel 12                      | Framework principal                                                  |
| Autenticación              | Laravel Fortify vía starter kit | Incluido en Laravel 12, extendido con parámetro context              |
| Roles y permisos           | Spatie Laravel Permissions      | Soporta múltiples roles por usuario                                  |
| Archivos adjuntos          | Spatie Laravel Media Library    | Tabla media polimórfica centralizada                                 |
| Bridge frontend            | Inertia.js                      | Incluido en starter kit, elimina API REST separada                   |
| UI                         | React 19 + TypeScript           | Stack oficial del starter kit de Laravel 12                          |
| Componentes                | shadcn/ui                       | Componentes copiados al proyecto, sin dependencia externa en runtime |
| Estilos                    | Tailwind CSS 4                  | Configuración directa en CSS, sin tailwind.config.js                 |
| Base de datos              | PostgreSQL                      | BOOLEAN nativo, mejor concurrencia, licencia libre                   |
| Almacenamiento de archivos | Disco local del servidor        | Sin S3 ni proveedores externos en fase inicial                       |
| Dependencias frontend      | Instaladas vía npm, sin CDN     | Requerido para operación offline                                     |

#### 14.2 Argumentación por Capa

- **Laravel 12**: Laravel es el framework de referencia en el ecosistema PHP. Se eligió por su maduro sistema de migraciones con Eloquent ORM, su ecosistema de paquetes de primera clase (Spatie, Fortify, Inertia) y porque permite desarrollar rápidamente sin sacrificar estructura. Laravel 12 incluye el starter kit oficial con React preconfigurado, eliminando la necesidad de integrar manualmente Inertia, Vite, TypeScript y Tailwind.
- **Laravel Fortify para autenticación**: El starter kit de React de Laravel 12 incluye Fortify preconfigurado con login, registro, recuperación de contraseña, protección CSRF y rate limiting. No se escribe desde cero porque resuelve correctamente todos estos aspectos estándar. La única extensión necesaria es agregar el parámetro opcional `context` en el `LoginRequest` existente para manejar el flujo de selección de rol en usuarios con múltiples roles. Esta decisión sigue el principio de mínima intervención: se extiende lo que ya funciona en lugar de reemplazarlo.
- **Spatie Laravel Permissions**: Un ENUM en la tabla `users` no puede manejar el caso de un usuario con múltiples roles simultáneos (profesor-administrador, profesor-representante). Spatie Laravel Permissions resuelve esto con una tabla polimórfica de roles asignables por usuario y agrega permisos granulares por módulo. Es la librería estándar del ecosistema Laravel para este propósito.
- **Spatie Laravel Media Library**: Centraliza el manejo de todos los archivos adjuntos bajo una única tabla `media` polimórfica. Evita rutas dispersas en múltiples tablas y soporta múltiples colecciones por modelo. Los cuatro modelos con adjuntos son: `User` (foto de perfil), `AttendanceActivity` (evidencias), `ExternalHour` (soportes de horas externas) y `StudentHealthRecord` (soportes médicos).
- **Inertia.js**: Elimina la necesidad de una API REST separada. Las respuestas de Laravel retornan props directamente a componentes React vía Inertia, manteniendo el enrutamiento del lado del servidor. El starter kit ya lo incluye preconfigurado.
- **React 19 con TypeScript**: React es el framework de UI más adoptado del ecosistema frontend. TypeScript agrega tipado estático que previene errores en tiempo de desarrollo. La combinación React + TypeScript + Inertia es el stack que el starter kit oficial de Laravel 12 provee nativamente.
- **shadcn/ui**: Provee componentes accesibles basados en Radix UI y Tailwind. shadcn copia el código de los componentes directamente al proyecto: no hay dependencia externa en runtime y los componentes son modificables libremente. Esto es especialmente importante para el modo offline.
- **Tailwind CSS 4**: Versión más reciente. Elimina `tailwind.config.js` y mueve la configuración al CSS. Es la versión que incluye el starter kit de Laravel 12. Se elige la versión más reciente para evitar deuda técnica desde el inicio.
- **PostgreSQL**: Elegido sobre MySQL por BOOLEAN nativo, JSON avanzado, mejor concurrencia y licencia libre. Eloquent ORM abstrae las diferencias entre motores, por lo que el código de la aplicación es independiente del motor subyacente.
- **Almacenamiento local y operación offline**: En la fase inicial el sistema opera en red local sin internet. El almacenamiento usa el driver local de Laravel. Spatie Media Library soporta múltiples drivers, por lo que migrar a S3 en el futuro es un cambio de configuración, no de código. Todas las dependencias frontend se instalan vía npm sin recursos externos por CDN.

---

_(Nota: La Sección 15 sobre el Plan de Desarrollo se encuentra cubierta y mejorada en el archivo amantina_implementacion_v5.md para evitar redundancia)._
