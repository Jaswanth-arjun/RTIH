@echo off
echo Stopping TaskFlow servers...
taskkill /f /im php.exe 2>nul
taskkill /f /im mysqld.exe 2>nul
echo Done. All servers stopped.

