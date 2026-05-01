# 🚀 Browser Testing - Guía Rápida

## ✅ Instalación Completada

Ya está todo instalado en este proyecto:
- ✅ Pest 4.6.3 con plugin browser
- ✅ Playwright con Chromium
- ✅ Configuración en `tests/Pest.php`
- ✅ Base de datos de testing: `amantina_app_testing` (PostgreSQL)
- ✅ Archivo `.env.testing` configurado

## 🎯 Ejecutar Tests

### Comando Principal (Modo Visual - Navegador Visible)

```bash
php artisan config:clear && php artisan test --env=testing tests/Browser/Auth/LoginTest.php
```

**Qué hace:**
- ✅ Limpia caché de configuración
- ✅ Usa base de datos de testing (NO toca producción)
- ✅ Abre el navegador Chromium (modo headed)
- ✅ Ejecuta el test con pausas visibles
- ✅ Cierra el navegador automáticamente al finalizar

### Ejecutar Todos los Browser Tests

```bash
php artisan config:clear && php artisan test --env=testing tests/Browser/
```

### Ejecutar con Filtro

```bash
php artisan config:clear && php artisan test --env=testing --filter="admin puede iniciar sesión"
```

### Modo Debug (Pausa en Fallos)

```bash
php artisan config:clear && php artisan test --env=testing --debug tests/Browser/Auth/LoginTest.php
```

Si el test falla, pausará y mantendrá el navegador abierto para inspección.

## 📝 Estructura de un Test

```php
<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('puede hacer algo', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->visit('/ruta')
        ->wait(2)                           // Pausa de 2 segundos
        ->assertSee('Texto Esperado')
        ->wait(1)
        ->fill('email', 'test@example.com') // Llenar campo
        ->wait(0.5)
        ->press('Guardar')                  // Click en botón
        ->wait(2)
        ->assertPathIs('/success')
        ->assertNoJavaScriptErrors();
});
```

## 🎨 React Select (Componentes Complejos)

Para interactuar con React Select:

```php
// 1. Click para abrir el dropdown
->click('#company_id_select')
->wait(0.5)

// 2. Escribir para filtrar
->typeSlowly('#company_id_select', 'COMP1234')

// 3. Esperar a que aparezca la opción
->waitForText('COMP1234')
->wait(0.5)

// 4. Navegar y seleccionar con teclado
->keys('#company_id_select', 'ArrowDown')
->keys('#company_id_select', 'Enter')
->wait(1)
```

## ⏱️ Control de Velocidad

```php
->wait(1)                              // Pausa de 1 segundo
->wait(0.5)                            // Pausa de 500ms
->typeSlowly('#campo', 'texto')        // Tipeo letra por letra (como humano)
->wait(999999)                         // Mantener navegador abierto indefinidamente
```

## 🔍 Mantener Navegador Abierto para Inspección

Agregar al final del test:

```php
->assertSee('Éxito')
->wait(999999);  // Mantener abierto hasta presionar Ctrl+C
```

Para cerrar: Presionar `Ctrl+C` en la terminal.

## ⚠️ IMPORTANTE: Base de Datos

**Los browser tests usan una base de datos SEPARADA:**

- **Desarrollo**: `amantina_app` (PostgreSQL)
- **Testing**: `amantina_app_testing` (PostgreSQL)

**SIEMPRE usar `--env=testing`** para evitar modificar datos de desarrollo.

### Verificar que la Base de Datos de Testing Existe

```bash
psql -U postgres -c "\l" | grep amantina_app_testing
```

Si no existe, crearla:

```bash
psql -U postgres -c "CREATE DATABASE amantina_app_testing;"
```

## 🐛 Troubleshooting

### Problema: El test se cuelga o timeout

**Solución**: Asegúrate de que el frontend está compilado:

```bash
npm run build
# O mantén corriendo en otra terminal:
npm run dev
```

### Problema: Error "Connection refused"

**Solución**: Verifica que Laravel Herd está corriendo:

```bash
curl http://amantina-app.test
```

### Problema: El navegador no se abre

**Solución**: Verifica que `pest()->browser()->headed();` está en `tests/Pest.php`.

### Problema: El test va muy rápido

**Solución**: Agregar más `->wait()` entre acciones:

```php
->click('#boton')
->wait(2)  // Agregar pausa
->assertSee('Resultado')
```

### Problema: Tests modifican la base de datos de desarrollo

**Solución**: SIEMPRE usar `--env=testing`:

```bash
php artisan config:clear && php artisan test --env=testing tests/Browser/
```

## 📊 Tests Disponibles

```
tests/Browser/
├── Auth/
│   └── LoginTest.php              (5 tests) ✅
├── Admin/
│   ├── UserManagementTest.php     (8 tests) ✅
│   ├── AcademicYearTest.php       (5 tests) ✅
│   ├── AcademicStructureTest.php  (6 tests) ✅
│   ├── EnrollmentTest.php         (5 tests) ✅
│   ├── FieldSessionTest.php       (7 tests) ✅
│   ├── AttendanceTest.php         (5 tests) ✅
│   └── CatalogTest.php            (9 tests) ✅
├── Dashboard/
│   └── DashboardTest.php          (5 tests) ✅
└── Profile/
    └── ProfileTest.php            (4 tests) ✅

Total: 61 browser tests
```

## 🎯 Próximos Pasos

1. **Ejecutar un test simple**:
   ```bash
   php artisan config:clear && php artisan test --env=testing tests/Browser/Auth/LoginTest.php --filter="admin puede iniciar sesión"
   ```

2. **Ver el navegador en acción** (el test tiene pausas para que puedas ver cada paso)

3. **Completar tests skip**peados** agregando `data-testid` a componentes React

4. **Crear tests de flujo completo** (desde crear año hasta registrar asistencia)

## 📚 Documentación Completa

Ver `tests/Browser/README.md` para documentación detallada.

## 🔑 Comandos Más Usados

```bash
# Ejecutar un test específico
php artisan config:clear && php artisan test --env=testing tests/Browser/Auth/LoginTest.php

# Ejecutar todos los browser tests
php artisan config:clear && php artisan test --env=testing tests/Browser/

# Ejecutar con filtro
php artisan config:clear && php artisan test --env=testing --filter="admin puede"

# Modo debug (pausa en fallos)
php artisan config:clear && php artisan test --env=testing --debug tests/Browser/Auth/LoginTest.php

# Ver lista de tests sin ejecutar
./vendor/bin/pest --list tests/Browser/
```

## ✨ Tips

- **Usa `wait()` generosamente** - mejor lento y visible que rápido y confuso
- **Usa `typeSlowly()` en lugar de `type()`** - más realista y fácil de seguir
- **Agrega `->wait(999999)` al final** para inspeccionar el resultado
- **Usa `--debug`** cuando un test falle para ver qué pasó
- **Siempre ejecuta con `--env=testing`** para proteger tus datos

---

**¿Listo para empezar?**

```bash
php artisan config:clear && php artisan test --env=testing tests/Browser/Auth/LoginTest.php
```
