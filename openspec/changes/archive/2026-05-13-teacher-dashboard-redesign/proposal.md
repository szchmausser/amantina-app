# Proposal: Teacher Dashboard Redesign

## Intent

Rediseñar el dashboard del profesor para que pase de ser un panel meramente informativo a uno **accionable y completo**, portando la estructura y componentes probados del dashboard de administración, pero acotados al ámbito de trabajo del profesor (sus secciones, sus estudiantes, sus jornadas).

El profesor debe poder, en 5 segundos: identificar qué estudiantes están en riesgo, qué secciones requieren atención, qué acciones pendientes tiene, y desde allí poder actuar inmediatamente.

## Current State

El dashboard del profesor actual (`resources/js/pages/teacher/dashboard.tsx`) muestra:

1. **4 Stats Cards**: Total sesiones, completadas, canceladas, pendientes de asistencia
2. **Alerta de Baja Asistencia**: Estudiantes con <3 asistencias (solo si existen)
3. **Recordatorios de Salud**: Estudiantes con condiciones de salud (solo si existen)
4. **Cards de Secciones**: Cada sección con semáforo promedio y **primeros 4 estudiantes** (el resto oculto tras "+X más" no accionable)
5. **Distribución por Categoría**: Barras horizontales de horas por tipo de actividad
6. **Sesiones por Período**: Grid con conteo de sesiones por lapso

### Problemas identificados (ver análisis completo en engram `sdd/teacher-dashboard-redesign/proposal`)

| # | Problema | Severidad |
|---|----------|-----------|
| 1 | No muestra estudiantes en riesgo **por horas acumuladas** (<40% de cuota) | 🔴 Crítico |
| 2 | SectionCards solo muestran 4 estudiantes; el resto invisible | 🔴 Crítico |
| 3 | No hay sentido de urgencia / acciones pendientes (próximas jornadas, quick actions) | 🟡 Alto |
| 4 | No hay tabla comparativa de secciones (vista de conjunto) | 🟡 Alto |
| 5 | No hay tendencias / evolución temporal | 🟡 Medio |
| 6 | Distribución por Categoría es informativa pero no actionable | 🟢 Bajo |
| 7 | No hay búsqueda de estudiantes por sección | 🟢 Bajo |

## Target State

Portar la estructura del **admin dashboard** al profesor, filtrando siempre por `teacher_assignments`. El profesor verá exactamente los mismos patrones visuales (semáforos, badges, tooltips, modales, distribución, secciones, rankings) pero únicamente con **sus datos**:

| Componente Admin | Versión Profesor (filtrada) |
|---|---|
| Alertas Críticas | ✅ Sí, sobre sus sesiones y estudiantes |
| Distribución de Estudiantes (en meta/progreso/riesgo) | ✅ Sí, sobre estudiantes de sus secciones |
| Estudiantes Sobresalientes (≥100%) | ✅ Sí, top de sus secciones |
| Ranking de alumnos con más horas | ✅ Sí, top de sus secciones |
| Estudiantes que Necesitan Apoyo (<40%) | ✅ Sí, **esto es lo más crítico que falta hoy** |
| Panorama por Secciones (top + concerning) | ✅ Sí, todas sus secciones ordenadas |
| Modales interactivos con drill-down | ✅ Sí, mismo patrón |
| Location Distribution / Teacher Workload / YoY | ❌ No aplica a nivel profesor |

### Mejoras adicionales (no presentes en admin)

| Mejora | Justificación |
|--------|---------------|
| Próximas jornadas (mini-agenda hoy/esta semana) | El profesor agenda, el admin no |
| Quick actions: "Registrar asistencia", "Nueva jornada", "Ver sección" | El profesor necesita actuar, no solo ver |
| Students at a glance por sección (tabla expandible) | En secciones de 30+ estudiantes, mostrar solo 4 es insuficiente |

## Scope

### In Scope

- Backend: Nuevos métodos/queries en `HourAccumulatorService::getTeacherDashboard()` para métricas de horas por estudiante (at risk, outstanding, zero hours, top students, distribution counts)
- Backend: Agregar `upcomingSessions` (próximas jornadas del profesor)
- Frontend: Reestructurar `teacher/dashboard.tsx` usando los mismos componentes que `admin/dashboard.tsx` (StudentListBadge, SectionCard con drill-down, modales, tooltips)
- Frontend: Quick actions component
- Frontend: Sidebar navigation items para el rol profesor
- Tests: Feature tests para nuevas queries del backend
- Tests: Browser tests para flujos del dashboard del profesor

### Out of Scope

- Creación de páginas CRUD completas para profesor (ej: "Mis Secciones" como página independiente) — eso iría en otro cambio
- Gráficos de tendencia avanzados (charts con librería externa) — dejarlo para cambio futuro
- Exportar datos a PDF/Excel — fuera de alcance
- Multi-tenant o soporte para múltiples instituciones

## Capabilities

### New Capabilities

- `teacher-dashboard-at-risk`: Profesor puede ver estudiantes en riesgo por horas (<40%) en sus secciones
- `teacher-dashboard-zero-hours`: Profesor puede ver estudiantes con 0 horas en sus secciones
- `teacher-dashboard-outstanding`: Profesor puede ver estudiantes sobresalientes (≥100%) en sus secciones
- `teacher-dashboard-ranking`: Profesor puede ver ranking de horas de sus estudiantes
- `teacher-dashboard-distribution`: Profesor puede ver distribución visual (en meta/progreso/riesgo) de sus estudiantes
- `teacher-dashboard-upcoming`: Profesor puede ver sus próximas jornadas agendadas
- `teacher-dashboard-quick-actions`: Profesor tiene accesos directos a acciones comunes
- `teacher-dashboard-expand-sections`: Profesor puede expandir secciones para ver todos los estudiantes

### Modified Capabilities

- `teacher-dashboard-sections`: Las SectionCards ahora muestran distribución completa (en meta/progreso/riesgo) y permiten expandir
- `teacher-dashboard-layout`: El layout cambia de estructura lineal a estructura tipo admin (secciones, rankings, alertas)

## Affected Areas

| Area | Impact | Summary |
|------|--------|---------|
| `app/Services/HourAccumulatorService.php` | Modified | Agregar queries: atRiskStudents, outstandingStudents, topStudents, zeroHourStudents, distribution, upcomingSessions |
| `app/Http/Controllers/DashboardController.php` | Modified | Mapear nuevas props en `teacherDashboard()` |
| `resources/js/pages/teacher/dashboard.tsx` | Rewrite | Reestructurar completamente usando componentes del admin |
| `resources/js/types/dashboard.ts` | Modified | Actualizar `TeacherDashboardData` con nuevos campos |
| `resources/js/components/app-sidebar.tsx` | Modified | Agregar items de navegación para rol profesor |
| `resources/js/components/ui/` | Reuse | StudentListBadge, SectionCard (admin), modales, tooltips — ya existen |
| `tests/Feature/Dashboard/` | New | Tests para nuevas queries del teacher dashboard |
| `tests/Browser/HappyPath/TeacherDashboardTest.php` | New/Modify | Browser tests para el dashboard rediseñado |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Queries complejas impactan performance (varias secciones con muchos estudiantes) | Medium | Índices existentes en FK; las mismas queries ya corren en admin dashboard. Agregar `EXPLAIN ANALYZE` si es necesario. |
| Romper tests existentes del teacher dashboard | Low | Los tests actuales cubren estructura mínima. Al expandir props, los tests existentes pueden fallar si validan props exactas. Actualizar tests en paralelo. |
| Sidebar items para profesor requieren permisos que no existen | Medium | Validar que `spatie/laravel-permission` tenga los permisos necesarios. Si no, crearlos en seeder existente. |
| La "tabla expandible" de estudiantes por sección puede ser compleja en UI | Medium | Primera iteración: modal simple listando todos los estudiantes. Iteración futura: tabla con ordenamiento. |

## Success Criteria

- [ ] Profesor ve estudiantes en riesgo por horas (<40%) en sus secciones
- [ ] Profesor ve estudiantes sobresalientes (≥100%) en sus secciones
- [ ] Profesor ve ranking de horas de sus estudiantes
- [ ] Profesor ve distribución visual (en meta/progreso/riesgo)
- [ ] Profesor puede expandir secciones para ver todos los estudiantes
- [ ] Profesor ve próximas jornadas agendadas
- [ ] Profesor tiene quick actions (registrar asistencia, nueva jornada, ver sección)
- [ ] Sidebar tiene navegación específica para profesor
- [ ] Todos los tests existentes siguen pasando (700+)
- [ ] Nuevos tests cubren las nuevas funcionalidades

## Dependencies

- Ninguna nueva dependencia de paquetes
- Los componentes UI del admin ya existen y están probados
- Las queries base (`getSectionProgress`, `getQuota`, etc.) ya existen en `HourAccumulatorService`

## Rollback Plan

1. Revertir `HourAccumulatorService.php` a la versión anterior de `getTeacherDashboard()`
2. Revertir `teacher/dashboard.tsx` a la versión anterior
3. Revertir `types/dashboard.ts` a la versión anterior
4. Revertir `app-sidebar.tsx` si se modificó
5. Revertir `DashboardController.php` si se modificó
6. Eliminar tests nuevos
7. Ejecutar suite completa para verificar
