@echo off
echo Iniciando servidor del Sistema de Tickets...
echo.
echo El servidor estara disponible en: http://localhost:8000
echo.
echo Presiona Ctrl+C para detener el servidor
echo.
cd /d "%~dp0"
C:\xampp\php\php.exe -S localhost:8000
pause


