# Fresh Setup - Amantina App

Fuente de verdad para levantar una copia local del proyecto desde cero y cargar datos de ejemplo suficientes para ver la unidad educativa con dashboards vivos.

**Última verificación real:** 2026-05-06  
**Base usada en esta guía:** SQLite local de desarrollo (`database/database.sqlite`)  
**Seeder completo:** `CompleteTestDataSeeder`

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

## Plantillas `.env.example`

Los archivos `.env` reales no se commitean porque contienen configuración local y secretos. Lo que sí viaja por Git son sus plantillas `.example`.

| Configuración del proyecto | Plantilla | Copiar a | Cuándo usarla |
|----------------------------|-----------|----------|---------------|
| Trabajo/SQLite — aplicación | `.env.sqlite.example` | `.env` | Uso diario de la app con SQLite. |
| Trabajo/SQLite — testing | `.env.testing.sqlite.example` | `.env.testing.local` | Tests locales con SQLite. No se commitea el destino. |
| Casa/PostgreSQL — aplicación | `.env.postgres.example` | `.env` | Uso diario de la app con PostgreSQL. |
| Casa/PostgreSQL — testing | `.env.testing.postgres.example` | `.env.testing` | Tests con PostgreSQL separado. Normalmente ya existe versionado. |
| Producción real | `.env.production.example` | `.env.production` o `.env` del servidor | Servidor real con PostgreSQL y secretos reales. |
| Instalación simple | `.env.example` | `.env` | Alias de setup local simple con SQLite. |

### Trabajo/SQLite — aplicación

```powershell
Copy-Item .env.sqlite.example .env
New-Item -ItemType File -Path database/database.sqlite -Force
php artisan key:generate
```

### Casa/PostgreSQL — aplicación

```powershell
Copy-Item .env.postgres.example .env
php artisan key:generate
```

Después editá en `.env`:

```env
DB_DATABASE=amantina_app
DB_USERNAME=postgres
DB_PASSWORD=tu_password_local
```

### Trabajo/SQLite — testing

```powershell
Copy-Item .env.testing.sqlite.example .env.testing.local
New-Item -ItemType File -Path database/database_testing.sqlite -Force
```

Luego corré:

```powershell
php artisan config:clear
php artisan cache:clear
php artisan test --env=testing.local --compact tests/Feature/ExampleTest.php
```

### Casa/PostgreSQL — testing

El proyecto ya trae `.env.testing` versionado para la configuración casa/PostgreSQL. Si necesitás recrearlo:

```powershell
Copy-Item .env.testing.postgres.example .env.testing
```

Después ajustá `DB_PASSWORD` según tu PostgreSQL local.

### Producción

Usá `.env.production.example` como plantilla, pero NO la copies ciegamente sin revisar. Producción debe completar:

- `APP_URL`
- `APP_KEY`
- credenciales PostgreSQL
- mail real
- cualquier storage externo si aplica

Regla fuerte: **nunca usar SQLite para producción real**.

---

## Configuración diaria por máquina

**Regla central:** el código viaja por Git; la configuración local de base de datos NO.

Cada computadora debe tener su propio `.env`, porque `.env` está ignorado por Git. Eso permite trabajar con PostgreSQL en casa, SQLite en el trabajo, o cualquier otra combinación local sin generar conflictos en commits.

| Configuración del proyecto | Archivo real | Se commitea | Base recomendada |
|----------------------------|--------------|-------------|------------------|
| Casa/PostgreSQL — aplicación | `.env` | No | PostgreSQL local (`amantina_app`) |
| Casa/PostgreSQL — testing | `.env.testing` | Sí | PostgreSQL testing (`amantina_app_testing`) |
| Trabajo/SQLite — aplicación | `.env` | No | SQLite local (`database/database.sqlite`) |
| Trabajo/SQLite — testing | `.env.testing.local` | No | SQLite testing (`database/database_testing.sqlite`) |

### Casa/PostgreSQL — aplicación

Ejemplo de `.env` local:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=amantina_app
DB_USERNAME=postgres
DB_PASSWORD=tu_password_local
```

### Trabajo/SQLite — aplicación

Ejemplo de `.env` local:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

Crear la base SQLite si no existe:

```powershell
New-Item -ItemType File -Path database/database.sqlite -Force
```

### Qué hacer al cambiar de una máquina a otra

Cuando llegás a casa o al trabajo:

```powershell
git pull
php artisan config:clear
php artisan cache:clear
```

No cambies archivos versionados solo para adaptar la base local. En particular:

- No cambies `phpunit.xml` para elegir PostgreSQL o SQLite.
- No cambies `.env.testing` para una necesidad puntual de una máquina.
- No commitees `.env`, `.env.testing.local` ni archivos `*.sqlite`.
- Sí commiteá plantillas `.env*.example` cuando el setup real cambie.

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
| Profesor test | `user90000002@amantina.test` | `password` |
| Representante test | `user90000010@amantina.test` | `password` |
| Alumno test | `user90000020@amantina.test` | `password` |
| Alumno test | `user90000021@amantina.test` | `password` |

Rangos útiles:

| Tipo | Rango |
|------|-------|
| Profesores test | `user90000001@amantina.test` a `user90000005@amantina.test` |
| Representantes test | `user90000010@amantina.test` a `user90000018@amantina.test` |
| Alumnos test | `user90000020@amantina.test` a `user90000104@amantina.test` |

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

Verificar test mínimo con el entorno local SQLite de testing:

```powershell
php artisan config:clear
php artisan cache:clear
php artisan test --env=testing.local --compact tests/Feature/ExampleTest.php
```

Resultado esperado:

```txt
Tests: 1 passed (1 assertions)
```

---

## Testing sin conflictos entre casa y trabajo

Regla de arquitectura: **no commitear configuración de base de datos que dependa de una máquina**.

| Configuración del proyecto | Archivo real | Se commitea | Uso |
|----------------------------|--------------|-------------|-----|
| Casa/PostgreSQL — aplicación | `.env` | No | App diaria con PostgreSQL local. |
| Casa/PostgreSQL — testing | `.env.testing` | Sí | Tests con PostgreSQL separado: `amantina_app_testing`. |
| Trabajo/SQLite — aplicación | `.env` | No | App diaria con SQLite local. |
| Trabajo/SQLite — testing | `.env.testing.local` | No | Tests con SQLite local: `database/database_testing.sqlite`. |

Para la configuración Trabajo/SQLite — testing, crear `.env.testing.local`:

```env
APP_ENV=testing
APP_DEBUG=true
APP_URL=http://amantina-app.test

DB_CONNECTION=sqlite
DB_DATABASE=database/database_testing.sqlite

SESSION_DRIVER=array
CACHE_STORE=array
QUEUE_CONNECTION=sync
MAIL_MAILER=array
BROADCAST_CONNECTION=null
FILESYSTEM_DISK=local
```

Crear la base de Trabajo/SQLite — testing:

```powershell
New-Item -ItemType File -Path database/database_testing.sqlite -Force
```

Correr Trabajo/SQLite — testing:

```powershell
php artisan config:clear
php artisan cache:clear
php artisan test --env=testing.local --compact tests/Feature/ExampleTest.php
```

En Casa/PostgreSQL — testing, usar:

```powershell
php artisan config:clear
php artisan cache:clear
php artisan test --env=testing --compact tests/Feature/ExampleTest.php
```

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

- [ ] `.env` apunta a `DB_CONNECTION=sqlite` para setup local simple.
- [ ] Existe `database/database.sqlite`.
- [ ] `php artisan migrate:fresh --force` termina sin errores.
- [ ] `php artisan db:seed --class=CompleteTestDataSeeder --force` termina sin errores.
- [ ] El admin real es `admin@amantina.test / password`.
- [ ] Hay jornadas, asistencias y actividades suficientes para dashboards vivos.
- [ ] `npm run dev` está corriendo si vas a trabajar frontend.

---

**Estado:** ✅ Probado y validado con `CompleteTestDataSeeder` el 2026-05-06.

