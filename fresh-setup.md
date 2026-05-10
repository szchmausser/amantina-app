# Fresh Setup - Amantina App

Fuente de verdad para levantar una copia local del proyecto desde cero y cargar datos de ejemplo suficientes para ver la unidad educativa con dashboards vivos.

**Última verificación real:** 2026-05-06  
**Base usada en esta guía:** SQLite local de desarrollo (`database/database.sqlite`)  
**Seeder completo:** `CompleteTestDataSeeder`

> ⚠️ **Cada máquina tiene sus propios `.env` y `.env.testing`**
>
> Ambos archivos están en `.gitignore`. Cada computadora copia los templates que
> necesita según el motor de base de datos que use (SQLite o PostgreSQL).
>
> El comando de tests es **el mismo** en cualquier máquina:
>
> ```powershell
> php artisan config:clear; php artisan cache:clear; php artisan test --compact tests/Feature/ExampleTest.php
> ```
>
> La diferencia está únicamente en qué template se copia a `.env.testing`.

---

## Camino rápido

> Este flujo destruye y recrea la base local configurada en `.env`. Usalo solo cuando quieras empezar limpio.

```powershell
# 1. Preparar entorno si todavía no existe
Copy-Item .env.sqlite.example .env
New-Item -ItemType File -Path database/database.sqlite -Force
php artisan key:generate

# 2. Limpiar cache de Laravel
php artisan config:clear
php artisan cache:clear

# 3. Recrear base y cargar datos completos
php artisan migrate:fresh --force
php artisan db:seed --class=CompleteTestDataSeeder --force
```

Resultado esperado: base local limpia, estructura escolar completa, usuarios de prueba, inscripciones, jornadas de campo, asistencias y actividades acumuladas.

---

## Requisitos previos

| Requisito | Detalle |
|-----------|---------|
| PHP | PHP 8.4+ recomendado. En esta máquina se usó Laragon PHP 8.5.5. |
| Extensiones PHP | `pdo_sqlite`, `sqlite3` y `sockets` habilitadas en CLI. Pest Browser necesita `sockets`. |
| Composer | Necesario para instalar dependencias PHP. |
| Node.js + npm | Necesario para el frontend. Ejecutá `npm run dev` mientras trabajás. |
| Servidor local | Herd o Laragon. No hace falta crear un servidor manual. |
| Base local | SQLite para setup simple: `database/database.sqlite`. |

Instalación de dependencias si es una copia nueva:

```powershell
composer install --no-interaction
npm install
```

> No uses CDNs ni recursos externos para frontend. El proyecto debe funcionar offline con dependencias instaladas por NPM.

---

## Plantillas `.env.*`

Los archivos `.env` y `.env.testing` **no se commitean** (están en `.gitignore`). Cada máquina tiene los suyos.

Lo que sí viaja por Git son las **plantillas `.example`**, una por motor de base de datos:

### Para aplicación (`.env`)

| Motor | Plantilla | Uso |
|-------|-----------|-----|
| SQLite | `.env.sqlite.example` | Desarrollo local simple, sin servidor de base de datos |
| PostgreSQL | `.env.postgres.example` | Desarrollo local con PostgreSQL (casa) |
| MySQL | `.env.mysql.example` | (futuro — no existe todavía) |

### Para testing (`.env.testing`)

| Motor | Plantilla | Uso |
|-------|-----------|-----|
| SQLite | `.env.sqlite.testing.example` | Testing con SQLite — base separada `database/database_testing.sqlite` |
| PostgreSQL | `.env.postgres.testing.example` | Testing con PostgreSQL — base separada `amantina_app_testing` |
| MySQL | `.env.mysql.testing.example` | (futuro — no existe todavía) |

### Configuración recomendada

#### Trabajo / SQLite

```powershell
# Aplicación
Copy-Item .env.sqlite.example .env
New-Item -ItemType File -Path database/database.sqlite -Force
php artisan key:generate

# Testing
Copy-Item .env.sqlite.testing.example .env.testing
New-Item -ItemType File -Path database/database_testing.sqlite -Force
```

#### Casa / PostgreSQL

```powershell
# Aplicación
Copy-Item .env.postgres.example .env
php artisan key:generate

# Testing
Copy-Item .env.postgres.testing.example .env.testing
```

Después editá en `.env` (y en `.env.testing` si hace falta):

```env
DB_DATABASE=amantina_app
DB_USERNAME=postgres
DB_PASSWORD=tu_password_local
```

Para testing con PostgreSQL, la base debe ser distinta:

```env
DB_DATABASE=amantina_app_testing
DB_USERNAME=postgres
DB_PASSWORD=tu_password_local
```

> **Regla fuerte:** la base de testing (`amantina_app_testing`) debe ser DISTINTA de la de desarrollo (`amantina_app`).
>
> **Regla fuerte 2:** nunca usar SQLite para producción real.

---

## Configuración diaria por máquina

**Regla central:** el código viaja por Git; la configuración local de base de datos NO.

| Archivo | Se commitea | Rol |
|---------|-------------|-----|
| `.env` | No (`.gitignore`) | Configuración de desarrollo local |
| `.env.testing` | No (`.gitignore`) | Configuración de testing local |
| `*.example` | Sí | Plantillas para copiar a los archivos reales |

Cada computadora tiene sus propios `.env` y `.env.testing`. Así se puede trabajar con
PostgreSQL en casa y SQLite en el trabajo sin generar conflictos en commits.

### Casa/PostgreSQL

#### Aplicación (`.env`)

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=amantina_app
DB_USERNAME=postgres
DB_PASSWORD=tu_password_local
```

#### Testing (`.env.testing`)

```env
APP_ENV=testing
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=amantina_app_testing
DB_USERNAME=postgres
DB_PASSWORD=tu_password_local
SESSION_DRIVER=array
CACHE_STORE=array
QUEUE_CONNECTION=sync
MAIL_MAILER=array
```

### Trabajo/SQLite

#### Aplicación (`.env`)

```env
DB_CONNECTION=sqlite
```

Crear la base SQLite si no existe:

```powershell
New-Item -ItemType File -Path database/database.sqlite -Force
```

#### Testing (`.env.testing`)

```env
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=database/database_testing.sqlite
SESSION_DRIVER=array
CACHE_STORE=array
QUEUE_CONNECTION=sync
MAIL_MAILER=array
```

Crear la base de testing:

```powershell
New-Item -ItemType File -Path database/database_testing.sqlite -Force
```

### Qué hacer al cambiar de una máquina a otra

Cuando llegás a casa o al trabajo:

```powershell
git pull
php artisan config:clear
php artisan cache:clear
```

Reglas importantes:

- `.env` y `.env.testing` son **tuyos** — no se commitean.
- No cambies `phpunit.xml` para elegir PostgreSQL o SQLite.
- No commitees `.env`, `.env.testing` ni archivos `*.sqlite`.
- Sí commiteá las plantillas `.env.*.example` cuando la configuración cambie.

---

## Configuración local de desarrollo con SQLite

Para una instalación simple en una máquina sin PostgreSQL, `.env` puede apuntar a SQLite:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

Crear el archivo de base si no existe:

```powershell
New-Item -ItemType File -Path database/database.sqlite -Force
```

Verificar qué base está usando Laravel:

```powershell
php artisan config:clear
php artisan config:show database.default
php artisan config:show database.connections.sqlite.database
```

Resultado esperado:

```txt
database.default = sqlite
database.connections.sqlite.database = database/database.sqlite
```

---

## Recrear la aplicación desde cero

Este es el flujo usado y verificado el 2026-05-06:

```powershell
php artisan config:clear
php artisan cache:clear
php artisan migrate:fresh --force
php artisan db:seed --class=CompleteTestDataSeeder --force
```

Qué hace cada comando:

| Comando | Qué garantiza |
|---------|---------------|
| `config:clear` | Laravel vuelve a leer `.env`; evita usar una conexión cacheada. |
| `cache:clear` | Limpia cache de aplicación. |
| `migrate:fresh --force` | Borra todas las tablas y ejecuta todas las migraciones desde cero. |
| `db:seed --class=CompleteTestDataSeeder --force` | Carga configuración base, usuarios, estructura escolar y datos operativos. |

---

## Datos reales generados

En la corrida verificada del 2026-05-06, el seeder dejó estos datos:

| Dato | Cantidad verificada |
|------|---------------------|
| Institución | `Amantina de Sucre` |
| Usuarios totales | 625 |
| Años académicos | 1 |
| Lapsos | 3 |
| Grados | 5 |
| Secciones | 13 |
| Inscripciones | 321 |
| Jornadas de campo | 161 |
| Asistencias | 5810 |
| Actividades registradas | 24806 |

Algunos valores pueden variar en futuras corridas porque los seeders usan datos aleatorios para estudiantes, profesores, inscripciones y jornadas. Lo importante no es clavar el mismo número exacto: lo importante es que haya volumen suficiente para probar dashboards, listados, filtros y acumulados.

---

## Credenciales de prueba

Estas son las credenciales reales creadas por los seeders:

| Rol | Email | Password |
|-----|-------|----------|
| Admin | `admin@amantina.test` | `password` |
| Profesor test | `user90000001@amantina.test` | `password` |
| Representante test | `user90000010@amantina.test` | `password` |
| Alumno test | `user90000020@amantina.test` | `password` |

Rangos útiles:

| Tipo | Rango |
|------|-------|
| Profesores test | `user90000001@amantina.test` a `user90000005@amantina.test` |
| Representantes test | `user90000010@amantina.test` a `user90000011@amantina.test` |
| Alumnos test | `user90000020@amantina.test` a `user90000029@amantina.test` |

También se generan 500 alumnos demo y 25 profesores demo con emails aleatorios de Faker. Para esos usuarios conviene consultarlos desde la base o desde la UI.

---

## Verificación después del setup

Verificar conteos principales:

```powershell
php artisan config:clear
php artisan config:show database.default
```

Consultas esperadas, si usás una herramienta de base de datos:

```sql
select count(*) from users;
select count(*) from enrollments;
select count(*) from field_sessions;
select count(*) from attendances;
select count(*) from attendance_activities;
select id, name from institution limit 1;
```

Verificar que los tests pasan:

```powershell
php artisan config:clear
php artisan cache:clear
php artisan test --compact tests/Feature/ExampleTest.php
```

El comando es **el mismo** en cualquier máquina. Laravel auto-detecta `.env.testing`
porque `phpunit.xml` ya setea `APP_ENV=testing`.

Resultado esperado:

```txt
Tests: 1 passed (1 assertions)
```

---

## Testing sin conflictos entre casa y trabajo

Regla fundamental: **`php artisan test` es el mismo comando en cualquier máquina.**

Cada máquina tiene su propio `.env.testing` (gitignorado) con la conexión a base
de datos que corresponda. No hay más archivos `.env.testing.sqlite` ni
`.env.testing.postgres` — solo existe `.env.testing`, y su contenido varía según
la máquina.

### Resumen visual

| Situación | `.env` (app) | Copiado de | `.env.testing` (tests) | Copiado de |
|-----------|-------------|------------|------------------------|------------|
| Casa / PostgreSQL | `DB_CONNECTION=pgsql` | `.env.postgres.example` | `DB_CONNECTION=pgsql`, apunta a `amantina_app_testing` | `.env.postgres.testing.example` |
| Trabajo / SQLite | `DB_CONNECTION=sqlite` | `.env.sqlite.example` | `DB_CONNECTION=sqlite`, apunta a `database/database_testing.sqlite` | `.env.sqlite.testing.example` |

### Casa — PostgreSQL

```powershell
Copy-Item .env.postgres.testing.example .env.testing
# Editar DB_PASSWORD si es necesario
php artisan config:clear
php artisan cache:clear
php artisan test --compact tests/Feature/ExampleTest.php
```

### Trabajo — SQLite

```powershell
Copy-Item .env.sqlite.testing.example .env.testing
New-Item -ItemType File -Path database/database_testing.sqlite -Force
php artisan config:clear
php artisan cache:clear
php artisan test --compact tests/Feature/ExampleTest.php
```

No hay comandos diferentes entre máquinas. La diferencia está solo en el contenido
de `.env.testing`.

---

## Frontend y acceso a la app

Backend:

- Herd/Laragon sirve el proyecto localmente.
- No hace falta ejecutar comandos para levantar un servidor backend manual.

Frontend en desarrollo:

```powershell
npm run dev
```

Entrar con:

```txt
admin@amantina.test / password
```

Si la UI no refleja cambios frontend, revisar que `npm run dev` esté corriendo.

---

## Problemas comunes

### Laravel usa una base equivocada

Causa: configuración cacheada.

Solución:

```powershell
php artisan config:clear
php artisan cache:clear
php artisan config:show database.default
```

### `socket_create_listen()` no existe al correr tests

Causa: falta la extensión PHP `sockets` en CLI.

Solución: habilitar en el `php.ini` usado por CLI:

```ini
extension=sockets
```

Luego verificar:

```powershell
php -m | Select-String sockets
```

### El login falla con `admin@example.com`

Causa: esa credencial era vieja y ya no es válida.

Usar:

```txt
admin@amantina.test / password
```

### Hay error de constraint o datos duplicados

Causa: se corrió seed sobre una base con datos previos.

Solución:

```powershell
php artisan config:clear
php artisan cache:clear
php artisan migrate:fresh --force
php artisan db:seed --class=CompleteTestDataSeeder --force
```

---

## Checklist final

- [ ] `.env` existe con la configuración correcta para tu máquina (SQLite o PostgreSQL).
- [ ] `.env.testing` existe con la configuración correcta para testing.
- [ ] `database/database.sqlite` existe (si usás SQLite).
- [ ] `database/database_testing.sqlite` existe (si usás SQLite para testing).
- [ ] `php artisan migrate:fresh --force` termina sin errores.
- [ ] `php artisan db:seed --class=CompleteTestDataSeeder --force` termina sin errores.
- [ ] El admin real es `admin@amantina.test / password`.
- [ ] Hay jornadas, asistencias y actividades suficientes para dashboards vivos.
- [ ] `npm run dev` está corriendo si vas a trabajar frontend.
- [ ] Los tests pasan con `php artisan test --compact tests/Feature/ExampleTest.php`.

---

**Estado:** ✅ Actualizado con la nueva estructura de plantillas `.env.*` el 2026-05-07.

---

## Convenciones del Framework: por qué `.env` y `.env.testing` son los nombres correctos

Laravel está programado para buscar nombres de archivos específicos en momentos específicos. Entender esto evita dolores de cabeza con la configuración.

### 1. El estándar: `.env.testing`

Laravel tiene una integración nativa con este nombre de archivo. Cuando ejecutás `php artisan test`, el framework detecta automáticamente si existe un archivo llamado `.env.testing` en la raíz. Si lo encuentra:

- Lo carga **encima** del `.env` original.
- Sobrescribe cualquier variable que coincida con la de desarrollo.

Es una convención "mágica" porque no tenés que configurar nada extra para que funcione; el framework simplemente lo busca por nombre gracias a que `phpunit.xml` setea `APP_ENV=testing`.

### 2. ¿Qué pasa con nombres personalizados como `.env.pruebas` o `.env.testing.sqlite`?

Si creás un archivo llamado `.env.pruebas`, Laravel **lo ignorará por completo** de forma automática. Para el framework, es simplemente un archivo de texto plano sin relevancia.

Sí, se puede forzar su uso con `--env=pruebas` o `--env=testing.sqlite`, pero eso rompe la convención. Cada máquina terminaría usando un flag distinto y el comando `php artisan test` ya no sería universal.

### 3. El orden de prioridad (jerarquía)

Cuando lanzás un test, Laravel decide qué configuración usar siguiendo este orden (el de arriba gana al de abajo):

1. **Variables en `phpunit.xml`:** Lo que definas acá (en la sección `<php>`) tiene la máxima prioridad.
2. **`.env.testing`:** Se carga automáticamente cuando `APP_ENV=testing` (lo setea `phpunit.xml`).
3. **`.env`:** Se usa como base, pero todo lo de arriba lo sobrescribe.

### ¿Por qué seguir esta convención?

La razón principal es que **`php artisan test` debe ser el mismo comando en cualquier máquina**. Si usás `.env.testing` (canónico):

- En casa con PostgreSQL copiás `.env.postgres.testing.example` → `.env.testing`.
- En el trabajo con SQLite copiás `.env.sqlite.testing.example` → `.env.testing`.
- En ambos lados ejecutás **exactamente el mismo comando**: `php artisan test`.

No importa qué motor uses: el framework siempre va a encontrar `.env.testing` porque es el nombre que espera. Eso hace que el setup sea predecible y que los comandos en la documentación, en CI/CD y en los hooks de Git sean siempre idénticos.

**En resumen:** `.env` y `.env.testing` son los únicos archivos que Laravel busca por sí solo. Los templates como `.env.sqlite.example` existen para que copies el que necesites a `.env`, y los templates `.env.sqlite.testing.example` para que copies el que necesites a `.env.testing`. El framework siempre lee `.env` y `.env.testing` — el resto son solo plantillas.


