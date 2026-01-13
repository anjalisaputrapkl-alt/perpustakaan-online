@echo off
REM This script must be run as Administrator
REM Right-click on this file and select "Run as Administrator"

echo.
echo Checking if running as Administrator...
net session >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: This script must be run as Administrator!
    echo.
    echo Please:
    echo   1. Right-click on this file
    echo   2. Select "Run as Administrator"
    echo.
    pause
    exit /b 1
)

echo OK - Running with Administrator privileges
echo.

REM ========================================
REM STEP 1: ADD TO HOSTS FILE
REM ========================================
echo STEP 1: Adding to Hosts File...
echo.

REM Create a temp file with the new entries
(
    echo 127.0.0.1 perpus.test
    echo 127.0.0.1 sma1.perpus.test
    echo 127.0.0.1 smp5.perpus.test
    echo 127.0.0.1 sma3.perpus.test
) > "%TEMP%\perpus_hosts.txt"

REM Append to hosts file
type "%TEMP%\perpus_hosts.txt" >> C:\Windows\System32\drivers\etc\hosts
del "%TEMP%\perpus_hosts.txt"

echo [OK] Hosts file updated
echo.

REM ========================================
REM STEP 2: UPDATE VIRTUALHOST CONFIG
REM ========================================
echo STEP 2: Configuring Apache VirtualHost...
echo.

REM Backup original file
if not exist "C:\xampp\apache\conf\extra\httpd-vhosts.conf.bak" (
    copy "C:\xampp\apache\conf\extra\httpd-vhosts.conf" "C:\xampp\apache\conf\extra\httpd-vhosts.conf.bak" >nul
    echo [OK] Backup created
)

REM Create new vhosts config
(
    echo # Perpustakaan Online - Virtual Hosts Configuration
    echo.
    echo # Main Domain - Landing Page
    echo ^<VirtualHost *:80^>
    echo     ServerName perpus.test
    echo     ServerAlias www.perpus.test
    echo     DocumentRoot "C:/xampp/htdocs/perpustakaan-online"
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
    echo     ^<Directory "C:/xampp/htdocs/perpustakaan-online/public"^>
    echo         Options Indexes FollowSymLinks
    echo         AllowOverride All
    echo         Require all granted
    echo     ^</Directory^>
    echo ^</VirtualHost^>
) > "C:\xampp\apache\conf\extra\httpd-vhosts.conf"

echo [OK] VirtualHost configuration created
echo.

REM ========================================
REM STEP 3: VERIFY APACHE SYNTAX
REM ========================================
echo STEP 3: Verifying Apache configuration...
echo.

C:\xampp\apache\bin\httpd.exe -t
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo ERROR: Apache configuration has syntax errors!
    echo Please check the configuration file.
    pause
    exit /b 1
)

echo [OK] Apache syntax is valid
echo.

REM ========================================
REM STEP 4: RESTART APACHE
REM ========================================
echo STEP 4: Restarting Apache...
echo.

echo Stopping Apache...
net stop Apache2.4 >nul 2>&1
timeout /t 2 /nobreak >nul

echo Flushing DNS cache...
ipconfig /flushdns >nul 2>&1
timeout /t 1 /nobreak >nul

echo Starting Apache...
net start Apache2.4 >nul 2>&1
timeout /t 2 /nobreak >nul

if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Failed to start Apache!
    pause
    exit /b 1
)

echo [OK] Apache restarted successfully
echo.

REM ========================================
REM COMPLETE
REM ========================================
echo.
echo ============================================
echo      SETUP COMPLETE
echo ============================================
echo.
echo Hosts File Updated with:
echo   - perpus.test
echo   - sma1.perpus.test
echo   - smp5.perpus.test
echo   - sma3.perpus.test
echo.
echo Apache VirtualHost Configured!
echo.
echo NEXT STEPS:
echo ============================================
echo.
echo 1. CLEAR BROWSER CACHE:
echo    - Press Ctrl+Shift+Delete
echo    - Delete all cached data
echo    - Close browser completely
echo.
echo 2. OPEN BROWSER FRESH:
echo    - Open new browser window
echo    - Go to: http://perpus.test/
echo.
echo 3. YOU SHOULD SEE:
echo    - "Perpustakaan Digital" title
echo    - Landing page with login buttons
echo    - NOT the XAMPP welcome page
echo.
echo IF STILL SEEING XAMPP PAGE:
echo    - Make sure URL is: http://perpus.test/
echo    - NOT http://localhost/ or http://127.0.0.1/
echo    - Try accessing: http://sma1.perpus.test/
echo.
echo ============================================
echo.

pause
