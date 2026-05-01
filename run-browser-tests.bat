@echo off
REM Script para ejecutar browser tests de forma segura en Windows
REM Uso: run-browser-tests.bat [ruta_del_test]

echo.
echo 🧪 Ejecutando Browser Tests con Pest + Playwright
echo ==================================================
echo.

REM Verificar que npm run dev o build está corriendo
echo ⚠️  IMPORTANTE: Asegúrate de que el frontend está compilado
echo    Ejecuta en otra terminal: npm run dev
echo.

REM Limpiar configuración
echo 🧹 Limpiando caché de configuración...
php artisan config:clear

echo.
echo 🚀 Ejecutando tests...
echo.

REM Si se proporciona un argumento, ejecutar ese test específico
REM Si no, ejecutar todos los browser tests
if "%~1"=="" (
    php artisan test --env=testing tests/Browser/
) else (
    php artisan test --env=testing %1
)
