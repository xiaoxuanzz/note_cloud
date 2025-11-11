@echo off

set "PROJECT_PATH=.."
set "OUTPUT_FILE=tree.txt"

echo.

tree "%PROJECT_PATH%" /f > "%OUTPUT_FILE%.tmp"

powershell -Command "(Get-Content -Path '%OUTPUT_FILE%.tmp' -Encoding Default) | Set-Content -Path '%OUTPUT_FILE%' -Encoding UTF8"

del "%OUTPUT_FILE%.tmp"

echo.