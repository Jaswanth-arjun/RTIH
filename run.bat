@echo off
setlocal enabledelayedexpansion
cd /d "%~dp0"

echo ============================================================
echo   TaskFlow - Task Tracker Launcher
echo ============================================================

tasklist /fi "imagename eq mysqld.exe" 2>NUL | find /i /n "mysqld.exe" >NUL
if "!ERRORLEVEL!"=="0" (
    echo [*] MySQL database server is already running.
) else (
    echo [*] Starting MySQL database server...
    start "" /b "D:\xampp\mysql\bin\mysqld.exe" --defaults-file="%~dp0my.ini" --standalone
    ping 127.0.0.1 -n 3 >nul
)

echo [*] Starting TaskFlow Web App at http://127.0.0.1:8000 ...
echo [*] Open your browser and navigate to: http://127.0.0.1:8000
echo [*] Press Ctrl+C in this window to stop TaskFlow.
echo ============================================================

"D:\xampp\php\php.exe" -c "%~dp0php.ini" -S 127.0.0.1:8000 -t public public/router.php

echo.
echo [*] Stopping MySQL database server...
taskkill /f /im mysqld.exe 2>nul
echo [*] TaskFlow stopped cleanly.
