@echo off
chcp 65001 >nul
title HR PRO V.2 - ติดตั้ง
cd /d "%~dp0"

where php >nul 2>nul
if errorlevel 1 goto nophp

php install.php %*
echo.
pause
exit /b

:nophp
echo.
echo   ไม่พบโปรแกรม PHP บนเครื่องนี้
echo   ให้ติดตั้ง PHP ก่อน ตามขั้นตอนที่ 1 ในคู่มือ START-HERE.html ที่กำลังเปิดขึ้นมา
echo   ติดตั้งเสร็จแล้ว ปิดหน้าต่างนี้ แล้วดับเบิลคลิก install-windows.bat อีกครั้ง
echo.
start "" "%~dp0START-HERE.html"
pause
exit /b 1
