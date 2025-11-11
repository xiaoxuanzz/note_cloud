@echo off
setlocal enabledelayedexpansion

REM 定义项目根目录为当前目录的上一级目录
set "PROJECT_PATH=.."
set "OUTPUT_FILE=merged_file.txt"

REM 创建或清空输出文件
echo. > "%OUTPUT_FILE%"

echo 正在从上级目录: %PROJECT_PATH% 合并所有 .php 文件...
echo ======================================================== >> "%OUTPUT_FILE%"
echo 合并时间: %date% %time% >> "%OUTPUT_FILE%"
echo 合并目录: %PROJECT_PATH% >> "%OUTPUT_FILE%"
echo ======================================================== >> "%OUTPUT_FILE%"
echo. >> "%OUTPUT_FILE%"

REM 使用 for /r 递归遍历上级目录及其所有子目录
for /r "%PROJECT_PATH%" %%f in (*.php) do (
    echo. >> "%OUTPUT_FILE%"
    echo ======= 开始：%%f ======= >> "%OUTPUT_FILE%"
    echo. >> "%OUTPUT_FILE%"
    type "%%f" >> "%OUTPUT_FILE%"
    echo. >> "%OUTPUT_FILE%"
    echo ======= 结束：%%f ======= >> "%OUTPUT_FILE%"
    echo. >> "%OUTPUT_FILE%"
)

echo.
echo.