# Amantina App - Guía para Agentes de IA

Este documento (AGENTS.md) provee contexto crítico sobre la arquitectura y convenciones del proyecto para asistir a herramientas e inteligencias artificiales que interactúen con este código.

## Resumen del Proyecto

El sistema Bitácora Socioproductiva es una aplicación web orientada exclusivamente al registro, seguimiento y reporte de las horas prácticas que los estudiantes acumulan en la asignatura Socioproductiva a lo largo de su bachillerato. Su razón de ser es reemplazar registros manuales en papel o en hojas de cálculo, centralizando toda la información en una plataforma consultable por todos los actores involucrados: administradores, profesores, estudiantes y representantes.

El sistema no es un gestor escolar general. Está deliberadamente acotado a una sola asignatura y a una sola función: saber cuántas horas ha acumulado cada estudiante, en qué actividades, con qué desempeño, y cuánto le falta para cumplir el cupo requerido por la institución. Esta decisión de alcance es intencional y debe respetarse en futuras iteraciones para evitar que el sistema crezca hacia responsabilidades que no le corresponden.

Su objetivo principal es gestionar la información académica e institucional de los estudiantes de un plantel único (no multi-tenant). La informacion academica esta limitada a una unica asignatura Socioproductiva, no es proposito de esta aplicacion almacenar datos de otras asignaturas.

> **Fuera de alcance:** calificaciones formales de otras materias, comunicación entre usuarios, planificación de actividades futuras, gestión de representantes como sistema independiente, y cualquier funcionalidad propia de un sistema de gestión escolar completo.

## Stack Tecnológico Principal

- **Backend:** Laravel 12 (PHP 8.4+)
- **ORM:** Laravel Eloquent preferiblemente sobre Query Builder y Raw SQL
- **Frontend:** React 19 con TypeScript
- **Integración:** Inertia.js (SSR no requerido por ahora, renderizado principal en cliente con enrutamiento de servidor)
- **Estilos:** Tailwind CSS 4, shadcn/ui
- **Paquetes clave:**
    - `spatie/laravel-permission` (Para control de RBAC)
    - `spatie/laravel-medialibrary` (Para gestión unificada de archivos)
    - `Askedio/laravel-soft-cascade` (Para cascada de soft deletes)
    - Laravel Fortify (Para flujos completos de autenticación sin vistas propias)
- **Herramientas de Desarrollo:**
    - Laravel Herd (PHP 8.4)
    - PostgreSQL 18
    - pgAdmin4 (Para gestión de base de datos)
    - Antigravity, VS Code, Cursor, Windsurf, Kiro, Trae, Google Gemini, Claude Web, (Editores de codigo con generacion de codigo IA)
    - Git (Para control de versiones)

## Convenciones Informativas y Arquitectura

Las siguientes convenciones buscan mantener la consistencia del código sin ser bloqueantes. Por favor respétalas a la hora de implementar nuevas funcionalidades:

### 1. Idioma y Nomenclatura

- Escribe todo el código, variables, métodos y migraciones en Inglés.
- Sin embargo, todos los textos de la interfaz (React), etiquetas, placeholders y mensajes de error de validación deben estar estrictamente en Español.

### 2. Base de Datos

- Las migraciones deben usar `SoftDeletes` (`$table->softDeletes()`) como regla general para tablas transaccionales, evitando la pérdida de integridad referencial.
- Existe una entidad central llamada `Institution` (con una tabla en singular: `institution`). El sistema asume que existe un registro único (ID=1) que define las variables de configuración del colegio (nombre, teléfono, etc.).

### 3. Frontend (React / Inertia)

- El entorno de desarrollo no asume conexión a internet permanente en el servidor de producción. Todo el stack frontend debe funcionar **offline**. No importa recursos por CDNs externos (Google Fonts, Tailwind play, etc.), todo debe descargarse vía NPM.
- Los componentes de página se ubican estandarizados bajo `resources/js/pages/`.
- Los componentes reutilizables (UI) se estandarizan preferiblemente bajo `resources/js/components/ui/`.
- No crear APIs REST ni usar Axios/Fetch. Toda la comunicación de datos entre Laravel y React se hace exclusivamente inyectando props a través de Inertia.js.

### 4. Entorno de Desarrollo Local

Los comandos para ejecutar el proyecto son:

- Backend: se ejecuta en background mediante Laravel Herd (PHP 8.4) y PostgreSQL 18.
- Frontend: se ejecuta en primer plano mediante `npm run dev`.

### 5. Convenciones de Testing

- Los tests deben usar **Pest 4** como framework de testing. Se prefieren los Feature Tests sobre los Unit Tests para asegurar la integración completa con Inertia y la base de datos.
- El PHP CLI usado para tests debe tener habilitada la extensión `sockets`, porque Pest Browser la necesita incluso cuando se ejecutan subconjuntos pequeños de tests.
- Cada funcionalidad debe tener al menos un test.
- Se deben usar datos de prueba que simulen datos reales. No usar datos reales en los tests.
- **Base de datos de pruebas separada:** Los tests usan la base de datos `amantina_app_testing` (PostgreSQL), distinta de la de desarrollo (`amantina_app`), para no afectar los datos reales. Crear la BD de pruebas una vez con: `psql -U postgres -c "CREATE DATABASE amantina_app_testing;"` (o desde pgAdmin). Las credenciales (host, usuario, contraseña) se toman del `.env`.
- **Overrides locales por máquina:** No cambiar `phpunit.xml` ni `.env.testing` para adaptar la base de datos a una computadora puntual. Para trabajar en una máquina que requiere SQLite, crear un archivo local ignorado llamado `.env.testing.local` con `DB_CONNECTION=sqlite` y `DB_DATABASE=database/database_testing.sqlite`, crear ese archivo SQLite, y ejecutar los tests con `php artisan test --env=testing.local --compact tests/...`. Esto evita conflictos al alternar entre casa/trabajo.
- **⚠️ PROTOCOLO OBLIGATORIO ANTES DE EJECUTAR TESTS:**
  ```bash
  php artisan config:clear
  php artisan cache:clear
  php artisan test --env=testing --compact tests/...
  ```
  O en una sola línea:
  ```bash
  php artisan config:clear; php artisan cache:clear; php artisan test --env=testing --compact tests/...
  ```
  **¿Por qué es obligatorio?** Laravel Herd cachea la configuración con `.env` (desarrollo). Sin limpiar cache, los tests usan `amantina_app` (desarrollo) en lugar de `amantina_app_testing`, causando **PÉRDIDA DE DATOS** en la base de datos de desarrollo.
- **Estado actual de la suite de tests:**
  - ✅ **700 tests pasando** (342 Feature + 358 Browser)
  - ✅ **2462 assertions**
  - ✅ **0 tests fallidos**
  - ✅ Duración: ~18 minutos
  - Desglose Browser: 55 HappyPath + 69 Security + 234 otros

### 6. Convenciones de Git

- Las ramas deben seguir el formato `feature/nombre-de-la-funcionalidad` o `fix/nombre-del-fix`.
- Los mensajes de commit deben seguir el formato Conventional Commits (ej: `feat: add new user registration endpoint`) escritos preferiblemente en inglés.
- No generar commits ni subir a github sin consultar previamente por aprobación.
- **Antes de generar un commit, ejecutar OBLIGATORIAMENTE:**
  ```bash
  php artisan config:clear
  php artisan cache:clear
  php artisan test --env=testing
  ```
  Es obligatorio corregir cualquier fallo —ya sea un error de implementación o un efecto secundario funcional (regresión)— antes de confirmar los cambios para garantizar la estabilidad del repositorio.
- **¿Por qué limpiar cache antes de tests?** Laravel Herd cachea la configuración con `.env` (desarrollo). Sin limpiar cache, los tests usan `amantina_app` (desarrollo) en lugar de `amantina_app_testing`, causando PÉRDIDA DE DATOS en la base de datos de desarrollo.

### 7. Convenciones de Seguridad

- No almacenar contraseñas en texto plano.
- Usar contraseñas fuertes y únicas para cada servicio.
- No compartir claves privadas o secretos en el código fuente.
- Implementar medidas de seguridad adecuadas para proteger los datos de los usuarios.

### 8. Normas Generales

- Si se genera del lado del backend una funcionalidad, como una nueva entidad y su funcionalidad crud, deberá también generar la funcionalidad del lado del frontend, con sus respectivos componentes, rutas, etc.

### 9. Documentación Extendida (Lectura Requerida)

Para entender el panorama general, reglas de negocio e histórico de decisiones, la IA cuenta con los siguientes archivos obligatorios de referencia:

- **Especificaciones y Requerimientos:** `ia_docs/bitacora_especificaciones.md` (Define el "qué" y el "por qué" funcional del negocio).
- **Manual de Implementación:** `ia_docs/amantina_implementacion.md` (Contiene el registro histórico de los Hitos ya completados y el porqué arquitectónico).
- **Fresh setup local:** `fresh-setup.md` (Fuente de verdad para recrear la instalación local desde cero y cargar datos completos de ejemplo con `CompleteTestDataSeeder`).

_(Este documento puede ser expandido a medida que el proyecto requiera nuevas convenciones)._

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.19
- inertiajs/inertia-laravel (INERTIA_LARAVEL) - v2
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/wayfinder (WAYFINDER) - v0
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- @inertiajs/react (INERTIA_REACT) - v2
- react (REACT) - v19
- tailwindcss (TAILWINDCSS) - v4
- @laravel/vite-plugin-wayfinder (WAYFINDER_VITE) - v0
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `wayfinder-development` — Activates whenever referencing backend routes in frontend components. Use when importing from @/actions or @/routes, calling Laravel routes from TypeScript, or working with Wayfinder route functions.
- `inertia-react-development` — Develops Inertia.js v2 React client-side applications. Activates when creating React pages, forms, or navigation; using <Link>, <Form>, useForm, or router; working with deferred props, prefetching, or polling; or when user mentions React with Inertia, React pages, React forms, or React navigation.
- `tailwindcss-development` — Styles applications using Tailwind CSS v4 utilities. Activates when adding styles, restyling components, working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors, typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle, hero section, cards, buttons, or any visual/UI changes.
- `fortify-development` — Laravel Fortify headless authentication backend development. Activate when implementing authentication features including login, registration, password reset, email verification, two-factor authentication (2FA/TOTP), profile updates, headless auth, authentication scaffolding, or auth guards in Laravel applications.
- `medialibrary-development` — Build and work with spatie/laravel-medialibrary features including associating files with Eloquent models, defining media collections and conversions, generating responsive images, and retrieving media URLs and paths.
- `laravel-permission-development` — Build and work with Spatie Laravel Permission features, including roles, permissions, middleware, policies, teams, and Blade directives.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan Commands

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`, `php artisan tinker --execute "..."`).
- Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Debugging

- Use the `database-query` tool when you only need to read from the database.
- Use the `database-schema` tool to inspect table structure before writing migrations or models.
- To execute PHP code for debugging, run `php artisan tinker --execute "your code here"` directly.
- To read configuration values, read the config files directly or run `php artisan config:show [key]`.
- To inspect routes, run `php artisan route:list` directly.
- To check environment variables, read the `.env` file directly.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - `public function __construct(public GitHub $github) { }`
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<!-- Explicit Return Types and Method Params -->
```php
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
```

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd and will be available at: `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs for the user.
- You must not run any commands to make the site available via HTTP(S). It is always available through Laravel Herd.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-react-development` when working with Inertia client-side patterns.

# Inertia v2

- Use all Inertia features from v1 and v2. Check the documentation before making changes to ensure the correct approach.
- New features: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== wayfinder/core rules ===

# Laravel Wayfinder

Wayfinder generates TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

- IMPORTANT: Activate `wayfinder-development` skill whenever referencing backend routes in frontend components.
- Invokable Controllers: `import StorePost from '@/actions/.../StorePostController'; StorePost()`.
- Parameter Binding: Detects route keys (`{post:slug}`) — `show({ slug: "my-post" })`.
- Query Merging: `show(1, { mergeQuery: { page: 2, sort: null } })` merges with current URL, `null` removes params.
- Inertia: Use `.form()` with `<Form>` component or `form.submit(store())` with useForm.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest 4

- This application uses **Pest 4** for testing. All tests must be written using Pest syntax.
- Use `php artisan make:test --pest {name}` to create a new test (Pest is the default).
- **Feature tests** (in `tests/Feature/`) use `RefreshDatabase` for HTTP/controller testing.
- **Browser tests** (in `tests/Browser/`) use `DatabaseTruncation` and `Browsable` for E2E testing with Playwright.
- Browser tests should use `visit()`, `click()`, `type()`, etc. NOT HTTP methods like `$this->get()`, `$this->post()`.
- If you need to test HTTP endpoints directly (PUT, POST, DELETE), use Feature tests, not Browser tests.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

=== inertia-react/core rules ===

# Inertia + React

- IMPORTANT: Activate `inertia-react-development` when working with Inertia React client-side patterns.

=== tailwindcss/core rules ===

# Tailwind CSS

- Always use existing Tailwind conventions; check project patterns before adding new ones.
- IMPORTANT: Always use `search-docs` tool for version-specific Tailwind CSS documentation and updated code examples. Never rely on training data.
- IMPORTANT: Activate `tailwindcss-development` every time you're working with a Tailwind CSS or styling-related task.

=== laravel/fortify rules ===

# Laravel Fortify

- Fortify is a headless authentication backend that provides authentication routes and controllers for Laravel applications.
- IMPORTANT: Always use the `search-docs` tool for detailed Laravel Fortify patterns and documentation.
- IMPORTANT: Activate `developing-with-fortify` skill when working with Fortify authentication features.

=== spatie/laravel-medialibrary rules ===

## Media Library

- `spatie/laravel-medialibrary` associates files with Eloquent models, with support for collections, conversions, and responsive images.
- Always activate the `medialibrary-development` skill when working with media uploads, conversions, collections, responsive images, or any code that uses the `HasMedia` interface or `InteractsWithMedia` trait.

</laravel-boost-guidelines>

<!-- gentle-ai:engram-protocol -->
## Engram Persistent Memory — Protocol

You have access to Engram, a persistent memory system that survives across sessions and compactions.
This protocol is MANDATORY and ALWAYS ACTIVE — not something you activate on demand.

### PROACTIVE SAVE TRIGGERS (mandatory — do NOT wait for user to ask)

Call `mem_save` IMMEDIATELY and WITHOUT BEING ASKED after any of these:
- Architecture or design decision made
- Team convention documented or established
- Workflow change agreed upon
- Tool or library choice made with tradeoffs
- Bug fix completed (include root cause)
- Feature implemented with non-obvious approach
- Notion/Jira/GitHub artifact created or updated with significant content
- Configuration change or environment setup done
- Non-obvious discovery about the codebase
- Gotcha, edge case, or unexpected behavior found
- Pattern established (naming, structure, convention)
- User preference or constraint learned

Self-check after EVERY task: "Did I make a decision, fix a bug, learn something non-obvious, or establish a convention? If yes, call mem_save NOW."

Format for `mem_save`:
- **title**: Verb + what — short, searchable (e.g. "Fixed N+1 query in UserList")
- **type**: bugfix | decision | architecture | discovery | pattern | config | preference
- **scope**: `project` (default) | `personal`
- **topic_key** (recommended for evolving topics): stable key like `architecture/auth-model`
- **capture_prompt**: optional; default `true`. Do not set this for normal human/proactive saves. Set `false` only for automated artifacts such as SDD proposal/spec/design/tasks/apply/verify/archive/init reports, testing-capabilities caches, onboarding/state artifacts, or skill-registry output.
- **content**:
  - **What**: One sentence — what was done
  - **Why**: What motivated it (user request, bug, performance, etc.)
  - **Where**: Files or paths affected
  - **Learned**: Gotchas, edge cases, things that surprised you (omit if none)

Prompt capture behavior (Engram v1.15.3+):
- `mem_save` captures the user prompt best-effort when the MCP process already has prompt context for the same `project + session_id`.
- `mem_save` never invents prompt text. If no prompt context exists, the save still succeeds without prompt capture.
- `mem_save_prompt` records the prompt and feeds SessionActivity so later `mem_save` calls can capture and dedupe it.
- If an agent/plugin hook can observe the user's prompt before derived memory saves happen, it should call `mem_save_prompt` first.
- Do not decide prompt capture by `type`; SDD artifacts also use `architecture`, and human decisions can too. Use explicit `capture_prompt: false` for automated artifacts.
- If an older Engram tool schema does not expose `capture_prompt`, omit the field rather than failing.

Topic update rules:
- Different topics MUST NOT overwrite each other
- Same topic evolving → use same `topic_key` (upsert)
- Unsure about key → call `mem_suggest_topic_key` first
- Know exact ID to fix → use `mem_update`

### WHEN TO SEARCH MEMORY

On any variation of "remember", "recall", "what did we do", "how did we solve", "recordar", "qué hicimos", or references to past work:
1. Call `mem_context` — checks recent session history (fast, cheap)
2. If not found, call `mem_search` with relevant keywords
3. If found, use `mem_get_observation` for full untruncated content

Also search PROACTIVELY when:
- Starting work on something that might have been done before
- User mentions a topic you have no context on
- User's FIRST message references the project, a feature, or a problem — call `mem_search` with keywords from their message to check for prior work before responding

### SESSION CLOSE PROTOCOL (mandatory)

Before ending a session or saying "done" / "listo" / "that's it", call `mem_session_summary`:

## Goal
[What we were working on this session]

## Instructions
[User preferences or constraints discovered — skip if none]

## Discoveries
- [Technical findings, gotchas, non-obvious learnings]

## Accomplished
- [Completed items with key details]

## Next Steps
- [What remains to be done — for the next session]

## Relevant Files
- path/to/file — [what it does or what changed]

This is NOT optional. If you skip this, the next session starts blind.

### AFTER COMPACTION

If you see a compaction message or "FIRST ACTION REQUIRED":
1. IMMEDIATELY call `mem_session_summary` with the compacted summary content — this persists what was done before compaction
2. Call `mem_context` to recover additional context from previous sessions
3. Only THEN continue working

Do not skip step 1. Without it, everything done before compaction is lost from memory.
<!-- /gentle-ai:engram-protocol -->

<!-- gentle-ai:sdd-orchestrator -->
# Agent Teams Lite — Orchestrator Instructions

Bind this to the dedicated `sdd-orchestrator` agent or rule only. Do NOT apply it to executor phase agents such as `sdd-apply` or `sdd-verify`.

## Agent Teams Orchestrator

You are a COORDINATOR, not an executor. Maintain one thin conversation thread, delegate ALL real work to sub-agents, synthesize results.

### Delegation Rules

Core principle: **does this inflate my context without need?** If yes → delegate. If no → do it inline.

| Action | Inline | Delegate |
|--------|--------|----------|
| Read to decide/verify (1-3 files) | ✅ | — |
| Read to explore/understand (4+ files) | — | ✅ |
| Read as preparation for writing | — | ✅ together with the write |
| Write atomic (one file, mechanical, you already know what) | ✅ | — |
| Write with analysis (multiple files, new logic) | — | ✅ |
| Bash for state (git, gh) | ✅ | — |
| Bash for execution (test, build, install) | — | ✅ |

delegate (async) is the default for delegated work. Use task (sync) only when you need the result before your next action.

Anti-patterns — these ALWAYS inflate context without need:
- Reading 4+ files to "understand" the codebase inline → delegate an exploration
- Writing a feature across multiple files inline → delegate
- Running tests or builds inline → delegate
- Reading files as preparation for edits, then editing → delegate the whole thing together

## SDD Workflow (Spec-Driven Development)

SDD is the structured planning layer for substantial changes.

### Artifact Store Policy

- `engram` — default when available; persistent memory across sessions
- `openspec` — file-based artifacts; use only when user explicitly requests
- `hybrid` — both backends; cross-session recovery + local files; more tokens per op
- `none` — return results inline only; recommend enabling engram or openspec

### Commands

Skills (appear in autocomplete):
- `/sdd-init` → initialize SDD context; detects stack, bootstraps persistence
- `/sdd-explore <topic>` → investigate an idea; reads codebase, compares approaches; no files created
- `/sdd-apply [change]` → implement tasks in batches; checks off items as it goes
- `/sdd-verify [change]` → validate implementation against specs; reports CRITICAL / WARNING / SUGGESTION
- `/sdd-archive [change]` → close a change and persist final state in the active artifact store 
- `/sdd-onboard` → guided end-to-end walkthrough of SDD using your real codebase

Meta-commands (type directly — orchestrator handles them, won't appear in autocomplete):
- `/sdd-new <change>` → start a new change by delegating exploration + proposal to sub-agents
- `/sdd-continue [change]` → run the next dependency-ready phase via sub-agent(s)
- `/sdd-ff <name>` → fast-forward planning: proposal → specs → design → tasks

`/sdd-new`, `/sdd-continue`, and `/sdd-ff` are meta-commands handled by YOU. Do NOT invoke them as skills.

### SDD Init Guard (MANDATORY)

Before executing ANY SDD command (`/sdd-new`, `/sdd-ff`, `/sdd-continue`, `/sdd-explore`, `/sdd-apply`, `/sdd-verify`, `/sdd-archive`), check if `sdd-init` has been run for this project:

1. Search Engram: `mem_search(query: "sdd-init/{project}", project: "{project}")`
2. If found → init was done, proceed normally
3. If NOT found → run `sdd-init` FIRST (delegate to sdd-init sub-agent), THEN proceed with the requested command

This ensures:
- Testing capabilities are always detected and cached
- Strict TDD Mode is activated when the project supports it
- The project context (stack, conventions) is available for all phases

Do NOT skip this check. Do NOT ask the user — just run init silently if needed.

### Execution Mode

When the user invokes `/sdd-new`, `/sdd-ff`, or `/sdd-continue` (or an equivalent natural-language request, e.g. "haceme un SDD para X" / "do SDD for X") for the first time in a session, ASK which execution mode they prefer:

- **Automatic** (`auto`): Run all phases back-to-back without pausing. Show the final result only. Use this when the user wants speed and trusts the process.
- **Interactive** (`interactive`): After each phase completes, show the result summary and ASK: "Want to adjust anything or continue?" before proceeding to the next phase. Use this when the user wants to review and steer each step.

If the user doesn't specify, default to **Interactive** (safer, gives the user control).

Cache the mode choice for the session — don't ask again unless the user explicitly requests a mode change.

In **Interactive** mode, between phases:
1. Show a concise summary of what the phase produced
2. List what the next phase will do
3. Ask: "¿Continuamos? / Continue?" — accept YES/continue, NO/stop, or specific feedback to adjust
4. If the user gives feedback, incorporate it before running the next phase

For this agent (sub-agent delegation): **Automatic** means phases run back-to-back via sub-agents without pausing. **Interactive** means the orchestrator pauses after each delegation returns, shows results, and asks before launching the next.

### Artifact Store Mode

When the user invokes `/sdd-new`, `/sdd-ff`, or `/sdd-continue` (or an equivalent natural-language request) for the first time in a session, ALSO ASK which artifact store they want for this change:

- **`engram`**: Fast, no files created. Artifacts live in engram only. Best for solo work and quick iteration. Note: re-running a phase overwrites the previous version (no history).
- **`openspec`**: File-based. Creates `openspec/` directory with full artifact trail. Committable, shareable with team, full git history.
- **`hybrid`**: Both — files for team sharing + engram for cross-session recovery. Higher token cost.

If the user doesn't specify, detect: if engram is available → default to `engram`. Otherwise → `none`.

Cache the artifact store choice for the session. Pass it as `artifact_store.mode` to every sub-agent launch.

### Delivery Strategy

On the first `/sdd-new`, `/sdd-ff`, or `/sdd-continue` (or an equivalent natural-language request) in a session, ask once for and cache delivery strategy: `ask-on-risk` (default), `auto-chain`, `single-pr`, or `exception-ok`. Pass it as `delivery_strategy` to `sdd-tasks` and `sdd-apply` prompts.

### Dependency Graph
```
proposal -> specs --> tasks -> apply -> verify -> archive
             ^
             |
           design
```

### Result Contract
Each phase returns: `status`, `executive_summary`, `artifacts`, `next_recommended`, `risks`, `skill_resolution`.

### Review Workload Guard (MANDATORY)

After `sdd-tasks` completes and before launching `sdd-apply`, inspect `Review Workload Forecast`.

If it says `Chained PRs recommended: Yes`, `400-line budget risk: High`, estimated changed lines exceed 400, or `Decision needed before apply: Yes`, apply cached `delivery_strategy`:

- **`ask-on-risk`**: STOP and ask chained/stacked PRs vs maintainer-approved `size:exception`.
- **`auto-chain`**: Do not ask. Tell `sdd-apply` to implement only the next autonomous chained/stacked PR slice using work-unit commits.
- **`single-pr`**: STOP and require/record `size:exception` before apply.
- **`exception-ok`**: Continue, but tell `sdd-apply` this run uses `size:exception`.

Automatic mode does not override this guard. Always pass the resolved delivery strategy to `sdd-apply`.

<!-- gentle-ai:sdd-model-assignments -->
## Model Assignments

Read this table at session start (or before first delegation), cache it for the session, and pass the mapped alias in every Agent tool call via the `model` parameter. If a phase is missing, use the `default` row. If you do not have access to the assigned model (for example, no Opus access), substitute `sonnet` and continue.

| Phase | Default Model | Reason |
|-------|---------------|--------|
| orchestrator | opus | Coordinates, makes decisions |
| sdd-explore | sonnet | Reads code, structural - not architectural |
| sdd-propose | opus | Architectural decisions |
| sdd-spec | sonnet | Structured writing |
| sdd-design | opus | Architecture decisions |
| sdd-tasks | sonnet | Mechanical breakdown |
| sdd-apply | sonnet | Implementation |
| sdd-verify | sonnet | Validation against spec |
| sdd-archive | haiku | Copy and close |
| default | sonnet | Non-SDD general delegation |

<!-- /gentle-ai:sdd-model-assignments -->

### Sub-Agent Launch Pattern

ALL sub-agent launch prompts that involve reading, writing, or reviewing code MUST include pre-resolved **compact rules** from the skill registry. Follow the **Skill Resolver Protocol** (`~/.claude/skills/_shared/skill-resolver.md`).

The orchestrator resolves skills from the registry ONCE (at session start or first delegation), caches the compact rules, and injects matching rules into each sub-agent's prompt. Also reads the Model Assignments table once per session, caches `phase → alias`, includes that alias in every Agent tool call via `model`.

Orchestrator skill resolution (do once per session):
1. `mem_search(query: "skill-registry", project: "{project}")` → `mem_get_observation(id)` for full registry content
2. Fallback: read `.atl/skill-registry.md` if engram not available
3. Cache the **Compact Rules** section and the **User Skills** trigger table
4. If no registry exists, warn user and proceed without project-specific standards

For each sub-agent launch:
1. Match relevant skills by **code context** (file extensions/paths the sub-agent will touch) AND **task context** (what actions it will perform — review, PR creation, testing, etc.)
2. Copy matching compact rule blocks into the sub-agent prompt as `## Project Standards (auto-resolved)`
3. Inject BEFORE the sub-agent's task-specific instructions

**Key rule**: inject compact rules TEXT, not paths. Sub-agents do NOT read SKILL.md files or the registry — rules arrive pre-digested. This is compaction-safe because each delegation re-reads the registry if the cache is lost.

### Skill Resolution Feedback

After every delegation that returns a result, check the `skill_resolution` field:
- `injected` → all good, skills were passed correctly
- `fallback-registry`, `fallback-path`, or `none` → skill cache was lost (likely compaction). Re-read the registry immediately and inject compact rules in all subsequent delegations.

This is a self-correction mechanism. Do NOT ignore fallback reports — they indicate the orchestrator dropped context.

### Sub-Agent Context Protocol

Sub-agents get a fresh context with NO memory. The orchestrator controls context access.

#### Non-SDD Tasks (general delegation)

- Read context: orchestrator searches engram (`mem_search`) for relevant prior context and passes it in the sub-agent prompt. Sub-agent does NOT search engram itself.
- Write context: sub-agent MUST save significant discoveries, decisions, or bug fixes to engram via `mem_save` before returning. Sub-agent has full detail — save before returning, not after.
- Always add to sub-agent prompt: `"If you make important discoveries, decisions, or fix bugs, save them to engram via mem_save with project: '{project}'."`
- Skills: orchestrator resolves compact rules from the registry and injects them as `## Project Standards (auto-resolved)` in the sub-agent prompt. Sub-agents do NOT read SKILL.md files or the registry — they receive rules pre-digested.

#### SDD Phases

Each phase has explicit read/write rules:

| Phase | Reads | Writes |
|-------|-------|--------|
| `sdd-explore` | nothing | `explore` |
| `sdd-propose` | exploration (optional) | `proposal` |
| `sdd-spec` | proposal (required) | `spec` |
| `sdd-design` | proposal (required) | `design` |
| `sdd-tasks` | spec + design (required) | `tasks` |
| `sdd-apply` | tasks + spec + design + **apply-progress (if exists)** | `apply-progress` |
| `sdd-verify` | spec + tasks + **apply-progress** | `verify-report` |
| `sdd-archive` | all artifacts | `archive-report` |

For phases with required dependencies, sub-agent reads directly from the backend — orchestrator passes artifact references (topic keys or file paths), NOT content itself.

#### Strict TDD Forwarding (MANDATORY)

When launching `sdd-apply` or `sdd-verify` sub-agents, the orchestrator MUST:

1. Search for testing capabilities: `mem_search(query: "sdd-init/{project}", project: "{project}")`
2. If the result contains `strict_tdd: true`:
   - Add to the sub-agent prompt: `"STRICT TDD MODE IS ACTIVE. Test runner: {test_command}. You MUST follow strict-tdd.md. Do NOT fall back to Standard Mode."`
   - This is NON-NEGOTIABLE. Do not rely on the sub-agent discovering this independently.
3. If the search fails or `strict_tdd` is not found, do NOT add the TDD instruction (sub-agent uses Standard Mode).

The orchestrator resolves TDD status ONCE per session (at first apply/verify launch) and caches it.

#### Apply-Progress Continuity (MANDATORY)

When launching `sdd-apply` for a continuation batch (not the first batch):

1. Search for existing apply-progress: `mem_search(query: "sdd/{change-name}/apply-progress", project: "{project}")`
2. If found, add to the sub-agent prompt: `"PREVIOUS APPLY-PROGRESS EXISTS at topic_key 'sdd/{change-name}/apply-progress'. You MUST read it first via mem_search + mem_get_observation, merge your new progress with the existing progress, and save the combined result. Do NOT overwrite — MERGE."`
3. If not found (first batch), no special instruction needed.

This prevents progress loss across batches. The sub-agent is responsible for read-merge-write, but the orchestrator MUST tell it that previous progress exists.

#### Engram Topic Key Format

| Artifact | Topic Key |
|----------|-----------|
| Project context | `sdd-init/{project}` |
| Exploration | `sdd/{change-name}/explore` |
| Proposal | `sdd/{change-name}/proposal` |
| Spec | `sdd/{change-name}/spec` |
| Design | `sdd/{change-name}/design` |
| Tasks | `sdd/{change-name}/tasks` |
| Apply progress | `sdd/{change-name}/apply-progress` |
| Verify report | `sdd/{change-name}/verify-report` |
| Archive report | `sdd/{change-name}/archive-report` |
| DAG state | `sdd/{change-name}/state` |

Sub-agents retrieve full content via two steps:
1. `mem_search(query: "{topic_key}", project: "{project}")` → get observation ID
2. `mem_get_observation(id: {id})` → full content (REQUIRED — search results are truncated)

### State and Conventions

Convention files under the agent's global skills directory (global) or `.agent/skills/_shared/` (workspace): `engram-convention.md`, `persistence-contract.md`, `openspec-convention.md`.

### Recovery Rule

- `engram` → `mem_search(...)` → `mem_get_observation(...)`
- `openspec` → read `openspec/changes/*/state.yaml`
- `none` → state not persisted — explain to user
<!-- /gentle-ai:sdd-orchestrator -->

<!-- gentle-ai:strict-tdd-mode -->
Strict TDD Mode: enabled
<!-- /gentle-ai:strict-tdd-mode -->
