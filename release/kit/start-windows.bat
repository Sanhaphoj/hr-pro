@echo off
chcp 65001 >nul
title HR PRO V.2 - กำลังทำงาน
cd /d "%~dp0"

where php >nul 2>nul
if errorlevel 1 goto nophp
if not exist ".env" goto notinstalled

echo.
echo   HR PRO V.2 กำลังทำงานที่  http://127.0.0.1:8000
echo   เบราว์เซอร์จะเปิดขึ้นมาเองในไม่กี่วินาที
echo   ปิดหน้าต่างนี้เมื่อต้องการหยุดระบบ
echo.
start "" /b powershell -NoProfile -WindowStyle Hidden -Command "Start-Sleep -Seconds 3; Start-Process 'http://127.0.0.1:8000'"
php artisan serve --host=127.0.0.1 --port=8000
pause
exit /b

:nophp
echo.
echo   ไม่พบโปรแกรม PHP - ดูขั้นตอนที่ 1 ใน START-HERE.html
echo.
pause
exit /b 1

:notinstalled
echo.
echo   ยังไม่ได้ติดตั้ง - ดับเบิลคลิก install-windows.bat ก่อน
echo.
pause
exit /b 1
