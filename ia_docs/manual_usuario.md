# MANUAL DE USUARIO: CONFIGURACIÓN Y PUESTA EN MARCHA

## SISTEMA DE BITÁCORA SOCIOPRODUCTIVA

Este manual está diseñado para guiar al personal administrativo y docente de la institución educativa en la configuración inicial, carga académica y el uso diario del sistema **Bitácora Socioproductiva**.

El objetivo es facilitar el registro, control y reporte de las horas de trabajo de campo acumuladas por los estudiantes en la asignatura de Socioproductiva para cumplir con el requisito indispensable para su graduación.

---

## 1. INTRODUCCIÓN Y CONCEPTOS CLAVE

### ¿Qué es la Bitácora Socioproductiva?

Es una plataforma web centralizada que reemplaza las bitácoras físicas en papel o las hojas de cálculo individuales. Su propósito exclusivo es registrar en qué actividades participan los estudiantes, cuántas horas acumulan en cada jornada de campo, qué nivel de desempeño tienen y cuántas horas les restan para completar el cupo requerido por la institución.

### Alcance del Sistema (Fuera de Alcance)

Para evitar confusiones en su uso, es vital comprender lo que el sistema **no** hace:

- **No gestiona otras asignaturas**: Solo almacena y calcula datos referentes a la asignatura Socioproductiva.
- **No es un gestor de calificaciones generales**: No almacena notas del boletín de otras asignaturas ni promedios académicos generales.
- **No es un sistema de comunicación general**: No incluye chats, foros ni herramientas de mensajería interna.
- **No es multi-institución**: Está configurado exclusivamente para el plantel local.

### Roles de Usuario

El sistema clasifica a las personas según sus responsabilidades reales:

1. **Administrador**: Posee control total. Configura el plantel, crea los años escolares y lapsos, gestiona los usuarios, asigna profesores a las secciones, carga horas de alumnos transferidos y tiene acceso a todos los reportes y estadísticas globales.
2. **Profesor (Docente)**: Es el responsable en el campo de trabajo. Crea las jornadas de campo, toma asistencia, carga las horas detalladas de las subactividades de sus estudiantes y adjunta evidencias fotográficas. Puede consultar la información de salud de los alumnos a su cargo y generar reportes de sus secciones asignadas.
3. **Estudiante (Alumno)**: Tiene acceso de solo lectura. Puede visualizar su barra de progreso de horas, el desglose de asistencia por lapsos, su historial de jornadas y descargar su certificado oficial de horas acumuladas en PDF.
4. **Representante**: Tiene acceso de solo lectura. Su única función es consultar el avance en tiempo real, historial y estado de horas del estudiante o estudiantes que tiene a su cargo, así como verificar que su ficha médica esté al día.

---

## 2. CONFIGURACIÓN INICIAL: DATOS INSTITUCIONALES

Antes de comenzar a registrar alumnos o jornadas, el administrador debe definir la identidad oficial de la sede. Estos datos se utilizarán para membretar y validar todos los certificados e informes en PDF que emita el sistema.

### Pasos para Configurar los Datos de la Institución:

1. Ingrese con su cuenta de **Administrador**.
2. En el menú lateral izquierdo, diríjase a **Configuración** y seleccione **Datos Institucionales**.
3. Complete el formulario con la información oficial del plantel:
    - **Nombre de la Institución**: Nombre completo y oficial de la unidad educativa (por ejemplo: _U.E. Amantina de Sucre_).
    - **Dirección**: Ubicación física detallada del plantel.
    - **Teléfono**: Número de contacto oficial de las oficinas administrativas.
    - **Correo Electrónico**: Correo institucional para notificaciones y soporte.
    - **Código del Plantel**: Código identificador oficial del ministerio o zona educativa.
4. **Logotipo**: Cargue la imagen oficial del logo institucional. Asegúrese de que tenga buena resolución, ya que aparecerá en el encabezado de los certificados en PDF generados para los estudiantes.
5. Haga clic en **Guardar Cambios**.

---

## 3. DISEÑO DE LA ESTRUCTURA ACADÉMICA (EL ESQUELETO OPERATIVO)

La estructura académica organiza jerárquicamente a los estudiantes y profesores. Para que el sistema funcione en el día a día, debe existir este esqueleto en orden descendente:
**Año Académico ➔ Lapsos Escolares ➔ Grados ➔ Secciones**.

### Paso 3.1: Configurar las Definiciones (Catálogos Base)

Antes de crear los elementos de la estructura académica (lapsos, grados y secciones), el sistema requiere que existan **Definiciones Base**. Estas definiciones actúan como "plantillas" o catálogos globales de los que usted seleccionará nombres. Esto garantiza que todos los años escolares usen la misma nomenclatura (evitando que alguien escriba "Primer Año" un año y "1er Año" el siguiente).

1. Desde el menú de **Configuración**, diríjase a la sección **Definiciones** en la barra lateral.
2. **Lapsos**: Defina los nombres de los periodos que usará (ej. _Primer Lapso_, _Segundo Lapso_).
3. **Grados**: Defina los nombres de los grados académicos (ej. _1er Año_). Deberá indicar un **Orden** numérico (ej: 1) para que el sistema los ordene correctamente de menor a mayor.
4. **Secciones**: Defina las letras o identificadores de las aulas (ej. _A_, _B_, _C_).

Una vez establecidas estas plantillas maestras, estarán disponibles en los selectores cada vez que configure un nuevo año escolar.

### Paso 3.2: Configurar el Año Académico

Cada año escolar es independiente y contiene sus propios límites y requisitos de horas.

1. Abra el menú de su perfil de usuario (esquina inferior izquierda) y haga clic en **Configuración**.
2. En el menú lateral, bajo la sección **Gestión Académica**, seleccione **Años Escolares**.
3. Haga clic en el botón **Nuevo Año Escolar**.
4. Complete los datos requeridos:
    - **Nombre**: Nombre representativo (ejemplo: _2025-2026_).
    - **Fecha de Inicio y Fin**: Rango de fechas que abarca el período escolar completo.
    - **Horas Requeridas**: El cupo total de horas de Socioproductiva que la promoción de este año debe cumplir para graduarse (ejemplo: _600.00_). Este valor puede cambiar entre generaciones de acuerdo con las normativas vigentes.

5. **Activar el Año**: Una vez creado, active el año vigente. **Nota Importante:** El sistema solo permite tener **un (1) único año académico activo a la vez**. Al activar un nuevo año, el anterior se desactiva automáticamente para evitar errores de carga.

### Paso 3.3: Configurar los Lapsos Escolares (Términos)

El año activo debe dividirse en periodos de tiempo más cortos (lapsos o términos) para permitir reportes parciales.

1. En el menú de **Configuración**, bajo **Gestión Académica**, ingrese a **Lapsos Académicos**.
2. Haga clic para crear un nuevo lapso. Allí el sistema le pedirá seleccionar un nombre desde las Definiciones de Lapsos creadas en el Paso 3.1.
3. Al crear un lapso, asigne las fechas de inicio y fin correspondientes.
4. **Reglas del Sistema**:
    - Las fechas de los lapsos no pueden solaparse entre sí.
    - El sistema asignará de forma automática el número del lapso (1, 2 o 3) según el orden cronológico.
    - **Es obligatorio configurar los lapsos antes de registrar jornadas**. Al guardar una jornada de campo, el sistema analizará la fecha del evento y la asignará de manera automática al lapso que le corresponda. Si no hay lapsos creados, el sistema mostrará un error al profesor.

### Paso 3.4: Configurar Grados y Secciones

Representan las aulas físicas y agrupaciones de los estudiantes en el año escolar activo.

1. Desde **Configuración**, bajo **Gestión Académica**, vaya a **Grados** y proceda a crear un nuevo grado.
    - El sistema le pedirá que seleccione el nombre del grado desde las **Definiciones de Grados** creadas previamente (ej. _5to Año_).
2. Luego vaya a **Secciones** (bajo Gestión Académica) y proceda a crear una nueva sección.
    - Seleccione el grado al que pertenecerá.
    - Seleccione la letra de la sección desde las **Definiciones de Secciones** (ej. _A_, _B_).
    - El sistema valida que no existan secciones con el mismo nombre dentro del mismo grado en el año activo.

---

## 4. GESTIÓN DE ROLES E INTEGRANTES (USUARIOS Y VÍNCULOS)

Una vez construida la estructura académica, es momento de registrar a las personas que interactúan en la aplicación.

### 4.1 Registro del Personal (Administradores y Profesores)

Por razones de seguridad institucional, responsabilidad civil y el manejo de información de menores de edad, **no se permite el registro público de personal docente ni administrativo**. Todo el personal institucional debe ser registrado manualmente por un administrador dirigiéndose a **Configuración** ➔ **Gestión Escolar** ➔ **Gestión de Usuarios** y haciendo clic en **Nuevo Usuario**.

- **Información Requerida**: Para docentes y administradores, todos los campos de contacto (cédula, correo electrónico, teléfono y dirección) son **obligatorios**. No se permiten perfiles institucionales incompletos.
- **Niveles de Acceso**: Por razones de seguridad, **ningún usuario puede modificar su propio nivel de acceso al sistema**. Si un administrador necesita actualizar sus permisos, deberá solicitar a otro administrador que realice el cambio.

### 4.2 Registro e Inscripción de Estudiantes

El sistema maneja dos flujos para el ingreso de estudiantes:

#### A) Autoregistro Público (Flujo Recomendado para Alumnos)

Para evitarle carga administrativa al personal, los estudiantes pueden registrarse ellos mismos:

1. El alumno ingresa al enlace público `/register` en el navegador.
2. Completa su nombre, cédula, correo personal, contraseña, teléfono y dirección residencial. Todos estos datos de contacto inicial son obligatorios para el alumno al autoregistrarse, evitando la creación de "perfiles fantasma".
3. **Casilla de Transferido**:
    - Si el alumno es un **nuevo ingreso regular** (comienza desde cero en la institución), deja la casilla desactivada. El sistema asociará de forma automática el nombre de la institución local configurada en el Paso 1.
    - Si el alumno es **transferido de otra institución** (viene de otro colegio a cursar años superiores), debe activar la casilla y escribir de manera obligatoria el nombre de la _Institución de Procedencia_. Esto habilitará en su expediente la opción de reconocerle horas acumuladas anteriormente (ver Sección 6).
4. Al hacer clic en registrarse, el usuario queda creado con el rol por defecto de **Estudiante**.

#### B) Inscripción Individual (Carga Manual de Estudiantes)

Cuando el administrador o docente deba inscribir individualmente a un estudiante nuevo:

1. Desde **Configuración**, bajo **Gestión Escolar**, ingrese a **Inscripciones** y haga clic en nueva inscripción.
2. Busque al estudiante escribiendo su nombre o cédula. El sistema solo mostrará alumnos creados que no posean una inscripción activa en el año vigente.
3. Seleccione el **Grado** y la **Sección** en la que cursará.
4. Guarde el registro. A partir de ese momento, el estudiante aparecerá como inscrito en esa sección.

#### C) Panel de Promoción Masiva (Inicio de Año Escolar)

Este panel es la herramienta principal al inicio de cada año escolar para promover de grado a los estudiantes del año anterior con unos pocos clics. Presenta una interfaz de **doble panel**:

- **Panel Izquierdo (Origen - Año Anterior)**:
    1. Seleccione el Año Escolar anterior, el Grado y la Sección de origen.
    2. Aparecerá la lista de estudiantes inscritos en esa aula.
    3. Los estudiantes que ya fueron inscritos o promovidos en el año activo aparecerán con una etiqueta de "Ya inscrito" y su casilla de selección deshabilitada para evitar dobles inscripciones.
    4. Seleccione mediante las casillas de verificación (o con el botón _Seleccionar Todos_) a los estudiantes que aprobaron y serán promovidos juntos.

- **Panel Derecho (Destino - Año Activo)**:
    1. El sistema realiza una **sugerencia de grado superior**: cargará automáticamente las secciones del año activo pertenecientes al grado inmediato superior (ejemplo: si el origen era _1er Año_, el destino sugerirá _2do Año_) para agilizar la promoción masiva.
    2. Si el grado superior sugerido no está creado en el año activo, el sistema mostrará una alerta y le permitirá al administrador seleccionar el grado de forma manual mediante un selector.
    3. En cada sección del panel destino verá una tarjeta con el nombre de la sección, un contador de alumnos ya inscritos en ella y el botón **Promover Aquí**.
    4. Al hacer clic en **Promover Aquí** de la sección deseada, el sistema solicitará una confirmación detallando el aula origen, el aula destino y el número de alumnos. Al confirmar, los alumnos seleccionados serán transferidos al nuevo año académico y sección al instante, desapareciendo del panel izquierdo para un control visual ágil y ordenado.

> **Consejo Operativo:** El administrador tiene la flexibilidad de redistribuir un grupo. Por ejemplo, de 30 estudiantes seleccionados en el panel izquierdo, puede marcar a 10 y hacer clic en "Promover Aquí" en la Sección A del panel derecho, luego seleccionar a otros 10 y promoverlos a la Sección B, y al resto a la Sección C.

### 4.3 Asignación de Profesores a Secciones

Para que los docentes puedan registrar jornadas y controlar la asistencia de una sección, primero deben ser asignados formalmente a ella.

1. Desde **Configuración**, bajo **Gestión Escolar**, diríjase a **Asignaciones Docentes** y haga clic para crear una nueva asignación.
2. Seleccione el nombre del **Profesor** de la lista (solo aparecen usuarios con rol _profesor_).
3. Seleccione el **Grado** y la **Sección** de la que será responsable en el año activo.
4. Confirme la asignación.

- **Nota**: Un profesor puede tener múltiples secciones asignadas simultáneamente en el año escolar activo. Asimismo, si la dinámica del plantel lo requiere, una sección puede tener asignado a más de un profesor.

### 4.4 Vinculación de Representantes (Solo Lectura)

Los representantes necesitan tener acceso al sistema para supervisar los avances de sus hijos.

1. Desde **Configuración**, ingrese a **Gestión de Usuarios**, busque al estudiante y acceda a su perfil detallado (**Ficha del Estudiante**) haciendo clic en el icono de Ver (ojo).
2. En la sección de **Representantes Legales**, haga clic en **Asignar**.
3. Busque al representante por su cédula o nombre (el usuario representante debe haber sido creado previamente).
4. Seleccione el **Tipo de Parentesco** desde el catálogo (Padre, Madre, Tutor Legal, Otro).
5. Guarde los cambios.

- **Multi-Rol e Inicio de Sesión**: Un representante puede tener a varios estudiantes vinculados a su cargo (ejemplo: hermanos). Además, si un docente del colegio es a la vez representante de un estudiante, su cuenta poseerá ambos roles. Al ingresar a la aplicación con su correo, el sistema le presentará un selector de contexto de rol al inicio para que elija si desea operar como **Profesor** o como **Representante**, manteniendo la privacidad y las funciones operativas de forma aislada.

---

## 5. CATÁLOGOS OPERATIVOS Y FICHA MÉDICA

Antes de iniciar con el registro diario en campo, existen catálogos y fichas informativas que los docentes y administradores deben completar para personalizar la experiencia.

### 5.1 Catálogos de Ubicaciones y Categorías de Actividades

El sistema provee menús bajo **Configuración** para predefinir los lugares y las labores que se realizan en el huerto o la institución. **Tanto los Administradores como los Profesores tienen permiso para crear, editar y eliminar** opciones de estos catálogos, permitiendo a los docentes adaptar las opciones a la realidad de su trabajo de campo sin depender de un administrador.

- **Ubicaciones**: Catálogo de sitios físicos (ejemplo: _Cancha de Fútbol_, _Huerto Escolar Sede A_, _Salón de Laboratorio_).
- **Categorías de Actividad**: Catálogo de labores (ejemplo: _Riego_, _Siembra_, _Desmalezamiento_, _Limpieza_, _Inventariado_).

#### Historial Seguro de Actividades

Los catálogos funcionan como plantillas para agilizar el trabajo. Al momento de crear una jornada de campo, el sistema **guarda el nombre exacto** de la ubicación y de la actividad seleccionada.
Esto significa que si en el futuro un administrador o profesor modifica o elimina una opción del catálogo, **las jornadas pasadas no se verán alteradas**, garantizando que el expediente de los estudiantes se mantenga intacto.

**Valor Estratégico:** Es vital recalcar que un correcto uso de estas plantillas hará que los reportes de horas detallados por actividades sirvan como un medio de control para los administradores y profesores sobre los avances y tareas específicas que realizan los estudiantes en cada jornada, alimentando estadísticas precisas del desempeño personal de cada estudiante o de la sección en general.

### 5.2 Información de Salud de Estudiantes (Ficha Médica)

Debido a que Socioproductiva incluye trabajo físico y herramientas de campo en el huerto, la institución necesita conocer las limitaciones físicas o de salud de los alumnos antes de asignarles tareas.

- **Cómo Registrar una Condición**: El administrador o docente con permisos debe acceder a la ficha del estudiante y, en la pestaña de **Salud**, hacer clic en **Registrar Ficha Médica**.
- **Campos Obligatorios de Validación**:
    - **Condición de Salud**: Selección del catálogo predefinido (ejemplo: _Asma_, _Alergias_, _Diabetes_).
    - **Recibido Por**: Nombre del funcionario del plantel que recibió el soporte físico.
    - **Recibido En**: Fecha y hora exacta de la entrega del documento.
    - **Lugar de Entrega**: Oficina o departamento donde se formalizó la entrega (ejemplo: _Dirección del Plantel_).
    - **Observaciones**: Indicaciones específicas (ejemplo: _No exponer al sol directo más de 30 minutos_).
    - **Documento Adjunto**: Es estrictamente obligatorio digitalizar y cargar en formato PDF o imagen el informe médico o certificado firmado y sellado por el especialista.
- **Impacto en el Sistema**: La ficha médica es estrictamente informativa y de protección física. **No reduce bajo ninguna circunstancia el cupo de horas exigidas al estudiante ni genera alertas automáticas de exención**. Su función es que los docentes a cargo consulten el perfil del estudiante en pantalla antes de enviarlo a tareas de campo intensivas, pudiendo reasignarlo a actividades más livianas (ejemplo: registro de inventario) en caso de limitaciones de salud.

---

## 6. EL DÍA A DÍA EN EL CAMPO (PLANIFICACIÓN, ASISTENCIA Y HORAS)

Esta es la sección operativa diaria utilizada por los Profesores y Administradores para dar vida al sistema.

### Paso 5.1: Registrar y Planificar una Jornada de Campo

Cada actividad programada o realizada se conoce como **Jornada de Campo** (registrada bajo el nombre técnico de _Sesión de Campo_).

1. En el menú lateral principal, ingrese a **Jornadas** (o **Mis Jornadas** si es profesor) y haga clic en el botón para crear una nueva jornada.
2. Complete el formulario de planificación:
    - **Nombre de la Jornada**: Título descriptivo (ejemplo: _Jornada de Preparación de Tierra para Canteros_).
    - **Descripción**: Objetivos y herramientas a llevar.
    - **Año Académico**: Se bloquea de manera automática en el año activo del sistema.
    - **Profesor Responsable**: Seleccione el docente que liderará la jornada en el campo.
    - **Fechas y Horarios**: Ingrese fecha y hora exacta de **Inicio** y de **Fin**.
    - **Horas Base**: El sistema calculará automáticamente las horas de duración a partir del horario ingresado. Este valor servirá como guía y límite sugerido para la asignación posterior.
    - **Categoría de Actividad y Ubicación**: Seleccione las opciones de los catálogos correspondientes.
    - **Estado de la Jornada**:
        - **Planificada (Planned)**: Para jornadas que ocurrirán en el futuro o están por iniciar. El sistema es flexible y permite registrar asistencia y actividades en este estado para facilitar la dinámica en tiempo real del docente.
        - **Realizada (Realized)**: Para jornadas que ya concluyeron. Cambiar el estado a "Realizada" ayuda a los administradores a saber que el trabajo de campo de ese día finalizó de forma exitosa y mantener organizado el historial.
        - **Cancelada (Cancelled)**: Si la jornada debió suspenderse. El sistema exige escribir obligatoriamente el **Motivo de Cancelación** para auditoría y archivo histórico.
3. Guarde el registro.

### Paso 5.2: Toma de Asistencia y Acreditación de Horas

Al finalizar una jornada que se encuentre en estado **Realizada (Realized)**, el profesor responsable debe cargar los resultados:

1. Desde la pantalla de detalles de la jornada, haga clic en **Registrar Asistencia**.
2. El sistema mostrará, por defecto, a **todos los estudiantes que posean una inscripción activa** en el año escolar vigente. Para ubicar rápidamente a su grupo, utilice los filtros de **Grado** y **Sección** ubicados en la parte superior de la tabla.
3. **Registro Inicial de Asistencia**: Puede registrar la asistencia de los alumnos presentes de dos formas:
    - **Individual**: Haciendo clic en el botón **Registrar** ubicado al final de la fila de cada estudiante.
    - **Masiva**: Marcando las casillas de selección a la izquierda de varios estudiantes (o la casilla superior para marcarlos todos) y haciendo clic en el botón **Registrar seleccionados**.
4. **Asignación de Actividades y Horas**:
   Una vez que el estudiante está registrado en la jornada, aparecerá el botón de **Actividades** (ícono de lista) al final de su fila. Al hacer clic en él, se abrirá un panel donde el docente debe desglosar las horas trabajadas:
    - Haga clic en **Añadir actividad**.
    - Seleccione la **Categoría de Actividad** (creada previamente en Configuración > Catálogos).
    - Ingrese la cantidad de **Horas** dedicadas a esa labor específica.
    - (Opcional) Agregue **Notas** descriptivas del desempeño.
    - (Opcional) Suba **Evidencias fotográficas** que certifiquen el trabajo de campo.
    - Puede añadir tantas actividades como desee para un mismo estudiante si este realizó diferentes tareas durante la jornada (ej. 2 horas de Siembra y 2 horas de Riego). El sistema sumará el total de horas automáticamente.
5. **Reglas e Integridad de Horas**:
    - **Alerta de Horas Base**: Si la suma de las subactividades de un estudiante supera las _Horas Base_ de la jornada calculadas en la planificación, el sistema mostrará una advertencia visual amarilla, pero **no bloqueará el registro**. Esto permite que el profesor acredite horas extras a alumnos que se quedaron más tiempo trabajando de forma justificada.
    - **Asistió pero no trabajó**: Si un estudiante estuvo presente físicamente pero no realizó actividades productivas, el profesor puede simplemente registrar su asistencia (`Registrar`) y no añadirle ninguna actividad desde el panel de Actividades. El estudiante quedará con su asistencia confirmada pero acumulará 0 horas en la jornada.

### Paso 5.3: Responsabilidad sobre las Jornadas

Para proteger la información cargada:

- **Solo el profesor responsable asignado a la jornada o un Administrador pueden modificar, editar, tomar asistencia o eliminar una jornada y sus registros**.
- Otros profesores pueden consultar las jornadas de sus colegas en modo de "solo lectura", pero los botones de edición y guardado estarán proactivamente bloqueados y ocultos para ellos.

---

## 7. REGISTRO DE HORAS EXTERNAS (ESTUDIANTES TRANSFERIDOS)

Cuando un estudiante se incorpora a la institución en grados superiores (por ejemplo, en 4to o 5to Año) habiendo cursado los años anteriores en otro plantel educativo, es indispensable reconocerle oficialmente las horas de Socioproductiva acumuladas en su colegio de origen.

### Consideraciones para Horas Externas:

1. **Exclusividad del Administrador**: Solo los usuarios con rol de **Administrador** poseen permisos para registrar, editar o eliminar horas externas en el sistema. Los docentes, representantes y estudiantes no tienen acceso a esta funcionalidad.
2. **Independencia Cronológica**: A diferencia de las jornadas de campo, las horas externas **no se ligan a un año académico del plantel local**. Se registran utilizando un campo de texto libre llamado **Período Académico** (ejemplo: _1er Año en U.E. Colegio Bolívar_).
3. **Carga de Evidencias Obligatoria**: Por cada registro de horas externas, es obligatorio adjuntar en formato digital (PDF o imagen) el **certificado de horas oficial**, debidamente firmado y sellado por las autoridades de la institución de procedencia.

### Pasos para Cargar Horas Externas:

1. Ingrese con el rol de **Administrador**.
2. Desde **Configuración**, ingrese a **Gestión de Usuarios**, busque al estudiante transferido y acceda a su **Ficha del Estudiante** (haciendo clic en el icono del ojo o Ver).
3. En la sección de **Horas Externas**, haga clic en **Registrar Horas Externas**.
4. Complete los datos obligatorios del formulario:
    - **Institución de Origen**: Nombre del plantel de donde proviene el alumno.
    - **Período Académico**: Año escolar o grado cursado en el origen (ejemplo: _2do y 3er Año_).
    - **Horas Acreditadas**: Cantidad de horas válidas a reconocer (ejemplo: _250.00_).
    - **Descripción**: Observaciones o detalles del acuerdo de revalidación.
    - **Certificado / Soporte Digital**: Suba el archivo con el justificativo oficial escaneado.
5. Guarde el registro. Las horas se sumarán de manera automática y permanente al **Total Acumulado Histórico** de horas del estudiante, y se reflejarán tanto en sus dashboards como en los certificados descargables en PDF.

---

## 8. SEGUIMIENTO DE AVANCES Y EXPORTACIÓN DE REPORTES

El fin de la plataforma es la transparencia y la consulta ágil de la información. El sistema ofrece pantallas de visualización adaptadas a cada rol.

### 8.1 Dashboards en Tiempo Real

- **Dashboard del Administrador (KPIs Globales)**:
    - **Cumplimiento Institucional**: Porcentaje general de estudiantes del plantel que han completado con éxito su cupo de horas exigido.
    - **Ranking de Secciones**: Listado ordenado de las secciones con mejor promedio de progreso de horas, permitiendo identificar aulas rezagadas.
    - **Análisis por Lapsos**: Gráficos comparativos de las horas generadas en el Primer, Segundo y Tercer lapso del año activo.
    - **Alertas de Gestión**: Conteo de jornadas planificadas, realizadas y canceladas en la institución.

- **Dashboard del Profesor (Control de Secciones)**:
    - **Mis Secciones**: Lista de las aulas asignadas con el promedio de avance de sus alumnos.
    - **Progreso de Alumnos**: Listado individual de sus estudiantes que muestra de forma rápida quiénes tienen baja asistencia o requieren atención en campo.
    - **Recordatorios de Salud**: Tarjetas de alerta que le recuerdan si algún alumno de su sección activa posee condiciones médicas registradas en su ficha de salud antes de iniciar el trabajo en campo.

- **Dashboard del Estudiante (Progreso Personal)**:
    - **Barra de Progreso**: Indicador visual que muestra el total de horas acumuladas contra la meta requerida del año activo.
    - **Horas Restantes**: Cálculo exacto de cuántas horas le restan para completar el requisito de graduación.
    - **Proyección de finalización**: Estimación automática de la fecha en que completará sus horas en base a su ritmo de asistencia actual.

- **Dashboard del Representante (Supervisión Familiar)**:
    - **Progreso del Alumno**: Muestra la misma barra visual y avance de horas de su representado de manera directa.
    - **Alertas de Asistencia**: Notificación de ausencias a jornadas recientes o justificaciones ingresadas por el profesor.

### 8.2 Generación de Reportes y Certificados Oficiales en PDF

El sistema genera documentos estandarizados listos para su impresión y firma:

- **Certificado de Horas del Estudiante (PDF)**:
    - **Cómo descargarlo**: Tanto el Administrador, el Profesor, el propio Estudiante y su Representante tienen un botón en sus paneles para **Descargar Certificado**.
    - **Contenido**: El documento se genera con el membrete oficial del plantel (Paso 1), el nombre y cédula del alumno, el total histórico de horas acumuladas (sumando jornadas locales y horas externas), el desglose detallado de las actividades realizadas por año escolar y lapso, y un espacio oficial para la firma y sello de las autoridades del plantel.

---

## 9. CONSEJOS OPERATIVOS PARA LA PUESTA EN MARCHA DIARIA

Para garantizar una experiencia sin inconvenientes en el uso del sistema, se recomienda seguir el siguiente orden operativo al iniciar las actividades escolares:

1. **Configuración Cero**: Asegúrese de que los **Datos Institucionales** y el **Logotipo** estén correctamente cargados el primer día.
2. **Setup de Año y Lapsos**: Active el nuevo Año Académico y configure los rangos de fechas de los 3 Lapsos antes de que comience el trabajo en el huerto.
3. **Carga Estructural**: Verifique que los Grados y Secciones estén creados.
4. **Registro de Profesores**: Registre las cuentas de los docentes que impartirán la asignatura.
5. **Promoción de Estudiantes**: Utilice el _Panel de Promoción Masiva_ para trasladar y distribuir rápidamente a la población estudiantil que aprobó el año anterior.
6. **Inscripción de Nuevos Ingresos**: Utilice el formulario de _Inscripción Individual_ para estudiantes nuevos u oyentes del plantel.
7. **Asignación de Profesores**: Vincule a cada profesor con las secciones que supervisará física y administrativamente.
8. **Catálogos Listos**: Complete las ubicaciones y actividades iniciales en la sección de configuración.
9. **Ficha de Salud y Horas Externas**: Registre con prioridad las condiciones médicas y justifique las horas de los estudiantes transferidos antes de su primera salida a campo.
10. **Planificación y Registro Diario**: Anime a los docentes a planificar las jornadas en la plataforma y a tomar asistencia el mismo día de la actividad para mantener los dashboards vivos y transparentes para estudiantes y representantes.
