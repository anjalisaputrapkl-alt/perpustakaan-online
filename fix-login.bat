@echo off
REM Quick Fix for Perpustakaan Online Login Issue
REM Run this as Administrator

echo.
echo ========================================
echo Perpustakaan Online - Apache Fix
echo ========================================
echo.

REM Flush DNS
echo [1] Flushing DNS Cache...
ipconfig /flushdns
echo.

REM Restart Apache
echo [2] Stopping Apache...
net stop Apache2.4 >nul 2>&1
timeout /t 2 /nobreak

echo [3] Starting Apache...
net start Apache2.4
timeout /t 2 /nobreak

REM Verify
echo.
echo [4] Verifying Apache Configuration...
C:\xampp\apache\bin\httpd.exe -t

echo.
echo ========================================
echo Done! 
echo ========================================
echo.
echo Try accessing: http://perpus.test/
echo.
pause
