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
| --- |

## Resumen de Hitos

| Hito | Título | Enfoque Principal |
| --- | --- | --- |
| 0 | Instalacion y esqueleto base | Setup inicial, auth base, Spatie |
| 1 | Usuarios: base del sistema | Tabla users definitiva y seeder admin |
| 2 | Roles y permisos | RBAC con Spatie Permissions |
| 3 | Autenticacion personalizada | Login con contexto y multi-rol |
| 4 | CRUD de usuarios | Gestión integral y perfil de usuario |
| 5 | Estructura academica | Años, lapsos, grados y secciones |
| 6 | Inscripciones y asignaciones | Vínculo alumno/sección y profesor/sección |
| 7 | Representantes | Vínculo representante/estudiante |
| 8 | Informacion de salud | Condiciones médicas y soportes |
| 9 | Catalogos de configuracion | Actividades y ubicaciones |
| 10 | Jornadas de campo | Registro central de actividades |
| 11 | Asistencia y subactividades | Acreditación de horas y evidencias |
| 12 | Horas externas | Acreditación para transferidos |
| 13 | Acumulados y dashboards | Progreso visual y KPIs |
| 14 | Reportes en PDF | Generación de certificados y listados |
| 15 | Revision y estabilizacion | QA final y seeders demo |

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

| Archivo | Acción | Motivo |
| --- | --- | --- |
| `database/migrations/2026_03_13_143000_create_institution_table.php` | Crear | Tabla base para los datos fijos del plantel |
| `app/Models/Institution.php` | Crear | Modelo asociado a Datos Institucionales |
| `database/seeders/InstitutionSeeder.php` | Crear | Primer registro (Amantina de Sucre) |
| `app/Http/Controllers/Settings/InstitutionController.php` | Crear | Controlador para manejar actualización en UI |
| `resources/js/pages/settings/institution.tsx` | Crear | Componente React para CRUD de Institución |
| `routes/settings.php` | Modificar | Rutas para el controlador InstitutionController |
| `resources/js/layouts/settings/layout.tsx` | Modificar | Añadir menú lateral para "Datos Institucionales" |
| `database/migrations/2026...add_new_fields_to_users_table.php` | Crear | Expansión de campos en tabla `users` (`cedula`, `phone`, etc.) |
| `database/migrations/2026...add_address_to_users_table.php` | Crear | Expansión de campos en tabla `users` (`address`) |
| `app/Models/User.php` | Modificar | `$fillable`, `casts`, y `SoftDeletes` |
| `database/factories/UserFactory.php` | Modificar | Campos obligatorios y valores por defecto |
| `database/seeders/UserSeeder.php` | Crear | Administrador raíz del sistema |
| `database/seeders/DatabaseSeeder.php` | Modificar | Ejecutar InstitutionSeeder y UserSeeder |
| `app/Concerns/ProfileValidationRules.php` | Modificar | Reglas de validación y unicidad de cédula |
| `app/Actions/Fortify/CreateNewUser.php` | Modificar | Persistencia del usuario e institución origen nativa |
| `resources/js/pages/auth/register.tsx` | Modificar | Inclusión visual de campos base y condicionales |
| `tests/Feature/Auth/RegistrationTest.php` | Modificar | Pruebas unificadas de usuario e institución |
| `tests/Feature/Settings/InstitutionTest.php` | Crear | Pruebas de CRUD de la entidad Institution |

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
                        className="flex flex-col space-y-1 space-x-0"
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
import { Check, Mail, MapPin, Phone, Building2, Fingerprint } from 'lucide-react';
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
                                        <Label htmlFor="name" className="flex items-center gap-2">
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
                                        <Label htmlFor="code" className="flex items-center gap-2">
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
                                        <Label htmlFor="email" className="flex items-center gap-2">
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
                                        <Label htmlFor="phone" className="flex items-center gap-2">
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
                                    <Label htmlFor="address" className="flex items-center gap-2">
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
                                        <p className="flex items-center gap-1.5 text-sm text-muted-foreground">
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
*(Nota: Puesto que el modelo establece el cast de `'password' => 'hashed'`, el texto plano `password` se asigna directamente y Eloquent lo cifrará.)*

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
                                <Label htmlFor="email">Correo electrónico</Label>
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
                                <Label htmlFor="address">Dirección de residencia</Label>
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
                                <Label>¿Eres estudiante transferido de otra institución?</Label>
                                <div className="flex gap-6">
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="radio"
                                            name="is_transfer"
                                            value="0"
                                            defaultChecked
                                            tabIndex={6}
                                            onChange={() => setIsTransfer(false)}
                                        />
                                        No
                                    </label>
                                    <label className="flex items-center gap-2 cursor-pointer">
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
                                    <Label htmlFor="institution_origin">Institución de origen</Label>
                                    <Input
                                        id="institution_origin"
                                        type="text"
                                        required
                                        tabIndex={8}
                                        name="institution_origin"
                                        placeholder="Nombre de la institución anterior"
                                    />
                                    <InputError message={errors.institution_origin} />
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
                                <Label htmlFor="password_confirmation">Confirmar contraseña</Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    required
                                    tabIndex={10}
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    placeholder="Confirmar contraseña"
                                />
                                <InputError message={errors.password_confirmation} />
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

                        <div className="text-center text-sm text-muted-foreground">
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

