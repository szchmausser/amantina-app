# Requirements Document

## Introduction

Este documento define los requerimientos para implementar mejoras prioritarias en los dashboards del sistema Bitácora Socioproductiva. El sistema actualmente cuenta con dashboards básicos funcionales para los 4 roles (Admin, Profesor, Alumno, Representante) implementados en el Hito 12. Las mejoras propuestas buscan transformar los dashboards de herramientas descriptivas ("qué pasó") a herramientas predictivas y accionables ("qué va a pasar" y "qué hacer al respecto").

**Contexto del sistema:**
- Stack: Laravel 12 + React 19 + Inertia.js + PostgreSQL
- Servicio centralizado: HourAccumulatorService maneja todos los cálculos de estadísticas
- 4 roles: admin, profesor, alumno, representante
- Hito 12 completado: Dashboards básicos implementados

**Objetivos de negocio:**
- Pasar de dashboards descriptivos a dashboards predictivos
- Aumentar la motivación de los alumnos mediante gamificación
- Dar herramientas accionables a profesores para identificar estudiantes que necesitan apoyo
- Facilitar el seguimiento a representantes con múltiples hijos
- Mejorar la experiencia de usuario con visualizaciones más ricas y filtros flexibles
- Optimizar el rendimiento para dashboards con grandes volúmenes de datos

## Glossary

- **Dashboard**: Panel de control que muestra estadísticas y métricas relevantes para cada rol de usuario
- **HourAccumulatorService**: Servicio centralizado de Laravel que calcula todas las estadísticas de horas acumuladas
- **Jornada**: Sesión de actividad socioproductiva registrada en el sistema (field_session)
- **Lapso**: Periodo académico dentro de un año escolar (1er, 2do, 3er lapso)
- **Cupo**: Cantidad de horas requeridas que un estudiante debe cumplir en un año escolar
- **Sección**: Grupo de estudiantes dentro de un grado (ej: 3er año sección A)
- **Percentil**: Posición relativa de un estudiante respecto al total de estudiantes (ej: top 25%)
- **Badge**: Reconocimiento visual otorgado al estudiante por alcanzar logros específicos
- **Racha**: Contador de semanas consecutivas con al menos una participación
- **Redis**: Sistema de caché en memoria para optimizar consultas costosas
- **Skeleton_Loader**: Componente visual que muestra un placeholder animado mientras cargan los datos
- **Lazy_Loading**: Técnica de carga progresiva de componentes para mejorar el rendimiento inicial

## Requirements

### Requirement 1: Análisis Predictivo para Administrador

**User Story:** Como administrador, quiero ver proyecciones de cumplimiento por sección, para identificar tempranamente secciones en riesgo y tomar acciones correctivas.

#### Acceptance Criteria

1. WHEN THE Admin_Dashboard carga, THE HourAccumulatorService SHALL calcular la proyección de cumplimiento para cada sección basándose en el ritmo actual de acumulación de horas
2. THE HourAccumulatorService SHALL clasificar cada sección en una de cuatro categorías: "En Riesgo" (< 50% del esperado), "Necesita Atención" (50-75%), "En Camino" (75-100%), "Cumplido" (>= 100%)
3. THE Admin_Dashboard SHALL mostrar las secciones clasificadas en tarjetas visuales con códigos de color (rojo, amarillo, verde claro, verde)
4. WHEN una sección está en categoría "En Riesgo" o "Necesita Atención", THE Admin_Dashboard SHALL mostrar el número de horas faltantes y el ritmo requerido para alcanzar el cupo
5. THE HourAccumulatorService SHALL calcular el porcentaje de días transcurridos del año escolar para determinar el progreso esperado
6. FOR ALL secciones clasificadas, THE System SHALL garantizar que la clasificación se actualiza cuando se registran nuevas jornadas o asistencias

### Requirement 2: Análisis Individual Detallado para Profesor

**User Story:** Como profesor, quiero acceder a una vista profunda de cada estudiante con comparativas, para identificar rápidamente quiénes necesitan apoyo y planificar intervenciones personalizadas.

#### Acceptance Criteria

1. WHEN THE Teacher_Dashboard muestra la lista de estudiantes de una sección, THE System SHALL permitir hacer clic en cualquier estudiante para ver su ficha detallada
2. THE Student_Detail_View SHALL mostrar el historial completo de participación del estudiante en orden cronológico
3. THE Student_Detail_View SHALL incluir un gráfico de línea temporal que visualice la evolución de horas acumuladas del estudiante a lo largo del año escolar
4. THE Student_Detail_View SHALL mostrar la comparación del estudiante con el promedio de su sección mediante un indicador visual (ej: "+15 horas sobre el promedio" o "-8 horas bajo el promedio")
5. WHEN un estudiante no ha participado en jornadas durante 3 semanas consecutivas, THE Teacher_Dashboard SHALL mostrar una alerta visual en la lista de estudiantes
6. THE Student_Detail_View SHALL mostrar las categorías de actividades en las que el estudiante ha participado y las que no ha explorado
7. WHEN un estudiante tiene condiciones de salud registradas, THE Student_Detail_View SHALL mostrar un recordatorio visible con la condición y las observaciones

### Requirement 3: Sistema de Gamificación para Alumno

**User Story:** Como alumno, quiero ver logros y badges por mis participaciones, para sentirme motivado a seguir acumulando horas y explorar diferentes actividades.

#### Acceptance Criteria

1. THE Student_Dashboard SHALL mostrar una sección de "Logros" con badges visuales otorgados al estudiante
2. WHEN un estudiante alcanza el 25%, 50%, 75% o 100% del cupo requerido, THE System SHALL otorgar un badge de hito correspondiente
3. WHEN un estudiante participa en todas las categorías de actividades disponibles, THE System SHALL otorgar el badge "Explorador"
4. WHEN un estudiante tiene asistencia perfecta en un lapso (asiste a todas las jornadas de su sección), THE System SHALL otorgar el badge "Asistencia Perfecta"
5. THE Student_Dashboard SHALL mostrar un contador de "Racha de Participación" que indica cuántas semanas consecutivas el estudiante ha participado en al menos una jornada
6. WHEN la racha del estudiante alcanza 4 semanas consecutivas, THE System SHALL otorgar el badge "Racha de Fuego"
7. THE Student_Dashboard SHALL mostrar el "Próximo Hito" con una barra de progreso y el texto "Te faltan X horas para alcanzar el Y%"
8. FOR ALL badges otorgados, THE System SHALL almacenar la fecha de obtención y permitir consultar el historial de logros

### Requirement 4: Análisis Comparativo Mejorado para Alumno

**User Story:** Como alumno, quiero ver mi posición relativa respecto a mis compañeros, para entender cómo me estoy desempeñando en comparación con el resto de la institución.

#### Acceptance Criteria

1. THE Student_Dashboard SHALL mostrar el percentil del estudiante dentro de todos los estudiantes de la institución (ej: "Estás en el top 25%")
2. THE Student_Dashboard SHALL incluir un histograma de distribución que muestre visualmente dónde se ubica el estudiante respecto a todos sus compañeros
3. THE Student_Dashboard SHALL mostrar una comparación con promociones anteriores indicando si la generación actual va más rápido o más lento que la generación anterior a la misma altura del año escolar
4. WHEN el estudiante está por encima del promedio de su sección, THE Student_Dashboard SHALL mostrar un mensaje motivacional (ej: "¡Vas muy bien! Estás X horas sobre el promedio")
5. WHEN el estudiante está por debajo del promedio de su sección, THE Student_Dashboard SHALL mostrar un mensaje de aliento con sugerencias (ej: "Participa en las próximas jornadas para alcanzar el promedio")
6. THE HourAccumulatorService SHALL calcular el percentil dividiendo el número de estudiantes con menos horas que el estudiante actual entre el total de estudiantes

### Requirement 5: Vista Consolidada Multi-Representado

**User Story:** Como representante con múltiples hijos en la institución, quiero ver el progreso de todos mis representados en una sola vista, para facilitar el seguimiento sin tener que cambiar de contexto.

#### Acceptance Criteria

1. WHEN un representante tiene más de un estudiante a su cargo, THE Representative_Dashboard SHALL mostrar automáticamente una vista consolidada
2. THE Consolidated_View SHALL listar todos los representados con sus nombres, fotos de perfil, grado y sección
3. FOR EACH representado en la vista consolidada, THE System SHALL mostrar el progreso de horas (porcentaje completado, horas acumuladas, cupo requerido)
4. THE Consolidated_View SHALL mostrar alertas consolidadas de todos los representados (ej: "2 de tus representados están por debajo del promedio")
5. THE Consolidated_View SHALL incluir un selector dropdown que permita cambiar entre la vista consolidada y la vista individual de cada representado
6. WHEN un representante tiene solo un estudiante a su cargo, THE Representative_Dashboard SHALL mostrar la vista individual directamente (comportamiento actual)
7. THE Consolidated_View SHALL mostrar las próximas jornadas programadas para todos los representados en una lista unificada

### Requirement 6: Análisis de Patrones de Participación para Representante

**User Story:** Como representante, quiero identificar patrones en la participación de mi representado, para entender sus preferencias y apoyarlo mejor en su proceso.

#### Acceptance Criteria

1. THE Representative_Dashboard SHALL mostrar un análisis de los días y horarios en los que el representado participa más frecuentemente
2. THE Representative_Dashboard SHALL identificar las categorías de actividades en las que el representado participa más y las que no ha explorado
3. WHEN el representado está por debajo del promedio de su sección, THE Representative_Dashboard SHALL mostrar una sugerencia específica (ej: "Considera motivar a tu representado a participar en jornadas de [categoría X]")
4. THE Representative_Dashboard SHALL mostrar un gráfico de tendencia de las últimas 8 semanas indicando si la participación está aumentando, disminuyendo o se mantiene estable
5. THE Representative_Dashboard SHALL mostrar la comparación del representado con el promedio de su sección mediante un indicador visual claro
6. WHEN el representado no ha participado en 2 semanas consecutivas, THE Representative_Dashboard SHALL mostrar una alerta destacada

### Requirement 7: Filtros Avanzados Transversales

**User Story:** Como usuario de cualquier rol, quiero filtrar las estadísticas del dashboard por lapso y rango de fechas personalizado, para analizar periodos específicos de interés.

#### Acceptance Criteria

1. THE Dashboard SHALL incluir un selector de lapso que permita filtrar todas las estadísticas por "1er Lapso", "2do Lapso", "3er Lapso" o "Todos los lapsos"
2. THE Dashboard SHALL incluir un selector de rango de fechas personalizado con un date picker que permita seleccionar fecha de inicio y fecha de fin
3. WHEN un usuario selecciona un filtro de lapso, THE HourAccumulatorService SHALL recalcular todas las estadísticas considerando solo las jornadas y asistencias dentro de ese lapso
4. WHEN un usuario selecciona un rango de fechas personalizado, THE HourAccumulatorService SHALL recalcular todas las estadísticas considerando solo las jornadas y asistencias dentro de ese rango
5. THE Dashboard SHALL mostrar claramente el filtro activo en la parte superior (ej: "Mostrando datos del 1er Lapso" o "Mostrando datos del 01/09/2025 al 15/12/2025")
6. THE Dashboard SHALL incluir un botón "Limpiar Filtros" que restablezca la vista a "Todos los lapsos" y sin rango de fechas
7. THE System SHALL preservar los filtros seleccionados en la sesión del usuario, de modo que si recarga la página los filtros se mantengan activos

### Requirement 8: Optimización de Performance y Caching

**User Story:** Como usuario de cualquier rol, quiero que el dashboard cargue rápidamente incluso con grandes volúmenes de datos, para tener una experiencia fluida sin tiempos de espera prolongados.

#### Acceptance Criteria

1. THE HourAccumulatorService SHALL implementar caching en Redis para estadísticas costosas con una duración de 15 minutos
2. WHEN se registra una nueva jornada, asistencia o actividad, THE System SHALL invalidar automáticamente el caché relacionado para garantizar datos actualizados
3. THE Dashboard SHALL implementar lazy loading para widgets secundarios, cargando primero los widgets críticos (progreso principal, alertas) y luego los complementarios (gráficos, historial)
4. THE Dashboard SHALL mostrar skeleton loaders animados mientras cargan los datos de cada widget
5. THE HourAccumulatorService SHALL optimizar las consultas SQL utilizando índices apropiados en las columnas más consultadas (user_id, academic_year_id, section_id, attended)
6. THE Dashboard SHALL cargar en menos de 2 segundos para el 95% de los usuarios en condiciones normales de red
7. WHEN una consulta tarda más de 5 segundos, THE System SHALL registrar un log de advertencia para identificar cuellos de botella
8. THE HourAccumulatorService SHALL utilizar eager loading para evitar problemas de N+1 queries en relaciones Eloquent

### Requirement 9: Visualizaciones Interactivas Mejoradas

**User Story:** Como usuario de cualquier rol, quiero interactuar con gráficos dinámicos y tooltips informativos, para explorar los datos de manera más intuitiva y obtener información contextual.

#### Acceptance Criteria

1. THE Dashboard SHALL utilizar una librería de gráficos interactivos (Recharts o Chart.js) para todos los gráficos estadísticos
2. WHEN un usuario pasa el mouse sobre un punto de datos en un gráfico, THE System SHALL mostrar un tooltip con información detallada (valor exacto, fecha, contexto)
3. THE Dashboard SHALL incluir animaciones suaves al cargar gráficos y al cambiar entre filtros (transiciones de 300ms)
4. THE Dashboard SHALL permitir hacer clic en elementos de gráficos de barras o pastel para filtrar o navegar a vistas detalladas
5. THE Dashboard SHALL incluir íconos informativos (?) junto a métricas complejas que muestren tooltips explicativos al pasar el mouse
6. THE Dashboard SHALL utilizar códigos de color consistentes en todos los gráficos (verde para cumplido, amarillo para en progreso, rojo para en riesgo)
7. THE Dashboard SHALL incluir leyendas claras en todos los gráficos que expliquen qué representa cada color o serie de datos

### Requirement 10: Parser y Pretty Printer para Configuración de Filtros

**User Story:** Como desarrollador del sistema, quiero parsear y serializar configuraciones de filtros de dashboard, para permitir guardar y compartir vistas personalizadas de manera confiable.

#### Acceptance Criteria

1. THE Filter_Parser SHALL parsear objetos de configuración de filtros desde formato JSON a objetos PHP validados
2. WHEN un objeto de configuración de filtros es inválido, THE Filter_Parser SHALL retornar un error descriptivo indicando el campo problemático
3. THE Filter_Pretty_Printer SHALL formatear objetos de configuración de filtros de PHP a JSON con formato legible (indentación, saltos de línea)
4. FOR ALL configuraciones de filtros válidas, parsear y luego imprimir y luego parsear nuevamente SHALL producir un objeto equivalente al original (round-trip property)
5. THE Filter_Parser SHALL validar que las fechas estén en formato ISO 8601 (YYYY-MM-DD)
6. THE Filter_Parser SHALL validar que el lapso seleccionado exista en el año académico activo
7. THE System SHALL almacenar configuraciones de filtros guardadas en la tabla `user_dashboard_preferences` con columnas: user_id, dashboard_type, filter_config (JSON), created_at, updated_at

