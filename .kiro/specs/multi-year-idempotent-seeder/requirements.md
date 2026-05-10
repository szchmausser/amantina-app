# Requirements Document

## Introduction

El sistema de seeders actual de Amantina App está hardcodeado a un único año escolar (2024-2025) con fechas específicas, lo que impide generar datos históricos multi-año necesarios para validar cálculos acumulados, promoción de alumnos entre años, y horas externas de alumnos transferidos.

Este documento especifica los requerimientos para un sistema de seeders idempotente que permita ejecutarse múltiples veces para generar tres años escolares completos (2023-2024, 2024-2025, 2025-2026) con datos temporalmente realistas, promoción automática de alumnos, y generación controlada de jornadas que respeten límites temporales.

## Glossary

- **Academic_Year**: Año escolar con fechas de inicio/fin, estado activo/inactivo, y horas requeridas
- **School_Term**: Lapso escolar (1, 2, o 3) con fechas de inicio/fin dentro de un año escolar
- **Multi_Year_Seeder**: Orquestador principal que detecta qué año crear y coordina todos los seeders
- **Academic_Year_Data**: Configuración centralizada con las definiciones de los 3 años escolares
- **Promotion_Logic**: Lógica que inscribe alumnos del año N en el año N+1 con grado incrementado
- **Field_Session**: Jornada de campo con fecha, hora, ubicación, y asistencias
- **External_Hour**: Registro de horas acumuladas en otra institución por un alumno transferido
- **Test_User**: Usuario con cédula conocida (90000001-90000030) para testing manual
- **Demo_User**: Usuario generado aleatoriamente (500 alumnos, 25 profesores) para volumen
- **Active_Year**: El año escolar con `is_active = true` (solo puede haber uno)
- **Closed_Year**: Año escolar con `is_active = false` (años históricos completados)
- **Idempotent_Execution**: Capacidad de ejecutar el seeder múltiples veces sin duplicar datos
- **Temporal_Realism**: Fechas de lapsos cronológicamente correctas sin superposiciones
- **Now_Cap**: Límite temporal que impide generar jornadas futuras para el año activo

## Requirements

### Requirement 1: Configuración Centralizada de Años Escolares

**User Story:** Como desarrollador, quiero una fuente de verdad centralizada con la configuración de los 3 años escolares, para que todos los seeders usen las mismas fechas y parámetros.

#### Acceptance Criteria

1. THE Academic_Year_Data SHALL be implemented as a PHP class or array structure accessible to all database seeders
2. THE Academic_Year_Data SHALL define exactly 3 academic years with identifiers 2023-2024, 2024-2025, and 2025-2026
3. FOR EACH academic year, THE Academic_Year_Data SHALL specify start_date in Y-m-d format, end_date in Y-m-d format, required_hours as an integer between 1 and 9999, is_active as a boolean, and exactly 3 school terms
4. FOR EACH school term, THE Academic_Year_Data SHALL specify type_order as an integer (1, 2, or 3), start_date in Y-m-d format, and end_date in Y-m-d format
5. THE Academic_Year_Data SHALL ensure school terms within each academic year are chronologically sequential where each term's start_date is at least 1 day after the previous term's end_date
6. THE Academic_Year_Data SHALL ensure school terms within each academic year have no overlaps where no date exists in more than one term's date range (inclusive of start_date and end_date)
7. THE Academic_Year_Data SHALL mark only 2025-2026 as active with is_active equal to true
8. THE Academic_Year_Data SHALL mark 2023-2024 and 2024-2025 as closed with is_active equal to false
9. THE Academic_Year_Data SHALL use realistic Venezuelan school calendar dates spanning from September to July
10. IF any academic year contains a start_date that is not before its end_date, THEN THE Academic_Year_Data SHALL fail validation with an error message indicating invalid date range for that academic year
11. IF any academic year contains a number of school terms different from 3, THEN THE Academic_Year_Data SHALL fail validation with an error message indicating incorrect term count for that academic year

### Requirement 2: Detección Automática de Año a Crear

**User Story:** Como desarrollador, quiero que el seeder detecte automáticamente qué año crear basándose en los años existentes, para evitar duplicación y mantener idempotencia.

#### Acceptance Criteria

1. WHEN no academic years exist in the database, THE Multi_Year_Seeder SHALL create 2023-2024
2. WHEN only 2023-2024 exists in the database, THE Multi_Year_Seeder SHALL create 2024-2025
3. WHEN 2023-2024 and 2024-2025 exist in the database, THE Multi_Year_Seeder SHALL create 2025-2026
4. WHEN all 3 academic years exist in the database, THE Multi_Year_Seeder SHALL return an error message stating "All academic years already exist"
5. THE Multi_Year_Seeder SHALL detect existing years by querying the academic_years table for matching name values
6. THE Multi_Year_Seeder SHALL log which year it detected and which year it will create

### Requirement 3: Creación de Estructura de Año Escolar

**User Story:** Como desarrollador, quiero que el seeder cree la estructura completa de un año escolar (año, lapsos, grados, secciones), para tener una base consistente antes de inscribir usuarios.

#### Acceptance Criteria

1. WHEN creating an academic year, THE Multi_Year_Seeder SHALL create the AcademicYear record with data from Academic_Year_Data
2. WHEN creating an academic year, THE Multi_Year_Seeder SHALL create 3 SchoolTerm records with dates from Academic_Year_Data
3. WHEN creating an academic year, THE Multi_Year_Seeder SHALL create 5 Grade records (1er Año through 5to Año)
4. WHEN creating an academic year, THE Multi_Year_Seeder SHALL create 2-4 Section records per grade
5. THE Multi_Year_Seeder SHALL ensure only one academic year has is_active = true at any time
6. THE Multi_Year_Seeder SHALL create grades and sections using existing GradeDefinition and SectionDefinition catalogs

### Requirement 4: Generación de Usuarios en Primer Año

**User Story:** Como desarrollador, quiero que el primer año (2023-2024) genere todos los usuarios necesarios (test + demo), para tener una población inicial que se promoverá en años subsiguientes.

#### Acceptance Criteria

1. WHEN creating 2023-2024, THE Multi_Year_Seeder SHALL create 17 test users via TestUsersSeeder (5 teachers, 2 representatives, 10 students)
2. WHEN creating 2023-2024, THE Multi_Year_Seeder SHALL create 500 demo students via DemoDataSeeder
3. WHEN creating 2023-2024, THE Multi_Year_Seeder SHALL create 25 demo teachers via DemoDataSeeder
4. WHEN creating 2023-2024, THE Multi_Year_Seeder SHALL assign the 'alumno' role to all students
5. WHEN creating 2023-2024, THE Multi_Year_Seeder SHALL assign the 'profesor' role to all teachers
6. WHEN creating 2023-2024, THE Multi_Year_Seeder SHALL assign the 'representante' role to representatives
7. THE Multi_Year_Seeder SHALL use password 'password' for all test and demo users

### Requirement 5: Reutilización de Usuarios en Años Subsiguientes

**User Story:** Como desarrollador, quiero que los años 2024-2025 y 2025-2026 reutilicen los usuarios existentes sin crear nuevos, para simular una cohorte real que avanza en el tiempo.

#### Acceptance Criteria

1. WHEN creating 2024-2025 or 2025-2026, THE Multi_Year_Seeder SHALL NOT create new User records
2. WHEN creating 2024-2025 or 2025-2026, THE Multi_Year_Seeder SHALL reuse existing students from the previous year
3. WHEN creating 2024-2025 or 2025-2026, THE Multi_Year_Seeder SHALL reuse existing teachers from the previous year
4. WHEN creating 2024-2025 or 2025-2026, THE Multi_Year_Seeder SHALL reuse existing test users from 2023-2024
5. THE Multi_Year_Seeder SHALL NOT modify user attributes (name, email, cedula, password) when reusing users

### Requirement 6: Promoción de Alumnos Entre Años

**User Story:** Como alumno, quiero ser promovido automáticamente al siguiente grado en el nuevo año escolar, para que mi progreso académico se refleje correctamente en el sistema.

#### Acceptance Criteria

1. WHEN creating year N+1, THE Promotion_Logic SHALL enroll students from 1er Año of year N into 2do Año of year N+1
2. WHEN creating year N+1, THE Promotion_Logic SHALL enroll students from 2do Año of year N into 3er Año of year N+1
3. WHEN creating year N+1, THE Promotion_Logic SHALL enroll students from 3er Año of year N into 4to Año of year N+1
4. WHEN creating year N+1, THE Promotion_Logic SHALL enroll students from 4to Año of year N into 5to Año of year N+1
5. WHEN creating year N+1, THE Promotion_Logic SHALL NOT enroll students from 5to Año of year N (they graduated)
6. THE Promotion_Logic SHALL randomly select 10% of students to repeat their current grade (not promoted)
7. THE Promotion_Logic SHALL create Enrollment records linking students to their new grade and section in year N+1

### Requirement 7: Reasignación de Profesores Entre Años

**User Story:** Como profesor, quiero ser reasignado a secciones en el nuevo año escolar, para continuar enseñando sin necesidad de crear nuevos usuarios.

#### Acceptance Criteria

1. WHEN creating year N+1, THE Multi_Year_Seeder SHALL reuse all existing teachers
2. WHEN creating year N+1, THE Multi_Year_Seeder SHALL randomly assign 1-3 teachers to each section
3. THE Multi_Year_Seeder SHALL create TeacherAssignment records for the new academic year
4. THE Multi_Year_Seeder SHALL NOT preserve teacher-section assignments from previous years (random reassignment)

### Requirement 8: Generación de Jornadas con Fechas Realistas

**User Story:** Como administrador, quiero que las jornadas de campo tengan fechas dentro del rango de cada lapso, para que los datos sean temporalmente consistentes.

#### Acceptance Criteria

1. WHEN generating field sessions, THE Field_Sessions_Seeder SHALL create sessions with dates between the start_date and end_date of each school term
2. WHEN generating field sessions, THE Field_Sessions_Seeder SHALL distribute sessions across all 3 school terms
3. WHEN generating field sessions, THE Field_Sessions_Seeder SHALL create between 120 and 180 total sessions per academic year
4. WHEN generating field sessions, THE Field_Sessions_Seeder SHALL assign each session to a random teacher
5. WHEN generating field sessions, THE Field_Sessions_Seeder SHALL assign each session to a random location
6. WHEN generating field sessions, THE Field_Sessions_Seeder SHALL set 95% of sessions to 'realized' status and 5% to 'cancelled' status

### Requirement 9: Límite Temporal para Jornadas del Año Activo

**User Story:** Como desarrollador, quiero que las jornadas del año activo no se generen en fechas futuras, para que los datos reflejen el estado actual del año escolar en curso.

#### Acceptance Criteria

1. WHEN generating field sessions for the active year, THE Field_Sessions_Seeder SHALL NOT create sessions with dates after today's date
2. WHEN generating field sessions for the active year, THE Field_Sessions_Seeder SHALL cap the effective end date of the current term to today's date
3. WHEN generating field sessions for closed years, THE Field_Sessions_Seeder SHALL use the full term date range without restrictions
4. THE Field_Sessions_Seeder SHALL use Carbon::now() to determine today's date
5. IF today's date falls within Lapso 3 of 2025-2026, THE Field_Sessions_Seeder SHALL generate sessions only up to today for Lapso 3

### Requirement 10: Generación de Asistencias y Actividades

**User Story:** Como alumno, quiero que se generen asistencias y actividades para las jornadas, para acumular horas prácticas en mi registro.

#### Acceptance Criteria

1. WHEN generating field sessions, THE Field_Sessions_Seeder SHALL create between 20 and 60 attendances per session
2. WHEN generating attendances, THE Field_Sessions_Seeder SHALL set 95% of attendances to attended = true and 5% to attended = false
3. WHEN generating attendances, THE Field_Sessions_Seeder SHALL create between 3 and 6 activities per attended student
4. WHEN generating activities, THE Field_Sessions_Seeder SHALL assign each activity to a random ActivityCategory
5. WHEN generating activities, THE Field_Sessions_Seeder SHALL assign between 1 and 4 hours per activity
6. THE Field_Sessions_Seeder SHALL NOT generate activities for students who did not attend (attended = false)
7. THE Field_Sessions_Seeder SHALL NOT generate attendances or activities for cancelled sessions

### Requirement 11: Generación de Horas Externas para Alumnos Transferidos

**User Story:** Como alumno transferido, quiero que se registren mis horas externas acumuladas en mi institución anterior, para que se contabilicen en mi total de horas.

#### Acceptance Criteria

1. WHEN creating 2025-2026 (active year), THE External_Hours_Seeder SHALL mark approximately 10% of students as is_transfer = true
2. WHEN creating 2025-2026 (active year), THE External_Hours_Seeder SHALL generate 1-3 ExternalHour records per transferred student
3. WHEN generating external hours, THE External_Hours_Seeder SHALL assign between 50 and 200 hours per record
4. WHEN generating external hours, THE External_Hours_Seeder SHALL set a realistic institution_name (e.g., "U.E. Simón Bolívar")
5. WHEN generating external hours, THE External_Hours_Seeder SHALL set period to a previous academic year (e.g., "2023-2024")
6. WHEN generating external hours, THE External_Hours_Seeder SHALL set admin_id to the first admin user
7. THE External_Hours_Seeder SHALL NOT generate external hours for 2023-2024 or 2024-2025 (closed years)

### Requirement 12: Idempotencia de Ejecución

**User Story:** Como desarrollador, quiero ejecutar el seeder múltiples veces sin duplicar datos, para poder recrear el estado de la base de datos de forma segura.

#### Acceptance Criteria

1. WHEN executing Multi_Year_Seeder twice without migrate:fresh, THE Multi_Year_Seeder SHALL detect existing years and skip creation
2. WHEN all 3 years exist, THE Multi_Year_Seeder SHALL return an error and NOT create duplicate records
3. THE Multi_Year_Seeder SHALL use database queries to detect existing years before creating new ones
4. THE Multi_Year_Seeder SHALL NOT rely on conversation history or session state for idempotence
5. THE Multi_Year_Seeder SHALL log which years already exist and which year will be created

### Requirement 13: Integración con DatabaseSeeder

**User Story:** Como desarrollador, quiero que `migrate:fresh --seed` ejecute el flujo completo de seeders, para tener un setup inicial consistente.

#### Acceptance Criteria

1. WHEN executing `php artisan migrate:fresh --seed`, THE DatabaseSeeder SHALL call CompleteTestDataSeeder
2. WHEN executing CompleteTestDataSeeder, THE CompleteTestDataSeeder SHALL delegate to Multi_Year_Seeder for year creation
3. THE DatabaseSeeder SHALL maintain backward compatibility with existing seeders (Institution, Roles, Catalogs)
4. THE DatabaseSeeder SHALL NOT call AcademicYearSeeder or SchoolTermSeeder directly (absorbed by Multi_Year_Seeder)

### Requirement 14: Modificación de Seeders Existentes

**User Story:** Como desarrollador, quiero que los seeders existentes acepten un parámetro opcional de año escolar, para mantener compatibilidad hacia atrás mientras soportan el nuevo flujo multi-año.

#### Acceptance Criteria

1. THE GradeSeeder SHALL accept an optional AcademicYear parameter
2. THE SectionSeeder SHALL accept an optional AcademicYear parameter
3. THE DemoDataSeeder SHALL accept an optional AcademicYear parameter
4. THE FieldSessionsSeeder SHALL accept an optional AcademicYear parameter
5. THE TeacherAssignmentSeeder SHALL accept an optional AcademicYear parameter
6. WHEN no AcademicYear parameter is provided, THE seeders SHALL query for the active year (backward compatibility)
7. WHEN an AcademicYear parameter is provided, THE seeders SHALL use that year instead of querying

### Requirement 15: Pretty Printer para Configuración de Años

**User Story:** Como desarrollador, quiero una función que formatee la configuración de años escolares de forma legible, para facilitar debugging y validación.

#### Acceptance Criteria

1. THE Academic_Year_Data SHALL provide a static method to return the configuration as a formatted array
2. THE Academic_Year_Data SHALL provide a static method to return the configuration as JSON
3. THE Academic_Year_Data SHALL provide a static method to validate that all dates are chronologically correct
4. THE Academic_Year_Data SHALL provide a static method to return the configuration for a specific year by name

### Requirement 16: Round-Trip de Configuración de Años

**User Story:** Como desarrollador, quiero validar que la configuración de años se puede serializar y deserializar sin pérdida de datos, para garantizar integridad en la persistencia.

#### Acceptance Criteria

1. FOR ALL valid Academic_Year_Data configurations, serializing to JSON then deserializing SHALL produce an equivalent configuration
2. THE Academic_Year_Data SHALL validate that deserialized dates are Carbon instances
3. THE Academic_Year_Data SHALL validate that deserialized boolean flags are boolean type
4. THE Academic_Year_Data SHALL validate that deserialized numeric values are numeric type

### Requirement 17: Validación de Fechas de Lapsos

**User Story:** Como desarrollador, quiero que el sistema valide que las fechas de los lapsos no se superpongan, para evitar inconsistencias temporales.

#### Acceptance Criteria

1. THE Academic_Year_Data SHALL validate that Lapso 1 end_date is before Lapso 2 start_date
2. THE Academic_Year_Data SHALL validate that Lapso 2 end_date is before Lapso 3 start_date
3. THE Academic_Year_Data SHALL validate that all school terms fall within the academic year date range
4. THE Academic_Year_Data SHALL return a descriptive error message if date validation fails

### Requirement 18: Logging y Feedback de Progreso

**User Story:** Como desarrollador, quiero ver el progreso de la ejecución del seeder con mensajes claros, para entender qué está sucediendo y detectar problemas.

#### Acceptance Criteria

1. THE Multi_Year_Seeder SHALL log "Detected existing years: [list]" before creating a new year
2. THE Multi_Year_Seeder SHALL log "Creating academic year: [year_name]" when starting year creation
3. THE Multi_Year_Seeder SHALL log "Created [count] enrollments for [year_name]" after enrolling students
4. THE Multi_Year_Seeder SHALL log "Promoted [count] students from [year_N] to [year_N+1]" after promotion
5. THE Multi_Year_Seeder SHALL log "Marked [count] students as repeating [grade]" after applying repeat logic
6. THE Multi_Year_Seeder SHALL use progress bars for long-running operations (enrollments, field sessions)

### Requirement 19: Manejo de Errores y Casos Edge

**User Story:** Como desarrollador, quiero que el seeder maneje casos edge de forma predecible, para evitar estados inconsistentes en la base de datos.

#### Acceptance Criteria

1. WHEN no GradeDefinition records exist, THE Multi_Year_Seeder SHALL return an error message "Grade definitions not found. Run GradeDefinitionSeeder first."
2. WHEN no SectionDefinition records exist, THE Multi_Year_Seeder SHALL return an error message "Section definitions not found. Run SectionDefinitionSeeder first."
3. WHEN no ActivityCategory records exist, THE Field_Sessions_Seeder SHALL return an error message "Activity categories not found. Run ActivityCategorySeeder first."
4. WHEN no Location records exist, THE Field_Sessions_Seeder SHALL return an error message "Locations not found. Run LocationSeeder first."
5. WHEN attempting to create a 4th academic year, THE Multi_Year_Seeder SHALL return an error message "All academic years already exist. Cannot create more than 3 years."
6. WHEN database transaction fails during year creation, THE Multi_Year_Seeder SHALL rollback all changes for that year

### Requirement 20: Verificación Post-Ejecución

**User Story:** Como desarrollador, quiero comandos de verificación para validar que los datos se generaron correctamente, para confirmar el éxito de la ejecución.

#### Acceptance Criteria

1. THE Multi_Year_Seeder SHALL provide a summary at the end showing: years created, students enrolled, teachers assigned, field sessions generated
2. THE Multi_Year_Seeder SHALL validate that exactly one academic year has is_active = true
3. THE Multi_Year_Seeder SHALL validate that the active year is 2025-2026 after 3 executions
4. THE Multi_Year_Seeder SHALL validate that Lapso 3 of 2025-2026 is the current term (based on today's date)
5. THE Multi_Year_Seeder SHALL provide a method to query total accumulated hours per student across all years
