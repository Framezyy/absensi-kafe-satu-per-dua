@echo off
cd /d "D:\Project TA Absensi\web"
echo.
echo ========================================
echo   Server berjalan di:
echo   http://localhost:8000
echo.
echo   Tekan Ctrl+C untuk berhenti
echo ========================================
echo.
"C:\Users\ASUS\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe" artisan serve --host=0.0.0.0