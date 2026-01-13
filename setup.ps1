# Perpustakaan Online - Setup Script (PowerShell)
# Run with Administrator privileges

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")

if (-not $isAdmin) {
    Write-Host "ERROR: This script must be run as Administrator!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please:"
    Write-Host "  1. Open PowerShell as Administrator"
    Write-Host "  2. Navigate to: C:\xampp\htdocs\perpustakaan-online"
    Write-Host "  3. Run: Set-ExecutionPolicy -ExecutionPolicy Bypass -Scope CurrentUser"
    Write-Host "  4. Run: .\setup.ps1"
    Write-Host ""
    exit 1
}

Write-Host ""
Write-Host "================================================" -ForegroundColor Green
Write-Host "  PERPUSTAKAAN ONLINE - SETUP" -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Green
Write-Host ""

# ============================================
# STEP 1: UPDATE HOSTS FILE
# ============================================
Write-Host "[STEP 1] Updating Hosts File..." -ForegroundColor Cyan
Write-Host ""

$hostsPath = "C:\Windows\System32\drivers\etc\hosts"
$hostsContent = Get-Content $hostsPath -Raw
$entriesToAdd = @"

# Perpustakaan Online - Multi-Tenant Library System
127.0.0.1 perpus.test
127.0.0.1 sma1.perpus.test
127.0.0.1 smp5.perpus.test
127.0.0.1 sma3.perpus.test
"@

if ($hostsContent -notmatch "perpus\.test") {
    Add-Content -Path $hostsPath -Value $entriesToAdd -Encoding ASCII
    Write-Host "[OK] Hosts file updated" -ForegroundColor Green
    Write-Host "     Added: perpus.test, sma1.perpus.test, smp5.perpus.test, sma3.perpus.test"
} else {
    Write-Host "[OK] Hosts file already has entries" -ForegroundColor Green
}

Write-Host ""

# ============================================
# STEP 2: CREATE VHOSTS CONFIG
# ============================================
Write-Host "[STEP 2] Creating Apache VirtualHost Configuration..." -ForegroundColor Cyan
Write-Host ""

$vhostsPath = "C:\xampp\apache\conf\extra\httpd-vhosts.conf"
$vhostsContent = @"
# Perpustakaan Online - Virtual Hosts Configuration
# Main Domain - Landing Page
<VirtualHost *:80>
    ServerName perpus.test
    ServerAlias www.perpus.test
    DocumentRoot "C:/xampp/htdocs/perpustakaan-online"
    
    <Directory "C:/xampp/htdocs/perpustakaan-online">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

# School Subdomains - Dashboards
<VirtualHost *:80>
    ServerName *.perpus.test
    DocumentRoot "C:/xampp/htdocs/perpustakaan-online/public"
    
    <Directory "C:/xampp/htdocs/perpustakaan-online/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
"@

# Backup original
if (-not (Test-Path "$vhostsPath.original")) {
    Copy-Item $vhostsPath "$vhostsPath.original" -Force
    Write-Host "[OK] Backup created: httpd-vhosts.conf.original"
}

# Write new config
Set-Content -Path $vhostsPath -Value $vhostsContent -Encoding ASCII
Write-Host "[OK] VirtualHost configuration created"
Write-Host ""

# ============================================
# STEP 3: VERIFY APACHE SYNTAX
# ============================================
Write-Host "[STEP 3] Verifying Apache Configuration..." -ForegroundColor Cyan
Write-Host ""

$apachePath = "C:\xampp\apache\bin\httpd.exe"
$testResult = & $apachePath -t 2>&1

if ($testResult -like "*Syntax OK*") {
    Write-Host "[OK] Apache configuration is valid" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Apache configuration has errors!" -ForegroundColor Red
    Write-Host $testResult
    exit 1
}

Write-Host ""

# ============================================
# STEP 4: FLUSH DNS
# ============================================
Write-Host "[STEP 4] Flushing DNS Cache..." -ForegroundColor Cyan
Write-Host ""

ipconfig /flushdns | Out-Null
Write-Host "[OK] DNS cache flushed"
Write-Host ""

# ============================================
# STEP 5: RESTART APACHE
# ============================================
Write-Host "[STEP 5] Restarting Apache Service..." -ForegroundColor Cyan
Write-Host ""

Write-Host "Stopping Apache..."
Stop-Service -Name "Apache2.4" -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 2

Write-Host "Starting Apache..."
Start-Service -Name "Apache2.4" -ErrorAction SilentlyContinue
Start-Sleep -Seconds 3

$service = Get-Service -Name "Apache2.4" -ErrorAction SilentlyContinue
if ($service.Status -eq "Running") {
    Write-Host "[OK] Apache is running" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Apache failed to start" -ForegroundColor Red
    exit 1
}

Write-Host ""

# ============================================
# COMPLETE
# ============================================
Write-Host "================================================" -ForegroundColor Green
Write-Host "  SETUP COMPLETE" -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Green
Write-Host ""

Write-Host "Configuration Summary:" -ForegroundColor Yellow
Write-Host "  ✓ Hosts file updated"
Write-Host "  ✓ Apache VirtualHost configured"
Write-Host "  ✓ DNS cache flushed"
Write-Host "  ✓ Apache restarted"
Write-Host ""

Write-Host "NEXT STEPS:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. CLEAR BROWSER CACHE:"
Write-Host "   - Press Ctrl+Shift+Delete"
Write-Host "   - Select all and clear"
Write-Host "   - Close browser completely"
Write-Host ""

Write-Host "2. OPEN BROWSER AND ACCESS:"
Write-Host "   - http://perpus.test/"
Write-Host ""

Write-Host "3. YOU SHOULD SEE:"
Write-Host "   - Perpustakaan Digital landing page"
Write-Host "   - NOT the XAMPP welcome page"
Write-Host ""

Write-Host "4. TO LOGIN:"
Write-Host "   - Email: admin@sma1.com"
Write-Host "   - Password: password"
Write-Host "   - Access: http://sma1.perpus.test/"
Write-Host ""

Write-Host "================================================" -ForegroundColor Green
Write-Host ""

Write-Host "Press any key to close..." -ForegroundColor Cyan
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
