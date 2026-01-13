@echo off
REM =====================================================
REM PERPUSTAKAAN ONLINE - MANUAL SETUP GUIDE
REM =====================================================

echo.
echo =====================================================
echo    PERPUSTAKAAN ONLINE - MANUAL SETUP
echo =====================================================
echo.
echo This script will guide you through manual setup steps.
echo.

REM Check if running as administrator
net session >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] This script must run as Administrator!
    echo Please right-click and "Run as Administrator"
    pause
    exit /b 1
)

echo [1] HOSTS FILE
echo ==========================================
echo File: C:\Windows\System32\drivers\etc\hosts
echo.
echo Add these lines (if not already present):
echo.
echo 127.0.0.1 perpus.test
echo 127.0.0.1 sma1.perpus.test
echo 127.0.0.1 smp5.perpus.test
echo 127.0.0.1 sma3.perpus.test
echo.
echo Open Notepad as Administrator:
echo   - Win+R, type: notepad C:\Windows\System32\drivers\etc\hosts
echo   - Paste the lines above
echo   - Save
echo.
pause

echo [2] APACHE VHOSTS
echo ==========================================
echo File: C:\xampp\apache\conf\extra\httpd-vhosts.conf
echo.
echo Replace ALL content with:
echo.
echo ===== START COPY FROM HERE =====
echo.
echo # Perpustakaan Online Virtual Hosts
echo # Main Domain - Landing Page
echo ^<VirtualHost *:80^>
echo     ServerName perpus.test
echo     ServerAlias www.perpus.test
echo     DocumentRoot "C:/xampp/htdocs/perpustakaan-online"
echo.
echo     ^<Directory "C:/xampp/htdocs/perpustakaan-online"^>
echo         Options Indexes FollowSymLinks
echo         AllowOverride All
echo         Require all granted
echo     ^</Directory^>
echo ^</VirtualHost^>
echo.
echo # School Subdomains - Dashboards
echo ^<VirtualHost *:80^>
echo     ServerName *.perpus.test
echo     DocumentRoot "C:/xampp/htdocs/perpustakaan-online/public"
echo.
echo     ^<Directory "C:/xampp/htdocs/perpustakaan-online/public"^>
echo         Options Indexes FollowSymLinks
echo         AllowOverride All
echo         Require all granted
echo     ^</Directory^>
echo ^</VirtualHost^>
echo.
echo ===== END COPY HERE =====
echo.
echo Open with Notepad as Administrator:
echo   - Right-click on file, "Open With" ^> Notepad
echo   - Delete all content
echo   - Paste the code above
echo   - Save
echo.
pause

echo [3] VERIFY APACHE SYNTAX
echo ==========================================
echo.
C:\xampp\apache\bin\httpd.exe -t
echo.
echo If you see "Syntax OK", continue to step 4.
echo If you see an error, fix httpd-vhosts.conf and try again.
echo.
pause

echo [4] RESTART APACHE
echo ==========================================
echo.
echo Stopping Apache...
net stop Apache2.4
timeout /t 2

echo.
echo Flushing DNS...
ipconfig /flushdns
timeout /t 1

echo.
echo Starting Apache...
net start Apache2.4
timeout /t 2

echo [OK] Apache restarted
echo.
pause

echo [5] TEST ACCESS
echo ==========================================
echo.
echo Open your browser and go to:
echo   http://perpus.test/
echo.
echo You should see:
echo   - "Perpustakaan Digital" title
echo   - Buttons: "Masuk Perpustakaan" and "Daftarkan Sekolah"
echo.
echo If you see XAMPP welcome page:
echo   1. Clear browser cache (Ctrl+Shift+Delete)
echo   2. Close browser completely
echo   3. Reopen and try again
echo   4. Make sure URL is: http://perpus.test/ (not localhost)
echo.

echo.
echo =====================================================
echo    SETUP COMPLETE
echo =====================================================
echo.
echo If successful, you can now:
echo.
echo Access main domain:
echo   - http://perpus.test/
echo.
echo Access school subdomains:
echo   - http://sma1.perpus.test/
echo   - http://smp5.perpus.test/
echo   - http://sma3.perpus.test/
echo.
echo Login credentials:
echo   - Email: admin@sma1.com
echo   - Password: password
echo.
echo =====================================================
echo.
pause
