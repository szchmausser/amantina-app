#!/bin/bash

# Script para ejecutar browser tests de forma segura
# Uso: ./run-browser-tests.sh [ruta_del_test]

echo "🧪 Ejecutando Browser Tests con Pest + Playwright"
echo "=================================================="
echo ""

# Verificar que npm run dev o build está corriendo
echo "⚠️  IMPORTANTE: Asegúrate de que el frontend está compilado"
echo "   Ejecuta en otra terminal: npm run dev"
echo ""

# Limpiar configuración
echo "🧹 Limpiando caché de configuración..."
php artisan config:clear

echo ""
echo "🚀 Ejecutando tests..."
echo ""

# Si se proporciona un argumento, ejecutar ese test específico
# Si no, ejecutar todos los browser tests
if [ -z "$1" ]; then
    php artisan test --env=testing tests/Browser/
else
    php artisan test --env=testing "$1"
fi
