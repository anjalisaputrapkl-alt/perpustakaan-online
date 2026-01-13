@echo off
REM Start Apache directly (not as service)

echo.
echo ================================================
echo  STARTING APACHE DIRECTLY
echo ================================================
echo.

REM Kill any running instances
taskkill /F /IM httpd.exe /T >nul 2>&1
timeout /t 2 /nobreak >nul

REM Flush DNS
echo [1] Flushing DNS...
ipconfig /flushdns >nul 2>&1
echo [OK]
echo.

REM Start Apache directly
echo [2] Starting Apache server...
start "" "C:\xampp\apache\bin\httpd.exe"
timeout /t 3 /nobreak >nul
echo [OK] Apache started
echo.

REM Verify
tasklist | findstr /I "httpd.exe" >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo [OK] Apache is running
) else (
    echo [ERROR] Apache failed to start
    pause
    exit /b 1
)
echo.

echo ================================================
echo  READY - NOW TEST IN BROWSER
echo ================================================
echo.
echo Open browser and go to:
echo   http://perpus.test/
echo.
echo Should show: Perpustakaan Digital landing page
echo (NOT XAMPP welcome page)
echo.
echo If still showing XAMPP:
echo 1. Make sure URL is http://perpus.test/
echo    (NOT http://localhost/ or http://127.0.0.1/)
echo.
echo 2. Clear browser cache (Ctrl+Shift+Delete)
echo.
echo 3. Close and reopen browser
echo.
echo ================================================
echo.

pause
