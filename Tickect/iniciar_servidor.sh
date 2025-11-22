#!/bin/bash
echo "Iniciando servidor del Sistema de Tickets..."
echo ""
echo "El servidor estará disponible en: http://localhost:8000"
echo ""
echo "Presiona Ctrl+C para detener el servidor"
echo ""
cd "$(dirname "$0")"
php -S localhost:8000

