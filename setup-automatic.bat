@echo off
REM =====================================================
REM PERPUSTAKAAN ONLINE - AUTOMATIC FIX SCRIPT
REM Run as Administrator
REM =====================================================

setlocal enabledelayedexpansion

echo.
echo =====================================================
echo    PERPUSTAKAAN ONLINE - SETUP OTOMATIS
echo =====================================================
echo.

REM ================== STEP 1: ADD HOSTS FILE ==================
echo [STEP 1] Updating Hosts File...
echo.

(
    echo.
    echo # Perpustakaan Online - Multi-Tenant Library System
    echo 127.0.0.1 perpus.test
    echo 127.0.0.1 sma1.perpus.test
    echo 127.0.0.1 smp5.perpus.test
    echo 127.0.0.1 sma3.perpus.test
) >> C:\Windows\System32\drivers\etc\hosts

echo [OK] Hosts file updated
echo.

REM ================== STEP 2: BACKUP ORIGINAL VHOSTS ==================
echo [STEP 2] Backing up Apache VirtualHost config...
copy C:\xampp\apache\conf\extra\httpd-vhosts.conf C:\xampp\apache\conf\extra\httpd-vhosts.conf.backup >nul 2>&1
echo [OK] Backup created: httpd-vhosts.conf.backup
echo.

REM ================== STEP 3: CREATE NEW VHOSTS ==================
echo [STEP 3] Configuring Apache VirtualHost...
echo.

(
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
) > C:\xampp\apache\conf\extra\httpd-vhosts.conf

echo [OK] VirtualHost configuration created
echo.

REM ================== STEP 4: VERIFY APACHE SYNTAX ==================
echo [STEP 4] Verifying Apache configuration syntax...
echo.

C:\xampp\apache\bin\httpd.exe -t
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [ERROR] Apache configuration has syntax errors!
    echo Please check C:\xampp\apache\conf\extra\httpd-vhosts.conf
    pause
    exit /b 1
)

echo.
echo [OK] Apache syntax is valid
echo.

REM ================== STEP 5: STOP APACHE ==================
echo [STEP 5] Stopping Apache...
net stop Apache2.4 >nul 2>&1
timeout /t 2 /nobreak >nul

echo [OK] Apache stopped
echo.

REM ================== STEP 6: FLUSH DNS ==================
echo [STEP 6] Flushing DNS cache...
ipconfig /flushdns >nul 2>&1
timeout /t 1 /nobreak >nul
echo [OK] DNS cache flushed
echo.

REM ================== STEP 7: START APACHE ==================
echo [STEP 7] Starting Apache...
net start Apache2.4 >nul 2>&1
timeout /t 3 /nobreak >nul

if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Failed to start Apache!
    echo Check if Apache2.4 service exists
    pause
    exit /b 1
)

echo [OK] Apache started successfully
echo.

REM ================== FINAL ==================
echo =====================================================
echo    ✓ SETUP COMPLETE
echo =====================================================
echo.
echo Hosts File Updated:
echo   - perpus.test
echo   - sma1.perpus.test
echo   - smp5.perpus.test
echo   - sma3.perpus.test
echo.
echo Apache VirtualHost Configured:
echo   - Main domain: C:/xampp/htdocs/perpustakaan-online
echo   - Subdomains: C:/xampp/htdocs/perpustakaan-online/public
echo.
echo Apache Status: RUNNING
echo.
echo =====================================================
echo    NEXT STEPS
echo =====================================================
echo.
echo 1. Open your browser
echo 2. Access: http://perpus.test/
echo 3. You should see Perpustakaan Online landing page
echo.
echo If you still see XAMPP page:
echo    - Clear browser cache (Ctrl+Shift+Delete)
echo    - Close and reopen browser
echo    - Check that you typed: http://perpus.test/
echo      (NOT http://localhost/)
echo.
echo To login with a test account:
echo    - Email: admin@sma1.com
echo    - Password: password
echo    - Access: http://sma1.perpus.test/
echo.
echo =====================================================
echo.

pause
