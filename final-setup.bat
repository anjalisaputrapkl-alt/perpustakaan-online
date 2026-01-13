@echo off
REM Run as Administrator to add hosts entries and restart Apache

net session >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Must run as Administrator!
    echo Right-click and select "Run as Administrator"
    pause
    exit /b 1
)

echo.
echo ================================================
echo  PERPUSTAKAAN ONLINE - FINAL SETUP
echo ================================================
echo.

REM Add to hosts file if not already there
findstr /i "perpus.test" C:\Windows\System32\drivers\etc\hosts >nul
if %ERRORLEVEL% NEQ 0 (
    echo [1] Adding to hosts file...
    (
        echo.
        echo # Perpustakaan Online
        echo 127.0.0.1 perpus.test
        echo 127.0.0.1 sma1.perpus.test
        echo 127.0.0.1 smp5.perpus.test
        echo 127.0.0.1 sma3.perpus.test
    ) >> C:\Windows\System32\drivers\etc\hosts
    echo [OK] Hosts file updated
) else (
    echo [OK] Hosts file already configured
)
echo.

REM Flush DNS
echo [2] Flushing DNS...
ipconfig /flushdns >nul 2>&1
echo [OK] DNS flushed
echo.

REM Restart Apache
echo [3] Restarting Apache...
net stop Apache2.4 >nul 2>&1
timeout /t 2 /nobreak >nul
net start Apache2.4 >nul 2>&1
timeout /t 2 /nobreak >nul
echo [OK] Apache restarted
echo.

REM Verify
echo [4] Verifying Apache config...
C:\xampp\apache\bin\httpd.exe -t >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo [OK] Apache syntax is valid
) else (
    echo [ERROR] Apache has configuration errors
    pause
    exit /b 1
)
echo.

echo ================================================
echo  SETUP COMPLETE - READY TO USE
echo ================================================
echo.
echo NEXT STEPS:
echo.
echo 1. Clear browser cache (Ctrl+Shift+Delete)
echo 2. Close browser completely
echo 3. Open browser and go to:
echo    http://perpus.test/
echo.
echo You should see Perpustakaan Online landing page
echo (NOT XAMPP welcome page)
echo.
echo ================================================
echo.

pause
