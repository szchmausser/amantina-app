# Bugfix Requirements Document

## Introduction

Después de implementar el feature "grade-section-definitions", 81 tests fallan debido a 4 problemas sistemáticos:
1. Tests desactualizados que intentan crear grados/secciones con el campo `name` libre en lugar de usar `grade_definition_id`
2. Incompatibilidad SQLite: la función `DATE_TRUNC` de PostgreSQL no existe en SQLite
3. Dashboard refactorizado: los tests esperan props antiguas (`globalCompliance`, `sectionRanking`, `termComparison`) pero el controller devuelve props nuevas
4. Foreign Key constraints en Browser tests: `DatabaseTruncation` falla al intentar borrar `academic_years` con FK activas en SQLite

El objetivo es corregir estos 81 tests para que la suite completa vuelva a pasar (657 passing → 738 passing, 0 failed).

## Bug Analysis

### Current Behavior (Defect)

#### 1. Tests desactualizados tras Grade/Section Definitions

1.1 WHEN un test intenta crear un grado con `['name' => '1er Año', 'academic_year_id' => $id, 'order' => 1]` THEN el sistema rechaza la petición con error "The grade definition id field is required."

1.2 WHEN un test intenta crear una sección con `['name' => 'A', 'grade_id' => $id, 'academic_year_id' => $id]` THEN el sistema rechaza la petición con error "The section definition id field is required."

#### 2. Incompatibilidad SQLite: DATE_TRUNC no existe

2.1 WHEN un representante accede al dashboard Y el sistema ejecuta `HourAccumulatorService::getRepresentativeDashboard()` THEN el sistema falla con error "SQLSTATE[HY000]: General error: 1 no such function: DATE_TRUNC"

2.2 WHEN el sistema ejecuta `DB::raw('DATE_TRUNC(\'week\', field_sessions.start_datetime) as week')` en SQLite THEN el sistema falla porque SQLite no tiene la función `DATE_TRUNC` de PostgreSQL

#### 3. Dashboard refactorizado: props cambiaron

3.1 WHEN un test de admin dashboard verifica `->has('globalCompliance')` THEN el test falla con error "Property [globalCompliance] does not exist"

3.2 WHEN un test de admin dashboard verifica `->has('sectionRanking')` THEN el test falla con error "Property [sectionRanking] does not exist"

3.3 WHEN un test de admin dashboard verifica `->has('termComparison')` THEN el test falla con error "Property [termComparison] does not exist"

3.4 WHEN un Browser test busca el texto "Ya asignado" en el dashboard THEN el test falla porque ese texto ya no existe en la nueva implementación

#### 4. Foreign Key constraint en Browser tests

4.1 WHEN un Browser test usa `DatabaseTruncation` Y intenta borrar `academic_years` THEN el sistema falla con error "SQLSTATE[23000]: Integrity constraint violation: 19 FOREIGN KEY constraint failed"

4.2 WHEN SQLite intenta ejecutar `DELETE FROM academic_years` Y existen FK activas apuntando a ella (grades, school_terms, etc.) THEN el sistema falla porque SQLite no permite borrar con FK activas

#### 5. Otros fallos menores en Browser tests

5.1 WHEN un Browser test compara una fecha sin hora (`2024-12-20`) con una fecha con hora (`2024-12-20 00:00:00`) THEN el test falla por diferencia de formato

5.2 WHEN un Browser test busca el input `[data-test="grade-name-input"]` THEN el test falla con timeout porque ese input ya no existe (ahora es un select de definiciones)

5.3 WHEN un Browser test intenta crear un grado esperando un input de texto libre THEN el test falla porque ahora debe seleccionar de un dropdown de definiciones

### Expected Behavior (Correct)

#### 1. Tests actualizados para Grade/Section Definitions

2.1 WHEN un test intenta crear un grado THEN el sistema SHALL aceptar `['grade_definition_id' => $definitionId, 'academic_year_id' => $id, 'order' => 1]` Y crear el grado correctamente

2.2 WHEN un test intenta crear una sección THEN el sistema SHALL aceptar `['section_definition_id' => $definitionId, 'grade_id' => $id, 'academic_year_id' => $id]` Y crear la sección correctamente

2.3 WHEN un test necesita crear un grado THEN el sistema SHALL primero crear o buscar una `GradeDefinition` Y luego usar su ID en la creación del grado

2.4 WHEN un test necesita crear una sección THEN el sistema SHALL primero crear o buscar una `SectionDefinition` Y luego usar su ID en la creación de la sección

#### 2. Compatibilidad SQLite: abstracción de DATE_TRUNC

2.5 WHEN el sistema ejecuta una query con agrupación por semana en PostgreSQL THEN el sistema SHALL usar `DATE_TRUNC('week', field_sessions.start_datetime)`

2.6 WHEN el sistema ejecuta una query con agrupación por semana en SQLite THEN el sistema SHALL usar `DATE(field_sessions.start_datetime, 'weekday 0', '-6 days')`

2.7 WHEN `HourAccumulatorService::getRepresentativeDashboard()` ejecuta la query de tendencia semanal THEN el sistema SHALL detectar el motor de base de datos con `DB::getDriverName()` Y usar la sintaxis correcta para cada motor

#### 3. Tests actualizados para nuevas props del dashboard

2.8 WHEN un test de admin dashboard verifica las props THEN el sistema SHALL verificar `->has('totalStudents')`, `->has('requiredHours')`, `->has('averageHours')`, `->has('distribution')`, `->has('onTrackStudents')`, `->has('topSections')`, `->has('concerningSections')`, `->has('alerts')`

2.9 WHEN un test de admin dashboard verifica props antiguas THEN el sistema SHALL eliminar las verificaciones de `globalCompliance`, `sectionRanking`, `termComparison` Y reemplazarlas con las props nuevas

2.10 WHEN un Browser test busca elementos del dashboard THEN el sistema SHALL buscar elementos que existen en la nueva implementación Y NO buscar textos o elementos que fueron removidos

#### 4. Solución de Foreign Key constraints en Browser tests

2.11 WHEN un Browser test usa `DatabaseTruncation` en SQLite THEN el sistema SHALL deshabilitar temporalmente las FK checks con `PRAGMA foreign_keys = OFF` antes de truncar Y rehabilitarlas con `PRAGMA foreign_keys = ON` después

2.12 WHEN un Browser test usa `DatabaseTruncation` en PostgreSQL THEN el sistema SHALL continuar usando el comportamiento actual (que funciona correctamente)

2.13 WHEN el sistema trunca tablas en SQLite THEN el sistema SHALL respetar el orden de dependencias para evitar violaciones de FK

#### 5. Corrección de fallos menores en Browser tests

2.14 WHEN un Browser test compara fechas THEN el sistema SHALL normalizar ambas fechas al mismo formato antes de comparar

2.15 WHEN un Browser test intenta crear un grado THEN el sistema SHALL buscar el select `[data-test="grade-definition-select"]` Y seleccionar una definición del dropdown

2.16 WHEN un Browser test intenta crear una sección THEN el sistema SHALL buscar el select `[data-test="section-definition-select"]` Y seleccionar una definición del dropdown

### Unchanged Behavior (Regression Prevention)

#### Funcionalidad de grados y secciones

3.1 WHEN un usuario crea un grado desde la UI con una definición válida THEN el sistema SHALL CONTINUE TO crear el grado correctamente Y copiar el nombre de la definición a `grade_definition_name`

3.2 WHEN un usuario edita un grado existente THEN el sistema SHALL CONTINUE TO permitir cambiar el `order` Y mantener bloqueado el `grade_definition_id`

3.3 WHEN un usuario crea una sección desde la UI con una definición válida THEN el sistema SHALL CONTINUE TO crear la sección correctamente Y copiar el nombre de la definición a `section_definition_name`

#### Dashboard de admin

3.4 WHEN un admin accede al dashboard con PostgreSQL THEN el sistema SHALL CONTINUE TO mostrar todas las métricas correctamente (totalStudents, requiredHours, averageHours, distribution, topSections, concerningSections, alerts)

3.5 WHEN un admin accede al dashboard Y existen estudiantes con horas acumuladas THEN el sistema SHALL CONTINUE TO calcular correctamente los porcentajes Y clasificar estudiantes en onTrack/inProgress/atRisk

#### Dashboard de representante

3.6 WHEN un representante accede al dashboard con PostgreSQL THEN el sistema SHALL CONTINUE TO mostrar la tendencia de las últimas 4 semanas correctamente

3.7 WHEN un representante accede al dashboard Y su estudiante tiene sesiones registradas THEN el sistema SHALL CONTINUE TO mostrar el progreso, próxima sesión Y recordatorios de salud correctamente

#### Tests de Feature

3.8 WHEN se ejecutan tests de Feature que NO involucran grados/secciones THEN el sistema SHALL CONTINUE TO pasar todos los tests sin cambios

3.9 WHEN se ejecutan tests de Feature que usan factories para crear datos THEN el sistema SHALL CONTINUE TO funcionar correctamente porque los factories ya usan `grade_definition_id` y `section_definition_id`

#### Tests de Browser que NO usan DatabaseTruncation

3.10 WHEN se ejecutan Browser tests que usan `RefreshDatabase` en lugar de `DatabaseTruncation` THEN el sistema SHALL CONTINUE TO funcionar correctamente sin cambios

3.11 WHEN se ejecutan Browser tests en PostgreSQL THEN el sistema SHALL CONTINUE TO funcionar correctamente porque PostgreSQL maneja FK constraints correctamente con TRUNCATE CASCADE
