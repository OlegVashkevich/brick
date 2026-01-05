@echo off

rem -------------------------------------------------------------
rem  Bricks Analyze command line bootstrap script for Windows.
rem -------------------------------------------------------------

@setlocal

set OVBINPATH=%~dp0

if "%PHP_COMMAND%" == "" set PHP_COMMAND=php.exe

"%PHP_COMMAND%" "%OVBINPATH%brick" %*

@endlocal