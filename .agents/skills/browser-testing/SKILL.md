---
name: browser-testing
description: >
  Guía completa de browser tests con Pest + Playwright en proyectos Laravel + Inertia + React.
  Cubre dos contextos: (1) escribir nuevos tests desde cero con criterio, estructura correcta
  y cobertura estratégica; (2) reparar tests existentes rotos después de un rediseño o
  refactor de UI. Los principios fundamentales aplican a ambos contextos.
license: MIT
compatibility: opencode
metadata:
  stack: laravel,inertia,react,tailwind,pest,playwright
  trigger: >
    escribir nuevos browser tests, o browser tests fallando después de un rediseño
    de UI o refactor de componentes
---

# Skill: browser-testing

Esta skill cubre dos contextos de uso:

- **Creación** — escribir browser tests nuevos desde cero con criterio, estructura y
  cobertura estratégica.
- **Reparación** — actualizar tests existentes que dejaron de pasar porque el frontend
  fue rediseñado o rebrandeado, sin cambiar la lógica de negocio ni las aserciones.

Los **principios fundamentales** de la Parte 1 aplican a ambos contextos sin excepción.

---

## PARTE 1 — Principios fundamentales

Antes de escribir o reparar cualquier test, estos principios deben estar claros.
Violarlos produce suites frágiles, no deterministas e imposibles de mantener.

---

### Principio 1 — Sin llamadas HTTP directas

**Los browser tests simulan a un usuario real. Un usuario real no hace peticiones HTTP.**
Ejecutar los tests contra el entorno de testing (`.env.testing`) garantiza que la base
de datos de producción no se vea afectada.

Está estrictamente prohibido usar dentro de un browser test:

- `Http::fake()`, `Http::get()`, `Http::post()` o cualquier facade HTTP de Laravel
- `$this->get()`, `$this->post()`, `$this->put()`, `$this->delete()` — son métodos
  de feature tests, no de browser tests
- `curl`, `fetch`, `axios` u otro cliente HTTP invocado directamente desde el test
- Llamadas a la API o endpoints internos para pre-poblar estado, verificar resultados
  o saltarse pasos de la UI

**Toda interacción debe ocurrir a través de la interfaz gráfica:**

```php
// ❌ MAL — llama al backend directamente, bypasea la UI
$this->post('/login', ['email' => $user->email, 'password' => 'password']);
$browser->visit('/dashboard')->assertSee('Bienvenido');

// ✅ BIEN — el usuario interactúa con el formulario
$browser->visit(route('login'))
        ->type('[name="email"]', $user->email)
        ->type('[name="password"]', 'password')
        ->click('[data-testid="login-btn"]')
        ->waitForText('Bienvenido');
```

```php
// ❌ MAL — verifica el resultado en la base de datos en lugar de en la UI
$browser->press('Guardar');
$this->assertDatabaseHas('posts', ['title' => 'Mi Post']);

// ✅ BIEN — verifica lo que el usuario vería en pantalla
$browser->press('Guardar')
        ->waitFor('[data-testid="success-toast"]')
        ->assertSee('Post guardado correctamente');
```

---

### Principio 2 — Cada test es completamente autosuficiente

> **Nunca dependas del orden de ejecución ni de datos que haya dejado otro test.**

Pest no garantiza el orden de ejecución entre archivos, y dentro del mismo archivo ese
orden puede variar entre corridas. Una suite que depende de un orden implícito falla de
manera no determinista y es imposible de depurar en CI.

Cada test debe:

1. **Crear sus propios datos** — factories o seeders dentro del `beforeEach`
2. **Limpiar tras de sí** automáticamente con `RefreshDatabase` o `DatabaseTransactions`
3. **Poder ejecutarse de forma aislada**, en cualquier orden, y pasar siempre

```php
// ❌ MAL — asume que otro test ya creó la categoría con id=1
it('registra un producto', function () {
    $this->actingAs(User::factory()->create())->browse(function (Browser $browser) {
        $browser->visit('/productos/crear')
                ->select('[name="categoria_id"]', 1) // ¿existe ese ID?
                ->press('Guardar');
    });
});

// ✅ BIEN — crea todos los datos necesarios dentro del propio test
it('registra un producto con su categoría', function () {
    $user      = User::factory()->create();
    $categoria = Categoria::factory()->create(['nombre' => 'Electrónica']);

    $this->actingAs($user)->browse(function (Browser $browser) use ($categoria) {
        $browser->visit(route('productos.crear'))
                ->type('[name="nombre"]', 'Laptop Pro')
                ->select('[name="categoria_id"]', $categoria->id)
                ->press('Guardar')
                ->waitForText('Producto registrado');
    });
});
```

---

### Principio 3 — UI completa solo cuando el flujo es lo que se prueba

Un test E2E no significa que cada paso del sistema deba ejecutarse via UI en cada test.
La UI completa se usa cuando el flujo en sí es la conducta que se quiere verificar.
Cuando el login o cualquier otro paso previo es solo una **precondición**, duplicarlo
en UI agrega ruido, lentitud y fragilidad sin ningún valor de cobertura adicional.

| Lo que se prueba | Mecanismo correcto |
|---|---|
| El flujo de login (formulario, validación, redirección) | UI completa |
| Cualquier funcionalidad que requiera sesión iniciada | `actingAs()` |
| Creación de datos que son precondición de la acción bajo prueba | Factory en `beforeEach` |

```php
// ✅ Prueba el flujo de LOGIN — usa UI porque eso es lo que se verifica
it('permite al usuario loguearse con credenciales válidas', function () {
    $user = User::factory()->create(['password' => bcrypt('secret')]);

    $this->browse(function (Browser $browser) use ($user) {
        $browser->visit(route('login'))
                ->type('[name="email"]', $user->email)
                ->type('[name="password"]', 'secret')
                ->click('[data-testid="login-btn"]')
                ->waitForText('Dashboard')
                ->assertPathIs('/dashboard');
    });
});

// ✅ Prueba REGISTRO DE PRODUCTO — el login es precondición, no el objeto de prueba
it('permite registrar un producto desde el panel', function () {
    $user      = User::factory()->create();
    $categoria = Categoria::factory()->create();

    $this->actingAs($user)->browse(function (Browser $browser) use ($categoria) {
        $browser->visit(route('productos.crear'))
                ->type('[name="nombre"]', 'Monitor 4K')
                ->select('[name="categoria_id"]', $categoria->id)
                ->press('Guardar')
                ->waitFor('[data-testid="success-toast"]')
                ->assertSee('Producto registrado');
    });
});
```

> **`actingAs()` no es un atajo incorrecto** — es la herramienta adecuada cuando el
> estado de autenticación es una precondición, no la conducta bajo prueba. Usarlo
> correctamente evita repetir pasos de UI que ya tienen su propio test dedicado.

---

### Principio 4 — Factories: solo antes de que el browser interactúe

Usar factories o seeders **antes** de que el browser inicie su primera interacción
es válido y es la forma correcta de establecer el estado inicial. Una vez que el
browser comenzó, cualquier creación de estado adicional debe ocurrir a través de la UI.

```php
// ✅ Factory ANTES de cualquier interacción del browser — correcto
it('muestra el listado de usuarios', function () {
    $admin = User::factory()->create();
    $users = User::factory(5)->create(); // estado inicial, antes del browser

    $this->actingAs($admin)->browse(function (Browser $browser) use ($users) {
        $browser->visit(route('usuarios.index'))
                ->assertSee($users->first()->name);
    });
});

// ❌ Factory DESPUÉS de que el browser ya comenzó — incorrecto
it('muestra nuevo usuario en el listado', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)->browse(function (Browser $browser) {
        $browser->visit(route('usuarios.index'));
        User::factory()->create(['name' => 'Nuevo Usuario']); // invisible para el usuario
        $browser->assertSee('Nuevo Usuario');
    });
});

// ✅ El usuario crea el dato a través de la UI — correcto
it('registra un nuevo usuario desde el panel', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)->browse(function (Browser $browser) {
        $browser->visit(route('usuarios.crear'))
                ->type('[name="name"]', 'Nuevo Usuario')
                ->type('[name="email"]', 'nuevo@ejemplo.com')
                ->press('Crear')
                ->waitForText('Nuevo Usuario');
    });
});
```

---

### Principio 5 — Compartir precondiciones con `beforeEach`

Cuando varios tests del mismo archivo comparten el mismo estado inicial, centralízalo
en `beforeEach`. Cada test recibe una copia fresca gracias a `RefreshDatabase`,
manteniendo el aislamiento sin duplicar código.

```php
beforeEach(function () {
    // Ejecutado antes de CADA test — cada uno parte de cero
    $this->user      = User::factory()->create();
    $this->categoria = Categoria::factory()->create(['nombre' => 'Electrónica']);
});

it('lista los productos de una categoría', function () {
    Producto::factory(3)->for($this->categoria)->create();

    $this->actingAs($this->user)->browse(function (Browser $browser) {
        $browser->visit(route('productos.index'))
                ->assertSee('Electrónica');
    });
});

it('registra un nuevo producto', function () {
    $this->actingAs($this->user)->browse(function (Browser $browser) {
        $browser->visit(route('productos.crear'))
                ->type('[name="nombre"]', 'Laptop Pro')
                ->select('[name="categoria_id"]', $this->categoria->id)
                ->press('Guardar')
                ->waitForText('Producto registrado');
    });
});
```

---

### Principio 6 — Seeders vs factories

| Tipo de dato | Herramienta | Cuándo |
|---|---|---|
| Datos base del sistema (roles, permisos, configuraciones globales) | `Seeder` en `beforeEach` | Cuando la app no puede funcionar sin ellos |
| Datos específicos del escenario de prueba | `Factory` en el test o `beforeEach` | La mayoría de los casos |
| Datos masivos para pruebas de paginación o volumen | `Factory` con `->count()` | Cuando la cantidad importa para la conducta |

> **Regla práctica:** si el dato existiría en producción desde el primer deploy
> (roles, configuraciones, tipos), usa un seeder. Si es un registro creado por un
> usuario durante el uso normal de la app, usa una factory.

```php
beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class); // datos estructurales del sistema
    $this->admin = User::factory()->withRole('admin')->create();
});
```

---

## PARTE 2 — Crear tests desde cero

---

### 2.1 ¿Cuándo escribir un browser test?

Los browser tests son los más costosos de la pirámide de testing: lentos, con más
superficie de fallo y mayor costo de mantenimiento. No todo debe tener un browser test.

```
         /\
        /  \       Browser tests (E2E)
       /    \      Pocos, flujos críticos de negocio
      /------\
     /        \    Feature / Integration tests
    /          \   Lógica de negocio, respuestas HTTP, permisos
   /------------\
  /              \  Unit tests
 /                \ Clases, métodos, transformaciones, cálculos
/------------------\
```

**Escribe un browser test cuando:**

- El flujo involucra múltiples pasos de UI que deben funcionar integrados
  (formulario multistep, wizard, flujo de checkout)
- La conducta depende de JavaScript para funcionar correctamente
  (componentes reactivos, validación en cliente, modales, dropdowns)
- Es un flujo crítico de negocio donde un fallo sería catastrófico
  (login, registro, pago, emisión de documento)
- Quieres verificar que la integración Inertia + React + backend produce el resultado
  correcto de punta a punta

**No escribas un browser test cuando:**

- La lógica es puramente de backend (un feature test es más rápido y preciso)
- Solo quieres verificar que un endpoint devuelve los datos correctos (usa feature test)
- La conducta no depende de JavaScript (un feature test alcanza)
- Ya tienes un feature test cubriendo esa lógica y el browser test no agrega cobertura
  nueva — duplicar cobertura en distintas capas es mantenimiento sin beneficio

---

### 2.2 Qué cubrir en un browser test

**Cubre:**

- El **happy path** — el flujo completo cuando todo funciona como se espera
- Los **flujos de error visibles en UI** — validaciones de formulario, mensajes de
  error que el usuario ve, redirecciones por acceso denegado
- Las **interacciones de JavaScript** que no pueden probarse en feature tests
  (confirmar que un modal se abre, que un select dinámico carga opciones, que un
  componente reactivo actualiza el DOM)
- Los **flujos críticos de negocio** end-to-end, aunque ya tengan cobertura en otras capas

**No cubras:**

- Lógica de negocio que ya está cubierta en unit tests — el browser test no es el
  lugar para verificar que un descuento se calcula bien
- Contenido de emails, notificaciones o jobs en background — eso va en feature tests
- Respuestas exactas de la API — usa feature tests para eso
- Cada variante de validación de un campo — prueba que la validación funciona (un caso),
  no todos los mensajes posibles

---

### 2.3 Configuración del entorno

Los browser tests necesitan un servidor HTTP corriendo y una base de datos separada.

**`.env.testing`** — nunca apuntes a la base de datos de desarrollo:

```dotenv
APP_ENV=testing
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=:memory:
# O una base de datos SQLite en disco para poder inspeccionarla
# DB_DATABASE=/absolute/path/to/testing.sqlite
```

**Levantar el servidor antes de correr los tests:**

```bash
# En una terminal separada
php artisan serve --env=testing

# O configurar el servidor en el setUp de la suite base
# (ver documentación de pest-plugin-browser para automatic server setup)
```

**`RefreshDatabase` en la clase base del browser test:**

```php
// tests/Browser/BrowserTestCase.php
abstract class BrowserTestCase extends TestCase
{
    use RefreshDatabase;
}
```

---

### 2.4 Organización de archivos y nomenclatura

```
tests/
└── Browser/
    ├── Auth/
    │   ├── LoginTest.php
    │   ├── RegisterTest.php
    │   └── PasswordResetTest.php
    ├── Productos/
    │   ├── ProductoCreacionTest.php
    │   ├── ProductoEdicionTest.php
    │   └── ProductoEliminacionTest.php
    ├── screenshots/          ← generados automáticamente al fallar
    └── console/              ← logs de consola del browser
```

**Reglas de nomenclatura:**

- Un archivo por **entidad o flujo** — no un archivo con todos los tests de la app
- Nombre del archivo en `PascalCase` terminado en `Test.php`
- Nombre del test en minúsculas, descriptivo, en el idioma del equipo:

```php
it('muestra error de validación cuando el email está vacío');
it('redirige al dashboard después de un login exitoso');
it('permite al administrador eliminar un producto');
it('no permite acceder al panel sin autenticación');
```

El nombre del test debe poder leerse como una oración que describe la conducta, no la
implementación. Si el nombre incluye palabras como "click" o "type", es una señal de
que describe pasos en lugar de conducta.

---

### 2.5 Anatomía de un browser test — el patrón AAA

Todo test bien formado tiene tres partes: **Arrange** (preparar), **Act** (actuar),
**Assert** (verificar). En browser tests se mapean así:

```php
it('permite registrar un nuevo producto', function () {

    // ─── ARRANGE — preparar el estado inicial ──────────────────────────────
    $user      = User::factory()->create();
    $categoria = Categoria::factory()->create(['nombre' => 'Electrónica']);
    // → Todo lo necesario para que el test pueda ejecutarse está listo aquí.
    // → No hay interacción con el browser todavía.

    $this->actingAs($user)->browse(function (Browser $browser) use ($categoria) {

        // ─── ACT — ejecutar la acción que se quiere verificar ────────────
        $browser->visit(route('productos.crear'))
                ->type('[name="nombre"]', 'Laptop Pro')
                ->type('[name="precio"]', '1500')
                ->select('[name="categoria_id"]', $categoria->id)
                ->press('Guardar')
                ->waitFor('[data-testid="success-toast"]');
        // → Solo los pasos necesarios para llegar al estado que se quiere
        // → verificar. Sin pasos extra, sin navegación innecesaria.

        // ─── ASSERT — verificar lo que el usuario vería ──────────────────
        $browser->assertSee('Producto registrado')
                ->assertPathIs('/productos')
                ->assertSee('Laptop Pro');
        // → Verificaciones sobre lo visible en pantalla.
        // → Sin assertDatabaseHas — eso es responsabilidad del feature test.
    });
});
```

---

### 2.6 Flujos de error: validaciones visibles en UI

Probar que la UI muestra errores de validación es responsabilidad del browser test,
porque requiere que el JavaScript del cliente y el backend estén integrados:

```php
it('muestra errores de validación cuando los campos requeridos están vacíos', function () {
    $this->actingAs(User::factory()->create())->browse(function (Browser $browser) {
        $browser->visit(route('productos.crear'))
                ->press('Guardar')                           // enviar sin llenar campos
                ->waitFor('[data-testid="field-error"]')     // esperar que aparezcan errores
                ->assertSee('El nombre es obligatorio')
                ->assertSee('El precio es obligatorio');
    });
});

it('no permite acceder al panel de administración sin autenticación', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit(route('admin.dashboard'))
                ->assertPathIs('/login');                    // debe redirigir al login
    });
});
```

---

### 2.7 Page Objects — para suites que crecen

Cuando varios tests comparten la misma secuencia de interacciones con una página,
extraer esa lógica a un Page Object evita duplicación y centraliza el mantenimiento
de selectores:

```php
// tests/Browser/Pages/ProductoCreacionPage.php
namespace Tests\Browser\Pages;

use Laravel\Dusk\Page;

class ProductoCreacionPage extends Page
{
    public function url(): string
    {
        return route('productos.crear');
    }

    public function llenarFormulario(Browser $browser, array $datos): void
    {
        $browser->type('[name="nombre"]', $datos['nombre'])
                ->type('[name="precio"]', $datos['precio'])
                ->select('[name="categoria_id"]', $datos['categoria_id']);
    }

    public function guardar(Browser $browser): void
    {
        $browser->press('Guardar')
                ->waitFor('[data-testid="success-toast"]');
    }
}

// Uso en el test
it('registra un producto', function () {
    $user      = User::factory()->create();
    $categoria = Categoria::factory()->create();

    $this->actingAs($user)->browse(function (Browser $browser) use ($categoria) {
        $page = new ProductoCreacionPage();
        $browser->visit($page->url());
        $page->llenarFormulario($browser, [
            'nombre'       => 'Laptop Pro',
            'precio'       => '1500',
            'categoria_id' => $categoria->id,
        ]);
        $page->guardar($browser);
        $browser->assertSee('Laptop Pro');
    });
});
```

> Usa Page Objects cuando la misma página aparece en tres o más tests distintos.
> Para tests aislados o suites pequeñas, la complejidad extra no vale la pena.

---

## PARTE 3 — Reparar tests existentes

---

### 3.1 Modelo mental antes de empezar

Los browser tests fallan después de un rediseño por una de estas cuatro razones:

| Causa raíz | Síntoma |
|---|---|
| **Selector roto** | Elemento no encontrado: botón, input, link o texto cambió |
| **Flujo modificado** | Un paso fue agregado, eliminado o reordenado |
| **Problema de timing** | Inertia o un componente async ahora necesita espera explícita |
| **URL / ruta cambiada** | `visit()` apunta a una página que ya no existe o fue renombrada |

Siempre diagnostica primero. Nunca reescribas selectores a ciegas.

---

### 3.2 Establecer la línea base — capturar todos los fallos

```bash
# Ejecutar solo el suite de browser tests y capturar el output
php artisan test --filter=Browser 2>&1 | tee /tmp/browser-failures.txt

# O si los tests están en una ruta específica
./vendor/bin/pest tests/Browser 2>&1 | tee /tmp/browser-failures.txt
```

Lee `/tmp/browser-failures.txt` completo antes de tocar cualquier archivo.
Agrupa los fallos por tipo de error: elemento no encontrado, timeout, URL inesperada,
aserción fallida.

---

### 3.3 Entender qué cambió — diff de las vistas

```bash
# Ver todos los archivos React/TypeScript modificados recientemente
git log --oneline --diff-filter=M -- 'resources/js/**' | head -30

# Ver el diff concreto de un componente
git diff HEAD~1 -- resources/js/pages/Auth/Login.tsx

# Listar todas las páginas de Inertia
find resources/js/pages -name '*.tsx' | sort
```

Qué buscar en los diffs:
- Texto de botón/link cambiado → actualizar `->clickLink()` / `->press()`
- `id`/`name`/`placeholder` de input cambiado → actualizar selectores `->type()`
- Ruta o `<Link href>` cambiada → actualizar `->visit(route('...'))`
- Un modal ahora envuelve el contenido → agregar el paso para abrirlo
- Un formulario fue dividido en pasos → agregar la navegación entre pasos
- Cambio de librería de componentes (Headless UI → Radix UI) → selectores cambiaron

---

### 3.4 Inspeccionar la página en vivo para encontrar nuevos selectores

**Opción A — volcar el HTML durante un test fallido** (más rápido)

```php
$browser->visit(route('login'))
    ->dump()                          // imprime el HTML en consola
    ->screenshot('debug-login');      // guarda PNG en tests/Browser/screenshots/
```

**Opción B — codegen de Playwright** (ideal para flujos complejos)

```bash
npx playwright codegen http://localhost:8000/login
```

**Opción C — inspeccionar el componente React directamente**

```bash
grep -n 'data-testid\|aria-label\|<button\|<input\|<a ' \
    resources/js/pages/Auth/Login.tsx
```

---

### 3.5 Prioridad de selectores

Usa selectores en este orden de preferencia (más robusto → menos robusto):

1. **`data-testid`** — inmune a refactors visuales
   `->click('[data-testid="submit-btn"]')`
   Si no existe, agrégalo al componente. Esa es la corrección correcta, no el workaround.

2. **ARIA role + nombre accesible** — sobrevive cambios de clases CSS
   `->click('button[aria-label="Guardar cambios"]')`

3. **Texto visible** — válido para links y botones con copy estable
   `->clickLink('Iniciar sesión')`
   `->click('button:has-text("Guardar")')`

4. **Atributo `name` en inputs** — estable si los campos no se renombran
   `->type('[name="email"]', 'usuario@ejemplo.com')`

5. **Atributo `id`** — solo si es semántico y estable
   `->type('#email', 'usuario@ejemplo.com')`

6. **Clase CSS** — último recurso; se rompe con cualquier cambio de Tailwind/shadcn.
   Si aparece en tests existentes, reemplázalo por uno de los anteriores.

---

### 3.6 Patrones de fallo más comunes y sus correcciones

**Texto de botón o link cambió:**

```php
// ANTES
$browser->clickLink('Login');
// DESPUÉS
$browser->clickLink('Iniciar sesión');
// o más robusto:
$browser->click('[data-testid="login-link"]');
```

**`id`/`name` del input cambió:**

```php
// ANTES
$browser->type('#email-address', $user->email);
// DESPUÉS — name es más estable que id
$browser->type('[name="email"]', $user->email);
```

**Radix UI / shadcn — Select, Dialog, DropdownMenu:**

Radix UI renderiza portals y usa atributos `[data-radix-*]`. Un `.click('select')`
directo no funciona sobre un Radix Select.

```php
// Radix Select
$browser->click('[data-testid="role-select-trigger"]')
        ->waitFor('[role="listbox"]')
        ->click('[role="option"]:has-text("Admin")');

// Radix Dialog
$browser->click('[data-testid="open-dialog-btn"]')
        ->waitFor('[role="dialog"]')
        ->type('[role="dialog"] [name="title"]', 'Nuevo ítem')
        ->click('[role="dialog"] [data-testid="confirm-btn"]')
        ->waitUntilMissing('[role="dialog"]');

// Radix DropdownMenu
$browser->click('[data-testid="actions-menu"]')
        ->waitFor('[role="menu"]')
        ->click('[role="menuitem"]:has-text("Eliminar")');
```

**Inertia — esperar que la página se estabilice:**

```php
// Siempre espera después de una navegación
$browser->clickLink('Dashboard')
        ->waitForText('Bienvenido')
        ->assertPathIs('/dashboard');

$browser->press('Guardar')
        ->waitFor('[data-testid="success-toast"]')
        ->assertSee('Guardado correctamente');
```

**Toast que cambió de componente:**

```php
// Antes: alert inline
$browser->assertSee('Perfil actualizado.');
// Después: toast en portal
$browser->waitFor('[role="status"]')
        ->assertSeeIn('[role="status"]', 'Perfil actualizado.');
```

---

### 3.7 Agregar `data-testid` a componentes React

Cuando no existe un selector estable, la corrección correcta es agregarlo.
No es deuda técnica — es testabilidad planificada.

```tsx
// shadcn/ui: los componentes hacen spread de {...props}, simplemente pasa el atributo
<Button data-testid="submit-btn" type="submit">Guardar</Button>

// Radix primitives: agrégalo al trigger
<Select.Trigger data-testid="role-select-trigger">
  <Select.Value />
</Select.Trigger>

// Componente propio: incluye el prop explícitamente
interface Props extends React.HTMLAttributes<HTMLDivElement> {
  'data-testid'?: string;
}
```

---

### 3.8 Flujo de reparación — archivo por archivo

```
1. Leer el archivo de test completo
2. Ejecutar SOLO ese archivo:
   ./vendor/bin/pest tests/Browser/LoginTest.php --verbose
3. Leer el error: ¿qué selector o aserción falló?
4. Encontrar el componente React: resources/js/pages/...
5. Identificar el selector correcto (sección 3.4 - 3.5)
6. Actualizar el test — conservar la lógica de aserción, solo cambiar la interacción
7. Ejecutar el test para confirmar que pasa
8. Pasar al siguiente fallo
```

No arregles múltiples archivos simultáneamente si comparten páginas — arregla uno,
ejecútalo, luego continúa. Evita perseguir fallos fantasma.

---

## PARTE 4 — Ejecución y depuración

```bash
# Ejecutar toda la suite de browser tests
./vendor/bin/pest tests/Browser

# Ejecutar un solo archivo
./vendor/bin/pest tests/Browser/LoginTest.php

# Ejecutar un solo test por nombre
./vendor/bin/pest --filter "el usuario puede iniciar sesión"

# Con browser visible (ver qué está pasando)
PLAYWRIGHT_HEADED=true ./vendor/bin/pest tests/Browser/LoginTest.php

# En cámara lenta para depurar paso a paso
PLAYWRIGHT_SLOW_MO=500 ./vendor/bin/pest tests/Browser/LoginTest.php

# Capturar screenshot en un punto específico del test
$browser->screenshot('estado-antes-de-guardar');
```

---

## PARTE 5 — Checklist

### Al crear un test nuevo

- [ ] El test verifica una conducta de negocio, no pasos de implementación
- [ ] El nombre describe qué se verifica, no cómo
- [ ] Se usa `actingAs()` cuando el login es precondición, no lo que se prueba
- [ ] Todos los datos necesarios se crean en `beforeEach` o al inicio del test
- [ ] El test puede ejecutarse solo, en cualquier orden, y pasar siempre
- [ ] Las aserciones son sobre lo que el usuario ve en pantalla, no sobre la base de datos
- [ ] Se usa `->waitFor()` / `->waitForText()` en lugar de `sleep()` para esperas
- [ ] Los selectores usan `data-testid` o atributos semánticos, no clases CSS
- [ ] Si no existen selectores estables, se agrega `data-testid` al componente

### Al reparar un test existente

- [ ] Se diagnosticó la causa raíz antes de tocar el código
- [ ] Solo cambió la interacción — la lógica de aserción se conservó
- [ ] No quedan selectores por clase CSS en el código de los tests
- [ ] Los `data-testid` nuevos están commiteados junto con los tests
- [ ] El test pasa ejecutado de forma aislada: `./vendor/bin/pest tests/Browser/ElTest.php`
- [ ] Todos los browser tests pasan: `php artisan test --filter=Browser`
- [ ] Todos los feature y unit tests siguen en verde: `php artisan test`

### Prohibiciones que aplican siempre

- [ ] Sin llamadas HTTP directas (`Http::`, `$this->post()`, etc.)
- [ ] Sin `assertDatabaseHas` / `assertDatabaseMissing` en browser tests
- [ ] Sin factories intercaladas entre interacciones del browser
- [ ] Sin dependencia del orden de ejecución entre tests
- [ ] Sin `sleep()` — siempre `->waitFor()` o `->waitForText()`

---

## PARTE 6 — Referencia rápida de métodos

| Qué se quiere hacer | Método |
|---|---|
| Navegar a una URL | `->visit(route('name'))` |
| Hacer clic en un link por texto | `->clickLink('Texto')` |
| Hacer clic en cualquier elemento | `->click('selector')` |
| Escribir en un input | `->type('[name="campo"]', 'valor')` |
| Seleccionar en un `<select>` nativo | `->select('[name="campo"]', 'valor')` |
| Marcar un checkbox | `->check('[name="remember"]')` |
| Esperar que aparezca un elemento | `->waitFor('[data-testid="x"]')` |
| Esperar que aparezca un texto | `->waitForText('Texto esperado')` |
| Esperar que desaparezca un elemento | `->waitUntilMissing('[role="dialog"]')` |
| Verificar la ruta actual | `->assertPathIs('/dashboard')` |
| Verificar que un texto es visible | `->assertSee('Texto')` |
| Verificar que un texto NO es visible | `->assertDontSee('Error')` |
| Verificar el valor de un input | `->assertInputValue('[name="email"]', 'x@y.com')` |
| Volcar el HTML de la página | `->dump()` |
| Tomar un screenshot | `->screenshot('nombre')` |