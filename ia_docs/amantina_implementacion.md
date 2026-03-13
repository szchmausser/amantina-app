# Amantina App

## Guía de Implementación por Hitos

**Sistema Bitácora Socioproductiva**

Cada hito entrega una funcionalidad completa de punta a punta.
Los pasos son reproducibles y sirven como referencia para nuevos desarrolladores.
Stack: Laravel 12 · React 19 · TypeScript · Inertia.js · PostgreSQL · Spatie

### Convenciones de este documento

Cada hito está organizado de la misma forma para facilitar la lectura y la replicación:

| Elemento              | Qué indica                                           |
| --------------------- | ---------------------------------------------------- |
| Contexto              | Por qué existe este hito y qué problema resuelve     |
| Prerrequisitos        | Qué debe estar hecho antes de empezar                |
| Archivos involucrados | Lista de archivos a crear o modificar                |
| Pasos numerados       | Secuencia exacta de acciones a ejecutar              |
| Bloques de código     | Comandos o código listo para copiar y ejecutar       |
| ADVERTENCIA           | Errores conocidos o trampas comunes a evitar         |
| NOTA                  | Información complementaria relevante                 |
| ENTREGA               | Lo que debe existir y funcionar al finalizar el hito |

> **NOTA:** Los bloques de código asumen que el directorio de trabajo es la raíz del proyecto (`amantina-app/`) salvo que se indique explícitamente lo contrario.

---

# Plan de Desarrollo

El desarrollo se organiza en hitos verticales. Cada hito entrega una funcionalidad completamente operativa de punta a punta: migracion, modelo, validaciones, seeders, factories, controlador y UI. No se crean todas las migraciones al inicio. El principio es que al finalizar cada hito el sistema tenga algo nuevo y funcional que se pueda usar, probar y validar antes de continuar.

| Prerequisito antes del Hito 0: PHP 8.2+, Composer, Node.js 20+, npm y PostgreSQL instalados en el entorno de desarrollo. |
| ------------------------------------------------------------------------------------------------------------------------ |

## Resumen de Hitos

| Hito | Título                       | Enfoque Principal                         |
| ---- | ---------------------------- | ----------------------------------------- |
| 0    | Instalacion y esqueleto base | Setup inicial, auth base, Spatie          |
| 1    | Usuarios: base del sistema   | Tabla users definitiva y seeder admin     |
| 2    | Roles y permisos             | RBAC con Spatie Permissions               |
| 3    | Autenticacion personalizada  | Login con contexto y multi-rol            |
| 4    | CRUD de usuarios             | Gestión integral y perfil de usuario      |
| 5    | Estructura academica         | Años, lapsos, grados y secciones          |
| 6    | Inscripciones y asignaciones | Vínculo alumno/sección y profesor/sección |
| 7    | Representantes               | Vínculo representante/estudiante          |
| 8    | Informacion de salud         | Condiciones médicas y soportes            |
| 9    | Catalogos de configuracion   | Actividades y ubicaciones                 |
| 10   | Jornadas de campo            | Registro central de actividades           |
| 11   | Asistencia y subactividades  | Acreditación de horas y evidencias        |
| 12   | Horas externas               | Acreditación para transferidos            |
| 13   | Acumulados y dashboards      | Progreso visual y KPIs                    |
| 14   | Reportes en PDF              | Generación de certificados y listados     |
| 15   | Revision y estabilizacion    | QA final y seeders demo                   |

> [!NOTE]
> Este plan dicta la planificación general del proyecto. Los detalles de cada hito se desarrollan en las secciones siguientes.

---

### Hito 0 — Instalación y esqueleto base

Punto de partida del proyecto. Todo lo que se instala aquí es prerrequisito para todos los hitos siguientes. Al finalizar este hito el proyecto debe estar corriendo en el navegador con el login del starter kit operativo y PostgreSQL conectado.

#### Prerrequisitos del entorno

Verificar que el entorno de desarrollo cuenta con las siguientes herramientas antes de ejecutar cualquier comando:

| Herramienta | Versión mínima | Verificar con    |
| ----------- | -------------- | ---------------- |
| PHP         | 8.2            | `php -v`         |
| Composer    | 2.x            | `composer -V`    |
| Node.js     | 20.x           | `node -v`        |
| npm         | 10.x           | `npm -v`         |
| PostgreSQL  | 15.x           | `psql --version` |
| Git         | cualquiera     | `git --version`  |

#### Archivos involucrados

- `.env` (creado desde `.env.example`)
- `composer.json` (modificado al instalar paquetes Spatie)
- `config/permission.php` (publicado por Spatie Permissions)
- `database/migrations/` (migraciones de Spatie publicadas aquí)

#### Paso 1 — Clonar el proyecto

```bash
laravel new amantina-app --react -n
cd amantina-app
```

#### Paso 2 — Instalar dependencias

```bash
# Dependencias PHP
composer install

# Dependencias JavaScript
npm install
```

#### Paso 3 — Configurar el entorno

```bash
cp .env.example .env
php artisan key:generate
```

Editar el archivo `.env` con las credenciales de PostgreSQL:

```env
APP_NAME="Amantina App"
APP_URL=http://amantina-app.test

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=amantina_app
DB_USERNAME=tu_usuario_postgres
DB_PASSWORD=tu_contrasena_postgres
```

#### Paso 4 — Crear la base de datos en PostgreSQL

```bash
# Desde la consola de psql
CREATE DATABASE amantina_app;

# Verificar que existe
\l
```

#### Paso 5 — Instalar Spatie Laravel Permissions

```bash
composer require spatie/laravel-permission

php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

Add the necessary trait to your User model:

```php
class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;
}
```

> **NOTA:** Esto crea `config/permission.php` y una migración que generará las tablas: `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`.

#### Paso 6 — Instalar Spatie Laravel Media Library

```bash
composer require spatie/laravel-medialibrary

php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="migrations"
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="config"
```

Preparing your model

```php
class User extends Authenticatable implements HasMedia
{
    use HasFactory, HasRoles, InteractsWithMedia, Notifiable, TwoFactorAuthenticatable;
}
```

> **NOTA:** Esto crea una migración que generará la tabla `media`: la tabla polifórmica centralizada para todos los archivos adjuntos del sistema (fotos de perfil, evidencias, soportes médicos, documentos de horas externas).

#### Paso 7 — Ejecutar migraciones iniciales

```bash
php artisan migrate
```

Se crean en este punto: las tablas del starter kit (`users`, `password_reset_tokens`, `sessions`, `cache`, `jobs`) y las tablas de Spatie (`roles`, `permissions`, `media` y las tablas de relación intermedias).

#### Paso 8 — Verificar que el proyecto levanta

```bash
# Terminal 1: servidor PHP
php artisan serve

# Terminal 2: compilación de assets (mantener corriendo)
npm run dev
```

Abrir `http://amantina-app.test` en el navegador. Debe mostrarse la pantalla de bienvenida del starter kit con los enlaces de Login y Register visibles.

> **ENTREGA:** Proyecto corriendo en el navegador. Login del starter kit operativo. PostgreSQL conectado y migrado. Spatie Permissions y Media Library instalados.

---

### Hito 1 — Usuarios y Datos Institucionales (Base del Sistema)

La tabla `users` del starter kit tiene solo los campos mínimos para autenticación básica. Este hito la extiende con todos los campos que requiere el sistema y ajusta el modelo Eloquent, el factory y el seeder. Además, se introduce la entidad fundamental **`Institution`** (Datos Institucionales), que servirá para dotar al sistema de identidad local y permitirá automatizar reglas de negocio como la asignación de la "institución de origen" a los nuevos alumnos nativos.

Los conceptos de roles y permisos no existen todavía en este hito (eso se resuelve en el Hito 2). Aquí solo se define la estructura base de los usuarios y de la sede institucional.

#### Prerrequisitos

Hito 0 completado: proyecto corriendo, PostgreSQL conectado, Spatie instalado.

#### Archivos involucrados

| Archivo                                                              | Acción    | Motivo                                                         |
| -------------------------------------------------------------------- | --------- | -------------------------------------------------------------- |
| `database/migrations/2026_03_13_143000_create_institution_table.php` | Crear     | Tabla base para los datos fijos del plantel                    |
| `app/Models/Institution.php`                                         | Crear     | Modelo asociado a Datos Institucionales                        |
| `database/seeders/InstitutionSeeder.php`                             | Crear     | Primer registro (Amantina de Sucre)                            |
| `app/Http/Controllers/Settings/InstitutionController.php`            | Crear     | Controlador para manejar actualización en UI                   |
| `resources/js/pages/settings/institution.tsx`                        | Crear     | Componente React para CRUD de Institución                      |
| `routes/settings.php`                                                | Modificar | Rutas para el controlador InstitutionController                |
| `resources/js/layouts/settings/layout.tsx`                           | Modificar | Añadir menú lateral para "Datos Institucionales"               |
| `database/migrations/2026...add_new_fields_to_users_table.php`       | Crear     | Expansión de campos en tabla `users` (`cedula`, `phone`, etc.) |
| `database/migrations/2026...add_address_to_users_table.php`          | Crear     | Expansión de campos en tabla `users` (`address`)               |
| `app/Models/User.php`                                                | Modificar | `$fillable`, `casts`, y `SoftDeletes`                          |
| `database/factories/UserFactory.php`                                 | Modificar | Campos obligatorios y valores por defecto                      |
| `database/seeders/UserSeeder.php`                                    | Crear     | Administrador raíz del sistema                                 |
| `database/seeders/DatabaseSeeder.php`                                | Modificar | Ejecutar InstitutionSeeder y UserSeeder                        |
| `app/Concerns/ProfileValidationRules.php`                            | Modificar | Reglas de validación y unicidad de cédula                      |
| `app/Actions/Fortify/CreateNewUser.php`                              | Modificar | Persistencia del usuario e institución origen nativa           |
| `resources/js/pages/auth/register.tsx`                               | Modificar | Inclusión visual de campos base y condicionales                |
| `tests/Feature/Auth/RegistrationTest.php`                            | Modificar | Pruebas unificadas de usuario e institución                    |
| `tests/Feature/Settings/InstitutionTest.php`                         | Crear     | Pruebas de CRUD de la entidad Institution                      |

---

#### 1. Implementación de Datos Institucionales (Institution)

El sistema ya no depende de opciones estáticas, sino que su configuración global parte de la tabla `institution`. Esta tabla actúa en singular dado que el software gestionará un solo plantel.

##### Paso 1.1 — Migración y Modelo de Institution

Se debe crear la tabla con los campos de contacto y organizativos.

```bash
php artisan make:model Institution -m
```

Reemplazar la migración generada con:

```php
public function up(): void
{
    Schema::create('institution', function (Blueprint $row) {
        $row->id();
        $row->string('name');
        $row->text('address')->nullable();
        $row->string('email')->nullable();
        $row->string('phone')->nullable();
        $row->string('code')->nullable();
        $row->timestamps();
    });
}
```

En el modelo `app/Models/Institution.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
    protected $table = 'institution';

    protected $fillable = [
        'name',
        'address',
        'email',
        'phone',
        'code',
    ];
}
```

##### Paso 1.2 — Seeder Base Institucional

El sistema no puede levantar sin su identidad principal. Creamos su seeder.

```bash
php artisan make:seeder InstitutionSeeder
```

En `database/seeders/InstitutionSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Institution;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    public function run(): void
    {
        Institution::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Amantina de Sucre',
                'address' => 'Dirección de la institución',
                'email' => 'contacto@amantina.edu',
                'phone' => '04120000000',
                'code' => 'AM-001',
            ]
        );
    }
}
```

Agregarlo al inicio de `database/seeders/DatabaseSeeder.php` antes que `UserSeeder`:

```php
$this->call([
    InstitutionSeeder::class,
    UserSeeder::class,
]);
```

##### Paso 1.3 — Controlador

Este controlador gestionará los datos en la interfaz.

```bash
php artisan make:controller Settings/InstitutionController
```

En `app/Http/Controllers/Settings/InstitutionController.php`:

```php
<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/institution', [
            'institution' => Institution::first(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'code' => ['nullable', 'string', 'max:50'],
        ]);

        $institution = Institution::first() ?? new Institution();
        $institution->fill($validated);
        $institution->save();

        return back();
    }
}
```

##### Paso 1.4 — Rutas y Menú Lateral (UI)

Registrar las rutas en `routes/settings.php` dentro del grupo `auth` y `verified`:

```php
    Route::get('settings/institution', [InstitutionController::class, 'edit'])->name('institution.edit');
    Route::patch('settings/institution', [InstitutionController::class, 'update'])->name('institution.update');
```

En `resources/js/layouts/settings/layout.tsx`, este es el código completo con la navegación actualizada:

```tsx
import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import { edit as editInstitution } from '@/routes/institution';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: edit(),
        icon: null,
    },
    {
        title: 'Security',
        href: editSecurity(),
        icon: null,
    },
    {
        title: 'Appearance',
        href: editAppearance(),
        icon: null,
    },
    {
        title: 'Datos Institucionales',
        href: editInstitution().url,
        icon: null,
    },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    // When server-side rendering, we only render the layout on the client...
    if (typeof window === 'undefined') {
        return null;
    }

    return (
        <div className="px-4 py-6">
            <Heading
                title="Settings"
                description="Manage your profile and account settings"
            />

            <div className="flex flex-col lg:flex-row lg:space-x-12">
                <aside className="w-full max-w-xl lg:w-48">
                    <nav
                        className="flex flex-col space-x-0 space-y-1"
                        aria-label="Settings"
                    >
                        {sidebarNavItems.map((item, index) => (
                            <Button
                                key={`${toUrl(item.href)}-${index}`}
                                size="sm"
                                variant="ghost"
                                asChild
                                className={cn('w-full justify-start', {
                                    'bg-muted': isCurrentOrParentUrl(item.href),
                                })}
                            >
                                <Link href={item.href}>
                                    {item.icon && (
                                        <item.icon className="h-4 w-4" />
                                    )}
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div className="flex-1 md:max-w-2xl">
                    <section className="max-w-xl space-y-12">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
```

> **ADVERTENCIA:** Como lección aprendida de arquitectura de permisos, este menú es temporalmente visible para cualquier usuario autenticado en el Hito 1. Su control estricto de visibilidad (RBAC) debe restringirse a roles de administrador en el **Hito 2**.

##### Paso 1.5 — Componente React para Institution

Crear `resources/js/pages/settings/institution.tsx`. A continuación el código completo del componente que gestiona los datos interactivos:

```tsx
import { Transition } from '@headlessui/react';
import { Form, Head } from '@inertiajs/react';
import {
    Check,
    Mail,
    MapPin,
    Phone,
    Building2,
    Fingerprint,
} from 'lucide-react';
import InstitutionController from '@/actions/App/Http/Controllers/Settings/InstitutionController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit } from '@/routes/institution';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Datos institucionales',
        href: edit().url,
    },
];

interface InstitutionProps {
    institution: {
        name: string;
        address: string | null;
        email: string | null;
        phone: string | null;
        code: string | null;
    } | null;
}

export default function InstitutionSettings({ institution }: InstitutionProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Datos institucionales" />

            <h1 className="sr-only">Datos institucionales</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Información de la Institución"
                        description="Gestiona los datos de contacto y parámetros generales de la institución"
                    />

                    <Form
                        {...InstitutionController.update.form()}
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <>
                                <div className="grid gap-6 md:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="name"
                                            className="flex items-center gap-2"
                                        >
                                            <Building2 className="h-4 w-4" />
                                            Nombre de la Institución
                                        </Label>
                                        <Input
                                            id="name"
                                            className="mt-1 block w-full"
                                            defaultValue={institution?.name}
                                            name="name"
                                            required
                                            placeholder="Ej: Amanita de Sucre"
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="code"
                                            className="flex items-center gap-2"
                                        >
                                            <Fingerprint className="h-4 w-4" />
                                            Código Institucional
                                        </Label>
                                        <Input
                                            id="code"
                                            className="mt-1 block w-full"
                                            defaultValue={institution?.code}
                                            name="code"
                                            placeholder="Ej: AM-001"
                                        />
                                        <InputError message={errors.code} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="email"
                                            className="flex items-center gap-2"
                                        >
                                            <Mail className="h-4 w-4" />
                                            Correo Electrónico
                                        </Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            className="mt-1 block w-full"
                                            defaultValue={institution?.email}
                                            name="email"
                                            placeholder="contacto@institucion.com"
                                        />
                                        <InputError message={errors.email} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="phone"
                                            className="flex items-center gap-2"
                                        >
                                            <Phone className="h-4 w-4" />
                                            Teléfono de Contacto
                                        </Label>
                                        <Input
                                            id="phone"
                                            className="mt-1 block w-full"
                                            defaultValue={institution?.phone}
                                            name="phone"
                                            placeholder="0412-0000000"
                                        />
                                        <InputError message={errors.phone} />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="address"
                                        className="flex items-center gap-2"
                                    >
                                        <MapPin className="h-4 w-4" />
                                        Dirección
                                    </Label>
                                    <Input
                                        id="address"
                                        className="mt-1 block w-full"
                                        defaultValue={institution?.address}
                                        name="address"
                                        placeholder="Dirección física completa"
                                    />
                                    <InputError message={errors.address} />
                                </div>

                                <div className="flex items-center gap-4">
                                    <Button disabled={processing}>
                                        Guardar cambios
                                    </Button>

                                    <Transition
                                        show={recentlySuccessful}
                                        enter="transition ease-in-out"
                                        enterFrom="opacity-0"
                                        leave="transition ease-in-out"
                                        leaveTo="opacity-0"
                                    >
                                        <p className="text-muted-foreground flex items-center gap-1.5 text-sm">
                                            <Check className="h-4 w-4" />
                                            Guardado correctamente
                                        </p>
                                    </Transition>
                                </div>
                            </>
                        )}
                    </Form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
```

---

#### 2. Extensión de Usuarios

La tabla del starter kit necesita los campos obligatorios para el esquema académico y logístico.

##### Paso 2.1 — Migraciones a `users`

Creamos las migraciones necesarias para extender la tabla `users` (`cedula`, `phone`, `is_active`, `is_transfer`, `institution_origin`, `softDeletes` y en un bloque aparte `address`).

```bash
php artisan make:migration add_new_fields_to_users_table
php artisan make:migration add_address_to_users_table
```

En la migración `..._add_new_fields_to_users_table.php`:

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('cedula', 20)->unique();
        $table->string('phone', 20)->nullable();
        $table->boolean('is_active')->default(true);
        $table->boolean('is_transfer')->nullable();
        $table->string('institution_origin')->nullable();
        $table->softDeletes();
    });
}
```

En la migración subsecuente `..._add_address_to_users_table.php`:

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('address')->nullable();
    });
}
```

##### Paso 2.2 — Actualizar el Modelo User

Ajustamos `app/Models/User.php`.
Es crucial incluir el trait `SoftDeletes` y definir los mapeos correspondientes de atributos de forma estricta.

```php
// Traits
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements HasMedia
{
    use HasFactory, HasRoles, InteractsWithMedia, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'cedula',
        'phone',
        'address',
        'is_active',
        'is_transfer',
        'institution_origin',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_active' => 'boolean',
            'is_transfer' => 'boolean',
        ];
    }
}
```

> **NOTA DE APRENDIZAJE:** Destaca la adición de `SoftDeletes`. A futuro las eliminaciones en el panel no destruyen los registros relacionales del sistema sino que actúan bajo papelera reciclada, brindando integridad referencial pasiva. Por esta razón era vital agregar el trait.

##### Paso 2.3 — UserFactory y UserSeeder

Ajustamos `database/factories/UserFactory.php` para llenar los datos completos durante los tests:

```php
public function definition(): array
{
    return [
        'cedula' => fake()->unique()->numerify('########'),
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'email_verified_at' => now(),
        'password' => static::$password ??= Hash::make('password'),
        'phone' => fake()->phoneNumber(),
        'address' => fake()->address(),
        'is_active' => true,
        'is_transfer' => false,
        'institution_origin' => null,
        'remember_token' => Str::random(10),
        'two_factor_secret' => null,
        'two_factor_recovery_codes' => null,
        'two_factor_confirmed_at' => null,
    ];
}
```

Creamos `database/seeders/UserSeeder.php`:

```bash
php artisan make:seeder UserSeeder
```

```php
public function run(): void
{
    User::create([
        'cedula' => '00000000',
        'name' => 'Administrador',
        'email' => 'admin@amantina.test',
        'password' => 'password',
        'phone' => '04121234567',
        'address' => null,
        'is_active' => true,
        'is_transfer' => false,
        'institution_origin' => null,
    ]);
}
```

_(Nota: Puesto que el modelo establece el cast de `'password' => 'hashed'`, el texto plano `password` se asigna directamente y Eloquent lo cifrará.)_

##### Paso 2.4 — Validaciones de Perfil y Registro de Usuarios

Para que el registro demande los nuevos datos, modificamos el trait en `app/Concerns/ProfileValidationRules.php`. Aquí especificamos restricciones complejas y condicionales:

```php
protected function profileRules(?int $userId = null, bool $requireContactInfo = true): array
{
    return [
        'cedula' => [
            'required', 'string', 'max:20', Rule::unique('users', 'cedula')->ignore($userId),
        ],
        'name' => ['required', 'string', 'max:255'],
        'email' => [
            'required', 'string', 'email', 'max:255',
            $userId === null ? Rule::unique(User::class) : Rule::unique(User::class)->ignore($userId),
        ],
        'phone' => [$requireContactInfo ? 'required' : 'nullable', 'string', 'max:20'],
        'address' => [$requireContactInfo ? 'required' : 'nullable', 'string', 'max:500'],
        'is_transfer' => ['nullable', 'boolean'],
        'institution_origin' => [
            $requireContactInfo ? 'required_if:is_transfer,true' : 'nullable',
            'nullable', 'string', 'max:255',
        ],
    ];
}
```

##### Paso 2.5 — Interceptar la Creación del Usurio (Fortify)

La regla de negocio de referenciación a la Institución Base se consolida en `app/Actions/Fortify/CreateNewUser.php`. Esta acción es el enganche donde ambos modelos (User e Institution) interactúan.

```php
public function create(array $input): User
{
    Validator::make($input, [
        ...$this->profileRules(userId: null, requireContactInfo: true),
        'password' => $this->passwordRules(),
    ])->validate();

    return User::create([
        'cedula' => $input['cedula'],
        'name' => $input['name'],
        'email' => $input['email'],
        'password' => $input['password'],
        'phone' => $input['phone'],
        'address' => $input['address'],
        'is_transfer' => $input['is_transfer'] ?? false,
        'institution_origin' => ($input['is_transfer'] ?? false)
                                    ? ($input['institution_origin'] ?? null)
                                    : Institution::first()?->name,
        'is_active' => true,
    ]);
}
```

##### Paso 2.6 — Formulario de Frontend (React)

En `resources/js/pages/auth/register.tsx`, se añaden todos los inputs definidos y se captura de forma reactiva el estado de institución de origen. A continuación el código fuente íntegro:

```tsx
import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import { login } from '@/routes';
import { store } from '@/routes/register';

export default function Register() {
    const [isTransfer, setIsTransfer] = useState(false);

    return (
        <AuthLayout
            title="Crear cuenta"
            description="Ingresa tus datos para registrarte en el sistema"
        >
            <Head title="Registro" />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            {/* Cédula */}
                            <div className="grid gap-2">
                                <Label htmlFor="cedula">Cédula</Label>
                                <Input
                                    id="cedula"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    name="cedula"
                                    placeholder="Número de cédula"
                                />
                                <InputError message={errors.cedula} />
                            </div>

                            {/* Nombre */}
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nombre completo</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    required
                                    tabIndex={2}
                                    autoComplete="name"
                                    name="name"
                                    placeholder="Nombre completo"
                                />
                                <InputError message={errors.name} />
                            </div>

                            {/* Correo */}
                            <div className="grid gap-2">
                                <Label htmlFor="email">
                                    Correo electrónico
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    tabIndex={3}
                                    autoComplete="email"
                                    name="email"
                                    placeholder="correo@ejemplo.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            {/* Teléfono */}
                            <div className="grid gap-2">
                                <Label htmlFor="phone">Teléfono</Label>
                                <Input
                                    id="phone"
                                    type="tel"
                                    required
                                    tabIndex={4}
                                    name="phone"
                                    placeholder="Número de teléfono"
                                />
                                <InputError message={errors.phone} />
                            </div>

                            {/* Dirección */}
                            <div className="grid gap-2">
                                <Label htmlFor="address">
                                    Dirección de residencia
                                </Label>
                                <Input
                                    id="address"
                                    type="text"
                                    required
                                    tabIndex={5}
                                    name="address"
                                    placeholder="Dirección completa"
                                />
                                <InputError message={errors.address} />
                            </div>

                            {/* ¿Eres transferido? */}
                            <div className="grid gap-2">
                                <Label>
                                    ¿Eres estudiante transferido de otra
                                    institución?
                                </Label>
                                <div className="flex gap-6">
                                    <label className="flex cursor-pointer items-center gap-2">
                                        <input
                                            type="radio"
                                            name="is_transfer"
                                            value="0"
                                            defaultChecked
                                            tabIndex={6}
                                            onChange={() =>
                                                setIsTransfer(false)
                                            }
                                        />
                                        No
                                    </label>
                                    <label className="flex cursor-pointer items-center gap-2">
                                        <input
                                            type="radio"
                                            name="is_transfer"
                                            value="1"
                                            tabIndex={7}
                                            onChange={() => setIsTransfer(true)}
                                        />
                                        Sí
                                    </label>
                                </div>
                                <InputError message={errors.is_transfer} />
                            </div>

                            {/* Institución de origen — solo si es transferido */}
                            {isTransfer && (
                                <div className="grid gap-2">
                                    <Label htmlFor="institution_origin">
                                        Institución de origen
                                    </Label>
                                    <Input
                                        id="institution_origin"
                                        type="text"
                                        required
                                        tabIndex={8}
                                        name="institution_origin"
                                        placeholder="Nombre de la institución anterior"
                                    />
                                    <InputError
                                        message={errors.institution_origin}
                                    />
                                </div>
                            )}

                            {/* Contraseña */}
                            <div className="grid gap-2">
                                <Label htmlFor="password">Contraseña</Label>
                                <PasswordInput
                                    id="password"
                                    required
                                    tabIndex={9}
                                    autoComplete="new-password"
                                    name="password"
                                    placeholder="Contraseña"
                                />
                                <InputError message={errors.password} />
                            </div>

                            {/* Confirmar contraseña */}
                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Confirmar contraseña
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    required
                                    tabIndex={10}
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    placeholder="Confirmar contraseña"
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 w-full"
                                tabIndex={11}
                                data-test="register-user-button"
                            >
                                {processing && <Spinner />}
                                Crear cuenta
                            </Button>
                        </div>

                        <div className="text-muted-foreground text-center text-sm">
                            ¿Ya tienes cuenta?{' '}
                            <TextLink href={login()} tabIndex={12}>
                                Iniciar sesión
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
```

---

#### 3. Pruebas y Validación Final

Creamos y corremos test unificados que demuestran que el flujo completo no posee fallas, especialmente sobre lógica customizada y reactiva del sistema.

**`tests/Feature/Auth/RegistrationTest.php`**

```php
<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyFeature(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $this->withoutVite();
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        // Asegurarnos de que la institución existe para el test
        $this->seed(\Database\Seeders\InstitutionSeeder::class);

        $response = $this->post(route('register.store'), [
            'cedula' => '12345678',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '04121234567',
            'address' => 'Test Address',
            'is_transfer' => '0',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'cedula' => '12345678',
            'is_active' => true,
            'is_transfer' => false,
            'institution_origin' => 'Amantina de Sucre', // Verificamos auto-asignación desde Institution
        ]);
    }

    public function test_registration_requires_mandatory_fields()
    {
        $response = $this->post(route('register.store'), []);

        $response->assertSessionHasErrors(['cedula', 'name', 'email', 'phone', 'address', 'password']);
    }

    public function test_registration_as_transfer_requires_institution_origin()
    {
        $response = $this->post(route('register.store'), [
            'cedula' => '12345678',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '04121234567',
            'address' => 'Test Address',
            'is_transfer' => '1',
            'institution_origin' => '', // Vacío siendo transferido
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['institution_origin']);
    }

    public function test_registration_as_transfer_with_institution_origin_success()
    {
        $response = $this->post(route('register.store'), [
            'cedula' => '12345678',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '04121234567',
            'address' => 'Test Address',
            'is_transfer' => '1',
            'institution_origin' => 'Otra Institución',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'is_transfer' => true,
            'institution_origin' => 'Otra Institución',
        ]);
    }

    public function test_new_user_has_student_role_incomplete()
    {
        $this->markTestIncomplete('La asignación del rol student se abordará en el Hito 2.');
    }
}

```

**`tests/Feature/Settings/InstitutionTest.php`**

```php
<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_institution_settings_page_can_be_rendered()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('institution.edit'));

        $response->assertOk();
    }

    public function test_institution_settings_can_be_updated()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('institution.update'), [
            'name' => 'New Institution Name',
            'address' => 'New Address',
            'email' => 'new@institution.com',
            'phone' => '04128888888',
            'code' => 'NEW-001',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $institution = Institution::first();
        $this->assertEquals('New Institution Name', $institution->name);
        $this->assertEquals('New Address', $institution->address);
        $this->assertEquals('new@institution.com', $institution->email);
        $this->assertEquals('04128888888', $institution->phone);
        $this->assertEquals('NEW-001', $institution->code);
    }

    public function test_institution_settings_requires_name()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('institution.update'), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors(['name']);
    }
}

```

#### 4. Preparación para Agentes de IA (Meta-desarrollo)

Para garantizar la mantenibilidad y agilidad del desarrollo asistido por Inteligencia Artificial, se han implementado capas de "instrucciones de oro" y herramientas de comunicación nativa entre el código y los agentes que aseguran la coherencia del proyecto a largo plazo.

##### 4.1 — Archivo de Reglas y Convenciones (AGENTS.md)

Se ha creado el archivo raíz `AGENTS.md` que actúa como la "fuente de verdad" para cualquier IA que interactúe con el repositorio. Este archivo es el primer punto de lectura para agentes externos y define:

- **Idioma**: Código en Inglés, Interfaz en Español.
- **Arquitectura**: Uso obligatorio de `SoftDeletes` y la entidad centralizada `Institution`.
- **Contexto**: Referencias indexadas a los documentos de `ia_docs/` para garantizar que la IA comprenda el negocio antes de proponer cambios.

##### 4.2 — Integración de Laravel Boost (MCP)

Se ha instalado y configurado `laravel/boost` para exponer un servidor MCP (Model Context Protocol). Esta integración técnica dota a los agentes de IA (Antigravity, Cursor, Windsurf) de capacidades de introspección profunda:

- **Base de Datos**: Inspección del esquema y ejecución de queries de lectura.
- **Rutas**: Listado de endpoints, nombres y middleware asociados.
- **Tinker**: Ejecución de código PHP en caliente para validar lógica de modelos y controladores.

```bash
# Instalación del puente IA
composer require laravel/boost --dev
php artisan boost:install
```

---

Ejecución final requerida para cerrar este hito con estado saludable:

```bash
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan test
```

> **ENTREGA:** La entidad central `Institution` y la tabla hiper-extendida `users` conviven y automatizan parámetros administrativos. El proyecto queda técnicamente "blindado" para el desarrollo asistido por IA mediante `AGENTS.md` y `Laravel Boost`. Se han establecido fundaciones que deberán restringirse estrictamente en el **Hito 2 (RBAC)**.

### Hito 2 — Roles y Permisos (RBAC) con Spatie Laravel Permissions + Laravel Policies

#### 1 — Lineamientos de arquitectura

> Este documento define el enfoque de autorización adoptado para el proyecto Amantina App. Debe ser seguido estrictamente. No se deben proponer variaciones a este diseño sin justificación explícita.

##### 1.1 Estructura técnica de roles y permisos (Hito 2)

**Roles (Gestionado por Spatie Laravel Permissions)**
Total roles: 4
- `admin`
- `docente`
- `estudiante`
- `representante`

**Permissions (Gestionado por Spatie Laravel Permissions)**
Total permissions: 9
- `users:create` (crear usuarios)
- `users:view-any` (ver información de otros)
- `users:view-self` (ver información propia)
- `users:edit-any` (editar información de otros)
- `users:edit-self` (editar información propia)
- `users:delete` (eliminar usuarios)
- `institution:view`
- `institution:edit`
- `institution:update`

**Role-Permission Matrix (Gestionado por Laravel Policies)**

- **admin**: Posee todos los permisos.

- **teacher**:
  - `users:create` (Un usuario puede crear usuarios de tipo estudiante, mas no usuarios de tipo admin o profesor, este tipo de usuarios solo lo puede crear otro usuario con rol de administrador).
  - `users:view-any` (ver información de otros).
  - `users:view-self` (ver información propia).
  - `users:edit-any` (editar información de usuarios con rol de estudiante o representante, mas no usuarios con rol de profesor o administrador).
  - `users:edit-self` (editar información propia).
  - `institution:view` (ver información de la institución).

- **student**:
  - `users:view-any` (ver información de su representante).
  - `users:view-self` (ver información propia).
  - `users:edit-self` (editar información propia).
  - `institution:view` (ver información de la institución).

- **representative**:
  - `users:view-any` (ver información de su representado).
  - `users:view-self` (ver información propia).
  - `users:edit-any` (editar información de representado solamente).
  - `users:edit-self` (editar información propia).
  - `institution:view` (ver información de la institución).

##### 1.2 Relación docente-estudiante (IMPORTANTE)

La relación entre docente y estudiante **no es directa**. Está mediada por la estructura académica:

- Un **estudiante** se vincula a una `Section` a través de la tabla `enrollments` (para un año escolar específico).
- Un **docente** se vincula a una `Section` a través de la tabla `teacher_assignments` (para un año escolar específico).
- Un docente tiene autoridad sobre un estudiante **solo si ambos están asignados a la misma sección en el periodo académico activo**.

Esta relación es dinámica: cambia cada año escolar sin romper el historial.

##### 1.3 Quién registra horas

**Los estudiantes NUNCA registran sus propias horas.** Solo el `admin` y el `docente` pueden registrar/editar horas, y el docente únicamente para estudiantes de su sección activa.

---

#### 2. Principio fundamental del diseño

El sistema usa **dos capas de autorización independientes**. Ninguna reemplaza a la otra.

```
Capa 1 — Spatie Permission  →  "¿Puede este usuario realizar esta acción en general?"
Capa 2 — Laravel Policy     →  "¿Puede este usuario realizar esta acción sobre ESTE objeto específico?"
```

##### Por qué dos capas

Con solo permisos Spatie, para evitar que un docente edite a un admin, se termina creando permisos excesivamente específicos como `edit-student-profile`, `edit-student-academic`, etc. Esto genera:

- Decenas de permisos difíciles de mantener.
- Lógica de negocio codificada en nombres de strings en la base de datos.
- Acumulación de `if/else` en los controladores.

Con Policies, la restricción "quién puede actuar sobre quién" vive en una clase PHP testeable, separada del controlador.

---

#### 3. Permisos Spatie (Capa 1)

##### Nomenclatura

```
recurso:accion
```

Ejemplos: `users:edit`, `hours:create`, `reports:view`.

**NO se incluye el alcance en el nombre del permiso.** El alcance (sobre quién o qué objeto aplica) es responsabilidad exclusiva de la Policy.

##### Lista de permisos y asignación (Hito 2)

Los permisos se asignan a los roles siguiendo la matriz definida en los lineamientos (Sección 1.1). La lista de permisos base para este hito es:

```php
$permisos_hito2 = [
    'users:create',
    'users:view-any',
    'users:view-self',
    'users:edit-any',
    'users:edit-self',
    'users:delete',
    'institution:view',
    'institution:edit',
    'institution:update',
];
```

> **Nota crítica:** `users:view-any` y `users:edit-any` son permisos con alcance restringido por Policies para los roles no administrativos. Siempre se evalúan ambas capas.

> **Nota crítica:** `hours:view-any` en `estudiante` y `representante` NO significa que pueden ver todas las horas del sistema. El permiso les da acceso al recurso en general; la Policy restringe qué registros específicos pueden ver. Siempre se evalúan ambas capas.

---

#### 4. Laravel Policies (Capa 2)

##### Qué es una Policy

Una Policy es una clase PHP ubicada en `app/Policies/`. Cada método representa una acción y recibe como parámetros el usuario autenticado y el objeto sobre el que se quiere actuar. Retorna `true` (permitido) o `false` (denegado).

##### El método `before`

Todas las Policies del sistema deben incluir el método `before`. Este se ejecuta antes que cualquier otro método de la Policy. Se usa para dar acceso total al `admin` sin repetir ese check en cada método.

```php
public function before(User $auth, string $ability): ?bool
{
    if ($auth->hasRole('admin')) return true;
    return null; // null = continuar evaluando el método correspondiente
}
```

##### Registro de Policies

En `app/Providers/AuthServiceProvider.php`:

```php
protected $policies = [
    User::class    => UserPolicy::class,
    HourLog::class => HourPolicy::class,
];
```

---

#### 5. UserPolicy — implementación completa

**Archivo:** `app/Policies/UserPolicy.php`

```php
<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * El admin pasa todas las verificaciones sin condiciones.
     */
    public function before(User $auth, string $ability): ?bool
    {
        if ($auth->hasRole('admin')) return true;
        return null;
    }

    /**
     * ¿Puede ver el listado de usuarios?
     * Solo el docente (además del admin que ya pasó por before).
     * Estudiantes y representantes no acceden al listado de usuarios.
     */
    public function viewAny(User $auth): bool
    {
        return $auth->hasRole('docente');
    }

    /**
     * ¿Puede ver el perfil de $target?
     * Docente: puede ver estudiantes.
     * Cada usuario puede ver su propio perfil.
     */
    public function view(User $auth, User $target): bool
    {
        if ($auth->id === $target->id) return true;

        return $auth->hasRole('docente') && $target->hasRole('estudiante');
    }

    /**
     * ¿Puede editar los datos de $target?
     * Docente: solo puede editar estudiantes, nunca a otro docente ni al admin.
     * Nadie más (fuera del admin) puede editar usuarios.
     */
    public function update(User $auth, User $target): bool
    {
        return $auth->hasRole('docente') && $target->hasRole('estudiante');
    }

    /**
     * ¿Puede eliminar a $target?
     * Nadie fuera del admin puede eliminar usuarios.
     * (El admin ya pasó por before(), este método solo aplica a los demás.)
     */
    public function delete(User $auth, User $target): bool
    {
        return false;
    }

    /**
     * ¿Puede activar/desactivar a $target?
     * Solo el admin. (Ya resuelto por before().)
     */
    public function toggleActive(User $auth, User $target): bool
    {
        return false;
    }
}
```

---

#### 6. HourPolicy — implementación completa

**Archivo:** `app/Policies/HourPolicy.php`

```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\HourLog;
use Illuminate\Support\Facades\DB;

class HourPolicy
{
    /**
     * El admin pasa todas las verificaciones sin condiciones.
     */
    public function before(User $auth, string $ability): ?bool
    {
        if ($auth->hasRole('admin')) return true;
        return null;
    }

    /**
     * ¿Puede registrar o editar horas para $student?
     *
     * Solo el docente puede, y únicamente si comparte sección activa
     * con el estudiante. Los estudiantes NUNCA registran sus propias horas.
     */
    public function manage(User $auth, User $student): bool
    {
        if (!$auth->hasRole('docente')) return false;

        return $this->docenteYEstudianteCompartenSeccion($auth, $student);
    }

    /**
     * ¿Puede ver las horas de $student?
     *
     * Docente:       si comparte sección activa con el estudiante.
     * Estudiante:    solo sus propias horas.
     * Representante: solo las horas de su representado.
     */
    public function view(User $auth, User $student): bool
    {
        if ($auth->hasRole('docente')) {
            return $this->docenteYEstudianteCompartenSeccion($auth, $student);
        }

        if ($auth->hasRole('estudiante')) {
            return $auth->id === $student->id;
        }

        if ($auth->hasRole('representante')) {
            return $auth->represented()
                ->where('id', $student->id)
                ->exists();
        }

        return false;
    }

    /**
     * ¿Puede aprobar horas de $student?
     * Solo el docente con relación de sección activa.
     */
    public function approve(User $auth, User $student): bool
    {
        if (!$auth->hasRole('docente')) return false;

        return $this->docenteYEstudianteCompartenSeccion($auth, $student);
    }

    /**
     * Verifica si el docente y el estudiante comparten sección
     * en el año escolar activo.
     *
     * Lógica:
     * - teacher_assignments vincula docente → sección → año escolar
     * - enrollments vincula estudiante → sección → año escolar
     * - Si existe una sección en común en el periodo activo, hay relación.
     */
    private function docenteYEstudianteCompartenSeccion(User $docente, User $student): bool
    {
        return DB::table('teacher_assignments as ta')
            ->join('enrollments as e', 'ta.section_id', '=', 'e.section_id')
            ->join('school_years as sy', 'ta.school_year_id', '=', 'sy.id')
            ->where('ta.user_id', $docente->id)
            ->where('e.user_id', $student->id)
            ->where('sy.is_active', true)
            ->exists();
    }
}
```

---

#### 7. Uso en controladores

### Patrón estándar — siempre dos capas

```php
public function unaAccion(Request $request, User $target)
{
    // Capa 1: permiso general (Spatie)
    if (!auth()->user()->hasPermissionTo('users:edit')) {
        abort(403);
    }

    // Capa 2: ¿puede actuar sobre ESTE objeto? (Policy)
    $this->authorize('update', $target);

    // Lógica de negocio...
}
```

### Ejemplo real: registrar horas

```php
// HourController.php
public function store(Request $request)
{
    $student = User::findOrFail($request->student_id);

    // Capa 1: ¿tiene permiso general de crear horas?
    if (!auth()->user()->hasPermissionTo('hours:create')) {
        abort(403);
    }

    // Capa 2: ¿puede registrar horas para ESTE estudiante?
    // Internamente verifica que docente y estudiante compartan sección activa.
    $this->authorize('manage', $student);

    HourLog::create([
        'student_id' => $student->id,
        'teacher_id' => auth()->id(),
        // ...resto de campos
    ]);

    return redirect()->back()->with('success', 'Horas registradas.');
}
```

### Ejemplo real: ver horas de un estudiante

```php
// HourController.php
public function show(User $student)
{
    // Capa 1: ¿tiene permiso general de ver horas?
    if (!auth()->user()->hasPermissionTo('hours:view-any')) {
        abort(403);
    }

    // Capa 2: ¿puede ver las horas de ESTE estudiante?
    // La Policy decide internamente según el rol del autenticado:
    // - Docente: verifica sección compartida
    // - Estudiante: verifica que sea el mismo usuario
    // - Representante: verifica relación de representación
    $this->authorize('view', $student);

    $hours = HourLog::where('student_id', $student->id)->get();

    return Inertia::render('Hours/Show', [
        'student' => $student,
        'hours'   => $hours,
    ]);
}
```

### Ejemplo real: editar usuario (el caso original del problema)

```php
// UserController.php
public function update(Request $request, User $user)
{
    // Capa 1: ¿tiene permiso general de editar usuarios?
    if (!auth()->user()->hasPermissionTo('users:edit')) {
        abort(403);
    }

    // Capa 2: ¿puede editar a ESTE usuario?
    // Si el autenticado es docente y $user es admin → Policy retorna false → 403 automático.
    // Si el autenticado es docente y $user es estudiante → Policy retorna true → continúa.
    $this->authorize('update', $user);

    $user->update($request->validated());

    return redirect()->route('users.index');
}
```

---

#### 8. Pasar permisos al frontend React (Inertia)

Las Policies **nunca se evalúan en el frontend**. El backend evalúa y pasa el resultado como prop booleano.

```php
// UserController.php
public function show(User $user)
{
    return Inertia::render('Users/Show', [
        'user' => $user,
        'can'  => [
            'edit'         => auth()->user()->can('update', $user),
            'delete'       => auth()->user()->can('delete', $user),
            'toggleActive' => auth()->user()->can('toggleActive', $user),
        ],
    ]);
}
```

```tsx
// Users/Show.tsx
interface Props {
    user: User;
    can: {
        edit: boolean;
        delete: boolean;
        toggleActive: boolean;
    };
}

export default function UserShow({ user, can }: Props) {
    return (
        <div>
            <h1>{user.name}</h1>
            {can.edit && <Button>Editar</Button>}
            {can.delete && <Button variant="destructive">Eliminar</Button>}
            {can.toggleActive && <Button>Activar / Desactivar</Button>}
        </div>
    );
}
```

---

#### 9. Reglas que no se deben romper

1. **Nunca omitir la Capa 1.** Aunque la Policy cubra el caso, el check de permiso Spatie debe estar presente. Las dos capas son independientes y complementarias.

2. **Nunca poner lógica de negocio en los controladores.** Todo `if ($auth->hasRole(...))` relacionado con restricciones sobre objetos específicos pertenece a la Policy, no al controlador.

3. **Nunca codificar el alcance en el nombre del permiso.** `users:edit` es correcto. `users:edit-student-academic` es incorrecto — el alcance es responsabilidad de la Policy.

4. **Nunca evaluar Policies en el frontend.** React recibe booleanos calculados por el backend. No recibe los permisos crudos para evaluarlos en el cliente.

5. **El método `before` es obligatorio en cada Policy.** Garantiza que el admin siempre tenga acceso total sin duplicar esa lógica en cada método.

6. **Los estudiantes nunca registran sus propias horas.** Solo `admin` y `docente` pueden crear o editar `HourLog`. Cualquier implementación que permita otra cosa es incorrecta.

---

#### 10. Resumen visual del flujo

```
Request del usuario
        │
        ▼
┌───────────────────────┐
│  Capa 1: Spatie       │  ¿Tiene el permiso general?
│  hasPermissionTo()    │  (users:edit, hours:create, etc.)
└───────────┬───────────┘
            │ sí
            ▼
┌───────────────────────┐
│  Capa 2: Policy       │  ¿Puede actuar sobre ESTE objeto?
│  $this->authorize()   │  (¿este user? ¿este student? ¿esta hora?)
└───────────┬───────────┘
            │ sí
            ▼
    Lógica del controlador
    (ya autorizado, sin más ifs de roles)
```

Si cualquier capa retorna `false` → **403 Forbidden** automático.
