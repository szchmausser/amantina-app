# Browser Tests - Guía de Ejecución

## Problema Identificado

Los **Browser tests** hacen peticiones HTTP reales al servidor de Laravel Herd, que por defecto usa el archivo `.env` apuntando a la base de datos de desarrollo (`amantina_app`).

Cuando se ejecutan los tests, PHPUnit intenta usar la base de datos de testing (`amantina_app_testing` configurada en `phpunit.xml`), pero el navegador hace peticiones al servidor que usa la base de datos de desarrollo, causando conflictos de migraciones y tablas duplicadas.

## Síntomas del Problema

- ✅ Los tests pasan cuando se ejecutan manualmente con el script correcto
- ❌ Los tests fallan o se quedan pegados cuando se ejecutan sin `--env=testing`
- ❌ Errores de "tabla ya existe" en PostgreSQL
- ❌ Errores de "tabla migrations no existe"

## ✅ Solución Correcta (Recomendada)

Usa el script **`run-browser-tests.bat`** que ya existe en la raíz del proyecto:

```batch
# Ejecutar todos los Browser tests
.\run-browser-tests.bat

# Ejecutar un test específico
.\run-browser-tests.bat tests/Browser/Admin/UserManagementTest.php
```

Este script usa `php artisan test --env=testing` que automáticamente carga el archivo `.env.testing` sin necesidad de hacer backups o copiar archivos.

## Alternativas (No Recomendadas)

### Opción 2: Comando Directo

Si prefieres ejecutar el comando directamente:

```powershell
php artisan test --env=testing tests/Browser --compact
```

### Opción 3: Script PowerShell (Obsoleto)

El script `tests/browser-test-setup.ps1` fue creado antes de descubrir el script `.bat` existente. No es necesario usarlo, pero funciona si prefieres ese enfoque.

## Configuración Actual

- **Base de datos de desarrollo:** `amantina_app` (usada por Laravel Herd con `.env`)
- **Base de datos de testing:** `amantina_app_testing` (configurada en `phpunit.xml` y `.env.testing`)
- **Servidor:** Laravel Herd en `amantina-app.test`
- **Framework de testing:** Pest 4 con Playwright
- **Script recomendado:** `run-browser-tests.bat` (usa `--env=testing`)

## Notas Técnicas

- Los **Feature tests** usan `RefreshDatabase` y funcionan correctamente porque no hacen peticiones HTTP
- Los **Browser tests** también usan `RefreshDatabase` para garantizar una base de datos limpia
- El flag `--env=testing` hace que Laravel cargue automáticamente `.env.testing`
- El servidor de Herd debe estar corriendo para que los Browser tests funcionen
- El frontend debe estar compilado (`npm run dev` o `npm run build`)

## ✅ Resultado de Tests

Última ejecución exitosa:
- **8 tests pasados** (20 assertions)
- **Duración:** ~40 segundos
- **Comando usado:** `.\run-browser-tests.bat tests/Browser/Admin/UserManagementTest.php`
