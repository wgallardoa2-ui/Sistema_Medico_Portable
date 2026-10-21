@echo off
title Sistema Medico Portable
color 0A
echo ===================================================
echo     INICIANDO SISTEMA MEDICO PORTABLE PROFESIONAL
echo ===================================================

:: Iniciar el servidor PHP interno apuntando al puerto 8000
start /B php\php.exe -S localhost:8000 >nul 2>&1

:: Esperar 1.5 segundos
timeout /t 2 /nobreak >nul

:: Abrir el navegador predeterminado en la aplicación
start http://localhost:8000/index.php

exit