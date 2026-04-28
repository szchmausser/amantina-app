# Oportunidades de Mejora - Dashboards y Estadísticas

**Fecha de análisis:** 2026-04-23  
**Estado actual:** Hito 12 completado (Dashboards básicos implementados)  
**Versión del sistema:** 8.0

---

## Resumen Ejecutivo

El sistema actualmente cuenta con dashboards funcionales para los 4 roles (Admin, Profesor, Alumno, Representante) implementados en el Hito 12. Sin embargo, existen múltiples oportunidades para enriquecer la experiencia de cada rol con estadísticas más profundas, visualizaciones mejoradas y funcionalidades predictivas que agreguen valor real al seguimiento de horas socioproductivas.

---

## 1. Dashboard del Administrador

### Estado Actual
El dashboard de admin muestra:
- `globalCompliance`: Cumplimiento global institucional
- `sectionRanking`: Ranking de secciones
- `termComparison`: Comparación entre lapsos
- `sessionStats`: Estadísticas de jornadas
- `alerts`: Alertas del sistema
- `activityCategoryDistribution`: Distribución por categoría de actividad
- `locationDistribution`: Distribución por ubicación
- `teacherWorkload`: Carga de trabajo docente
- `yearOverYear`: Comparación año tras año

### Oportunidades de Mejora

#### 1.1 Análisis Predictivo y Proyecciones
**Prioridad:** Alta  
**Impacto:** Alto

- **Proyección de cumplimiento por sección:** Calcular la probabilidad de que cada sección alcance el cupo requerido basándose en el ritmo actual de acumulación de horas
- **Identificación temprana de riesgo:** Alertas automáticas para secciones que están significativamente por debajo del promedio esperado para la fecha actual del año escolar
- **Tendencias de participación:** Gráficos de tendencia que muestren si la participación está aumentando o disminuyendo a lo largo del año

**Implementación sugerida:**
```php
// En HourAccumulatorService
public function getSectionRiskAnalysis(int $academicYearId): array
{
    // Calcular días transcurridos vs días totales del año escolar
    // Calcular horas esperadas vs horas reales por sección
    // Clasificar secciones en: En riesgo, Atención, En camino, Cumplido
    return [
        'atRisk' => [...],      // < 50% del esperado
        'needsAttention' => [...], // 50-75% del esperado
        'onTrack' => [...],     // 75-100% del esperado
        'completed' => [...]    // >= 100%
    ];
}
```

#### 1.2 Análisis de Eficiencia Docente
**Prioridad:** Media  
**Impacto:** Alto

- **Promedio de horas por jornada por profesor:** Identificar qué profesores organizan jornadas más productivas
- **Tasa de asistencia por profesor:** Medir qué profesores tienen mejor convocatoria
- **Diversidad de actividades:** Evaluar qué profesores ofrecen mayor variedad de experiencias
- **Frecuencia de jornadas:** Identificar profesores que necesitan apoyo para organizar más actividades

**Visualización sugerida:**
- Tabla comparativa con métricas clave por profesor
- Gráfico de dispersión: Frecuencia de jornadas vs Promedio de horas acreditadas

#### 1.3 Análisis Geográfico y de Ubicaciones
**Prioridad:** Baja  
**Impacto:** Medio

- **Mapa de calor de ubicaciones:** Visualizar qué ubicaciones son más utilizadas
- **Análisis de accesibilidad:** Identificar si hay estudiantes que nunca participan en ciertas ubicaciones (posible problema de transporte)
- **Costo-beneficio por ubicación:** Si se registran costos, analizar qué ubicaciones generan más horas por inversión

#### 1.4 Dashboard de Salud Institucional
**Prioridad:** Media  
**Impacto:** Medio

- **Resumen de condiciones de salud:** Estadísticas agregadas (sin identificar estudiantes) sobre condiciones médicas registradas
- **Alertas de documentación pendiente:** Estudiantes sin información de salud registrada
- **Actividades adaptadas:** Sugerencias de actividades según el perfil de salud de la población estudiantil

---

## 2. Dashboard del Profesor

### Estado Actual
El dashboard de profesor muestra:
- `sections`: Secciones asignadas
- `ownSessions`: Jornadas propias
- `pendingAttendance`: Asistencias pendientes
- `lowAttendanceStudents`: Estudiantes con baja asistencia
- `categoryDistribution`: Distribución por categoría
- `sessionsPerTerm`: Jornadas por lapso
- `healthReminders`: Recordatorios de salud

### Oportunidades de Mejora

#### 2.1 Planificador de Jornadas Inteligente
**Prioridad:** Alta  
**Impacto:** Alto

- **Sugerencias de próximas jornadas:** Basándose en:
  - Estudiantes que más necesitan horas
  - Categorías de actividades menos exploradas
  - Ubicaciones no utilizadas recientemente
  - Lapso actual y tiempo restante
- **Calendario visual:** Vista de calendario con jornadas pasadas y sugerencias de fechas óptimas para próximas jornadas
- **Simulador de impacto:** "Si organizas una jornada de X horas con Y estudiantes, el promedio de tu sección subirá a Z"

**Implementación sugerida:**
```php
public function getSessionPlannerSuggestions(int $teacherId, int $academicYearId): array
{
    return [
        'studentsNeedingHours' => [...], // Top 10 estudiantes con menos horas
        'underutilizedCategories' => [...], // Categorías poco usadas
        'optimalDates' => [...], // Fechas sugeridas (fines de semana, feriados)
        'impactSimulation' => [
            'if_10_students_4_hours' => ['new_average' => 45.2, 'improvement' => '+3.5'],
            'if_15_students_6_hours' => ['new_average' => 48.7, 'improvement' => '+7.0'],
        ]
    ];
}
```

#### 2.2 Análisis Individual de Estudiantes
**Prioridad:** Alta  
**Impacto:** Alto

- **Vista detallada por estudiante:** Acceso rápido desde el dashboard a la ficha completa de cada estudiante
- **Comparación con el promedio:** Visualizar cómo cada estudiante se compara con el promedio de su sección
- **Historial de participación:** Gráfico de línea temporal mostrando la evolución de cada estudiante
- **Alertas personalizadas:** Notificaciones sobre estudiantes que:
  - No han participado en X semanas
  - Tienen condiciones de salud sin documentación actualizada
  - Están significativamente por debajo del promedio

#### 2.3 Análisis de Efectividad de Actividades
**Prioridad:** Media  
**Impacto:** Medio

- **Actividades más exitosas:** Ranking de actividades por:
  - Promedio de horas acreditadas
  - Tasa de asistencia
  - Satisfacción (si se implementa feedback)
- **Análisis de repetición:** Identificar actividades que funcionan bien y podrían repetirse
- **Sugerencias de mejora:** Comparar con actividades similares de otros profesores (sin identificarlos)

#### 2.4 Gestión de Evidencias
**Prioridad:** Media  
**Impacto:** Bajo

- **Estado de evidencias:** Resumen de cuántas jornadas tienen evidencias completas vs incompletas
- **Recordatorios de carga:** Alertas para jornadas antiguas sin evidencias
- **Galería rápida:** Vista previa de las últimas evidencias cargadas

---

## 3. Dashboard del Alumno

### Estado Actual
El dashboard de alumno muestra:
- `progress`: Progreso general
- `breakdownByYear`: Desglose por año
- `breakdownByTerm`: Desglose por lapso
- `sessionHistory`: Historial de jornadas
- `closureProjection`: Proyección de cierre
- `categoryParticipation`: Participación por categoría
- `mostRecentSession`: Última jornada
- `sectionAverage`: Promedio de la sección
- `evidenceCount`: Cantidad de evidencias

### Oportunidades de Mejora

#### 3.1 Gamificación y Motivación
**Prioridad:** Alta  
**Impacto:** Alto

- **Sistema de logros/badges:** Reconocimientos visuales por:
  - Alcanzar hitos (25%, 50%, 75%, 100% del cupo)
  - Participar en todas las categorías de actividades
  - Asistencia perfecta en un lapso
  - Ser de los primeros en completar el cupo
- **Ranking de sección (opcional):** Posición relativa dentro de la sección (con opción de ocultarlo si genera presión negativa)
- **Racha de participación:** Contador de semanas consecutivas con al menos una jornada
- **Próximo hito:** Visualización clara de "Te faltan X horas para alcanzar el Y%"

**Visualización sugerida:**
```tsx
<div className="achievements-section">
  <Badge variant="gold">🏆 100% Completado</Badge>
  <Badge variant="silver">🌟 Explorador (5 categorías)</Badge>
  <Badge variant="bronze">🔥 Racha de 4 semanas</Badge>
</div>

<div className="next-milestone">
  <ProgressBar value={78} />
  <p>¡Solo 22 horas más para alcanzar el 100%!</p>
</div>
```

#### 3.2 Planificador Personal
**Prioridad:** Media  
**Impacto:** Alto

- **Calendario de próximas jornadas:** Vista de jornadas programadas por todos los profesores (si están públicas)
- **Recordatorios personalizados:** Notificaciones sobre jornadas próximas en las que podría participar
- **Simulador de escenarios:** "Si participas en las próximas 3 jornadas, alcanzarás X horas"
- **Sugerencias de participación:** Recomendaciones de jornadas basadas en:
  - Categorías en las que ha participado menos
  - Ubicaciones cercanas a su dirección (si está registrada)
  - Horarios que históricamente le funcionan mejor

#### 3.3 Análisis Comparativo Mejorado
**Prioridad:** Media  
**Impacto:** Medio

- **Comparación con promociones anteriores:** "Tu generación va X% más rápido/lento que la generación anterior a esta altura del año"
- **Percentil en la institución:** "Estás en el top 25% de todos los estudiantes"
- **Gráfico de distribución:** Histograma mostrando dónde se ubica el estudiante respecto a todos sus compañeros

#### 3.4 Portafolio de Experiencias
**Prioridad:** Baja  
**Impacto:** Medio

- **Resumen narrativo:** Generación automática de un texto descriptivo: "Has participado en X jornadas, explorando Y categorías diferentes, acumulando Z horas en total"
- **Galería de evidencias:** Vista de todas las fotos/documentos de las jornadas en las que participó
- **Certificado de progreso:** Documento descargable (PDF) con su progreso actual (útil para mostrar a representantes)
- **Mapa de experiencias:** Visualización geográfica de todas las ubicaciones visitadas

---

## 4. Dashboard del Representante

### Estado Actual
El dashboard de representante muestra:
- `studentName`: Nombre del representado
- `studentId`: ID del representado
- `progress`: Progreso del representado
- `last4WeeksTrend`: Tendencia de últimas 4 semanas
- `nextSession`: Próxima jornada
- `healthReminder`: Recordatorio de salud

### Oportunidades de Mejora

#### 4.1 Vista Multi-Representado
**Prioridad:** Alta  
**Impacto:** Alto

**Problema actual:** Si un representante tiene 2 o más estudiantes a cargo, debe cambiar de contexto para ver cada uno.

**Solución propuesta:**
- **Dashboard consolidado:** Vista única que muestre todos los representados simultáneamente
- **Comparación entre hermanos:** Si tiene múltiples representados, mostrar comparativas:
  - Progreso relativo de cada uno
  - Alertas consolidadas
  - Próximas jornadas de todos
- **Selector rápido:** Dropdown para cambiar entre vista consolidada y vista individual

**Implementación sugerida:**
```php
public function getRepresentativeDashboard(int $representativeId, ?int $academicYearId = null): array
{
    $students = User::whereHas('representatives', function($q) use ($representativeId) {
        $q->where('representative_id', $representativeId);
    })->get();

    if ($students->count() === 1) {
        // Vista individual (actual)
        return $this->getSingleStudentView($students->first(), $academicYearId);
    } else {
        // Vista consolidada (nueva)
        return [
            'viewType' => 'consolidated',
            'students' => $students->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'progress' => $this->getStudentProgress($s->id, $academicYearId),
                'alerts' => $this->getStudentAlerts($s->id),
            ]),
            'consolidatedAlerts' => [...],
            'upcomingSessions' => [...], // De todos los estudiantes
        ];
    }
}
```

#### 4.2 Comunicación y Seguimiento
**Prioridad:** Media  
**Impacto:** Alto

- **Historial de comunicaciones:** Registro de cuándo el representante revisó el dashboard (para demostrar seguimiento activo)
- **Notificaciones configurables:** Permitir al representante elegir sobre qué eventos quiere ser notificado:
  - Nuevas jornadas completadas
  - Hitos alcanzados (25%, 50%, 75%, 100%)
  - Alertas de bajo rendimiento
  - Recordatorios de salud
- **Exportación de reportes:** Botón para descargar un PDF con el progreso actual del representado

#### 4.3 Contexto Educativo
**Prioridad:** Baja  
**Impacto:** Medio

- **Explicación del sistema:** Sección informativa sobre qué es la asignatura Socioproductiva y por qué es importante
- **Glosario de términos:** Explicaciones de "lapso", "jornada", "categoría de actividad", etc.
- **FAQs para representantes:** Preguntas frecuentes específicas para este rol
- **Contacto con profesores:** Información de contacto del profesor asignado a la sección del representado

#### 4.4 Análisis de Participación
**Prioridad:** Media  
**Impacto:** Medio

- **Patrón de participación:** Identificar si el estudiante participa más en ciertos días/horarios
- **Comparación con compañeros:** "Tu representado está en el promedio de su sección" o "está por debajo del promedio"
- **Sugerencias de apoyo:** Recomendaciones automáticas:
  - "Considera motivar a tu representado a participar en jornadas de [categoría X]"
  - "El promedio de la sección es Y horas, tu representado tiene Z"

---

## 5. Mejoras Transversales (Todos los Dashboards)

### 5.1 Filtros y Personalización
**Prioridad:** Alta  
**Impacto:** Alto

- **Selector de año académico:** Ya implementado, pero mejorar la UX con un dropdown más visible
- **Selector de lapso:** Filtrar todas las estadísticas por lapso específico
- **Rango de fechas personalizado:** Permitir análisis de periodos arbitrarios
- **Guardar vistas personalizadas:** Permitir a cada usuario configurar qué widgets ver y en qué orden

### 5.2 Exportación y Compartir
**Prioridad:** Media  
**Impacto:** Medio

- **Exportar a PDF:** Generar un reporte PDF del dashboard actual
- **Exportar a Excel:** Descargar datos tabulares para análisis externo
- **Compartir snapshot:** Generar un enlace temporal para compartir una vista específica del dashboard (útil para reuniones)

### 5.3 Visualizaciones Mejoradas
**Prioridad:** Media  
**Impacto:** Alto

- **Gráficos interactivos:** Usar una librería como Recharts o Chart.js para gráficos más dinámicos
- **Tooltips informativos:** Explicaciones contextuales al pasar el mouse sobre métricas
- **Animaciones suaves:** Transiciones visuales al cambiar filtros o actualizar datos
- **Modo de presentación:** Vista fullscreen optimizada para proyectar en reuniones

### 5.4 Rendimiento y Caching
**Prioridad:** Alta  
**Impacto:** Alto

- **Cache de estadísticas:** Cachear cálculos pesados con invalidación inteligente
- **Carga progresiva:** Cargar widgets críticos primero, luego los secundarios
- **Skeleton loaders:** Mostrar placeholders mientras cargan los datos
- **Actualización en tiempo real:** WebSockets para actualizar dashboards sin recargar (opcional, baja prioridad)

### 5.5 Accesibilidad
**Prioridad:** Media  
**Impacto:** Medio

- **Modo de alto contraste:** Para usuarios con problemas visuales
- **Navegación por teclado:** Asegurar que todos los widgets sean accesibles sin mouse
- **Lectores de pantalla:** Etiquetas ARIA apropiadas en todos los componentes
- **Tamaños de fuente ajustables:** Permitir al usuario aumentar/disminuir el tamaño del texto

---

## 6. Nuevas Funcionalidades Sugeridas

### 6.1 Dashboard de Comparación Institucional
**Prioridad:** Baja  
**Impacto:** Bajo

Si en el futuro el sistema se expande a múltiples instituciones (multi-tenant), crear un dashboard que compare:
- Promedio de horas por institución
- Tasa de cumplimiento por institución
- Mejores prácticas identificadas

### 6.2 Dashboard de Auditoría
**Prioridad:** Media  
**Impacto:** Medio

Para administradores, un dashboard que muestre:
- Actividad reciente del sistema (últimas jornadas creadas, asistencias registradas)
- Usuarios más activos
- Errores o inconsistencias detectadas
- Uso del sistema por hora/día/mes

### 6.3 Dashboard de Proyección de Recursos
**Prioridad:** Baja  
**Impacto:** Bajo

Para administradores, proyectar:
- Cuántas jornadas más se necesitan para que todos alcancen el cupo
- Carga de trabajo proyectada para profesores
- Necesidades de transporte/materiales basadas en jornadas planificadas

---

## 7. Priorización Recomendada

### Fase 1 - Mejoras Críticas (Hito 13 o 14)
1. **Admin:** Análisis predictivo y proyecciones (1.1)
2. **Profesor:** Planificador de jornadas inteligente (2.1)
3. **Alumno:** Gamificación y motivación (3.1)
4. **Representante:** Vista multi-representado (4.1)
5. **Transversal:** Filtros y personalización (5.1)

### Fase 2 - Mejoras de Alto Impacto (Hito 15)
1. **Admin:** Análisis de eficiencia docente (1.2)
2. **Profesor:** Análisis individual de estudiantes (2.2)
3. **Alumno:** Planificador personal (3.2)
4. **Representante:** Comunicación y seguimiento (4.2)
5. **Transversal:** Visualizaciones mejoradas (5.3)

### Fase 3 - Mejoras Complementarias (Post-lanzamiento)
1. **Admin:** Dashboard de salud institucional (1.4)
2. **Profesor:** Análisis de efectividad de actividades (2.3)
3. **Alumno:** Análisis comparativo mejorado (3.3)
4. **Representante:** Análisis de participación (4.4)
5. **Transversal:** Exportación y compartir (5.2)

### Fase 4 - Mejoras Opcionales (Futuro)
1. **Admin:** Análisis geográfico (1.3)
2. **Profesor:** Gestión de evidencias (2.4)
3. **Alumno:** Portafolio de experiencias (3.4)
4. **Representante:** Contexto educativo (4.3)
5. **Transversal:** Accesibilidad (5.5)

---

## 8. Consideraciones Técnicas

### 8.1 Arquitectura de Datos
- **Mantener HourAccumulatorService como fuente única de verdad:** Todas las estadísticas deben calcularse en este servicio
- **Evitar lógica de negocio en React:** Los componentes solo deben renderizar, no calcular
- **Cachear agresivamente:** Las estadísticas son costosas de calcular, usar Redis o cache de Laravel

### 8.2 Performance
- **Índices de base de datos:** Asegurar que todas las consultas frecuentes tengan índices apropiados
- **Paginación:** Para listados largos (ej: todos los estudiantes de la institución)
- **Lazy loading:** Cargar widgets bajo demanda, no todos al cargar la página

### 8.3 Testing
- **Tests de regresión:** Cada nueva métrica debe tener tests que validen su cálculo
- **Tests de performance:** Asegurar que los dashboards carguen en < 2 segundos
- **Tests de accesibilidad:** Validar que los dashboards cumplan WCAG 2.1 AA

---

## 9. Conclusión

El sistema actual tiene una base sólida de dashboards funcionales. Las mejoras propuestas se enfocan en:

1. **Valor predictivo:** Pasar de "qué pasó" a "qué va a pasar"
2. **Accionabilidad:** Dar a cada rol herramientas para tomar decisiones informadas
3. **Motivación:** Especialmente para alumnos, hacer el seguimiento más engaging
4. **Eficiencia:** Reducir el tiempo que cada rol necesita para entender su situación

La implementación gradual por fases permite entregar valor incremental sin comprometer la estabilidad del sistema.

---

**Próximos pasos sugeridos:**
1. Revisar este documento con el cliente para validar prioridades
2. Crear specs detalladas para las mejoras de Fase 1
3. Estimar esfuerzo de desarrollo para cada mejora
4. Incorporar mejoras seleccionadas en Hito 13 o crear un Hito 16 dedicado a "Dashboards Avanzados"
