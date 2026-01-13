@echo off
REM FORCE RESTART APACHE WITH VHOSTS
REM Run as Administrator

echo.
echo ================================================
echo  FORCE APACHE RESTART WITH VHOSTS
echo ================================================
echo.

REM Check if running as admin
net session >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Must run as Administrator!
    pause
    exit /b 1
)

echo [1] Verifying Apache config...
C:\xampp\apache\bin\httpd.exe -t
echo.

echo [2] Checking currently running Apache processes...
tasklist | findstr /I "httpd.exe"
echo.

echo [3] Killing all Apache processes...
taskkill /F /IM httpd.exe /T >nul 2>&1
timeout /t 1 /nobreak >nul
echo [OK] All Apache processes stopped
echo.

echo [4] Verifying all processes killed...
tasklist | findstr /I "httpd.exe" >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo [Warning] Some processes still running, killing again...
    taskkill /F /IM httpd.exe /T >nul 2>&1
    timeout /t 2 /nobreak >nul
)
echo [OK] Ready to start
echo.

echo [5] Flushing DNS cache...
ipconfig /flushdns >nul 2>&1
echo [OK] DNS flushed
echo.

echo [6] Starting Apache service...
net start Apache2.4
echo.

timeout /t 3 /nobreak >nul

echo [7] Verifying Apache is running...
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
echo  TEST ACCESS
echo ================================================
echo.
echo Open browser and test:
echo.
echo 1. http://perpus.test/
echo    Should show: Perpustakaan Digital landing page
echo.
echo 2. If still showing XAMPP page:
echo    - Clear browser cache (Ctrl+Shift+Delete)
echo    - Close browser completely
echo    - Reopen and try again
echo.
echo 3. Check URL is correct:
echo    - CORRECT: http://perpus.test/
echo    - WRONG: http://localhost/
echo    - WRONG: http://127.0.0.1/
echo.
echo ================================================
echo.

pause
