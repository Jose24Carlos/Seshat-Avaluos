@echo off
REM Ejecuta Composer con el PHP de XAMPP (ajusta la ruta si tu XAMPP no está en C:\xampp).
set PHP_EXE=C:\xampp\php\php.exe
"%PHP_EXE%" "%~dp0composer.phar" %*
