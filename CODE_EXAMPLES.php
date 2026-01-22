<?php
/**
 * CODE EXAMPLES - Email Verification System
 * 
 * File ini berisi contoh-contoh kode dari implementasi
 * Email Verification System untuk referensi.
 */

// ============================================================
// 1. GENERATING VERIFICATION CODE
// ============================================================

/**
 * Location: src/EmailHelper.php
 */
function generateVerificationCode()
{
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Usage:
// $code = generateVerificationCode(); // Result: "042857" atau "000123"


// ============================================================
// 2. SENDING VERIFICATION EMAIL
// ============================================================

/**
 * Location: src/EmailHelper.php
 */
function sendVerificationEmail($recipient_email, $school_name, $admin_name, $verification_code)
{
    $subject = "Verifikasi Email - Pendaftaran Perpustakaan Digital";

    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .code { font-size: 36px; font-weight: bold; color: #2563eb; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>✓ Verifikasi Email</h1>
            <p>Halo $admin_name,</p>
            <p>Terima kasih telah mendaftarkan $school_name</p>
            
            <div class='code'>$verification_code</div>
            
            <p>Kode berlaku selama 15 menit dari email ini dikirim.</p>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: noreply@perpustakaan.edu\r\n";

    return mail($recipient_email, $subject, $message, $headers);
}

// Usage:
// $sent = sendVerificationEmail('admin@sch.id', 'SMA Jaya', 'Budi', '123456');
// if ($sent) { echo "Email terkirim"; }


// ============================================================
// 3. REGISTER DENGAN VERIFICATION
// ============================================================

/**
 * Location: public/api/register.php
 * Simplified version untuk reference
 */
function handleRegister($pdo)
{
    $school_name = $_POST['school_name'];
    $admin_name = $_POST['admin_name'];
    $admin_email = $_POST['admin_email'];
    $admin_password = $_POST['admin_password'];

    // 1. Create school
    $stmt = $pdo->prepare('INSERT INTO schools (name) VALUES (:name)');
    $stmt->execute(['name' => $school_name]);
    $school_id = $pdo->lastInsertId();

    // 2. Generate verification code
    $verification_code = generateVerificationCode();

    // 3. Create user dengan is_verified = 0
    $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        'INSERT INTO users (school_id, name, email, password, verification_code, is_verified) 
         VALUES (:school_id, :name, :email, :password, :verification_code, 0)'
    );
    $stmt->execute([
        'school_id' => $school_id,
        'name' => $admin_name,
        'email' => $admin_email,
        'password' => $password_hash,
        'verification_code' => $verification_code
    ]);
    $user_id = $pdo->lastInsertId();

    // 4. Send verification email
    $email_sent = sendVerificationEmail($admin_email, $school_name, $admin_name, $verification_code);

    if (!$email_sent) {
        return ['success' => false, 'message' => 'Gagal mengirim email verifikasi'];
    }

    // 5. Return user info untuk modal
    return [
        'success' => true,
        'message' => 'Pendaftaran berhasil. Silakan verifikasi email Anda.',
        'user_id' => $user_id,
        'email' => $admin_email
    ];
}


// ============================================================
// 4. VERIFY EMAIL CODE
// ============================================================

/**
 * Location: public/api/verify-email.php
 * Simplified version untuk reference
 */
function verifyEmailCode($pdo, $user_id, $verification_code)
{
    // 1. Get user dengan verification_code
    $stmt = $pdo->prepare(
        'SELECT id, created_at FROM users WHERE id = :user_id AND verification_code = :code'
    );
    $stmt->execute(['user_id' => $user_id, 'code' => $verification_code]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'message' => 'Kode verifikasi tidak valid'];
    }

    // 2. Cek apakah kode sudah expired (15 menit)
    $created = strtotime($user['created_at']);
    $expiry = $created + (15 * 60);
    if (time() > $expiry) {
        return ['success' => false, 'message' => 'Kode verifikasi telah kadaluarsa'];
    }

    // 3. Update user menjadi verified
    $stmt = $pdo->prepare(
        'UPDATE users SET is_verified = 1, verified_at = NOW(), verification_code = NULL WHERE id = :user_id'
    );
    $stmt->execute(['user_id' => $user_id]);

    // 4. Set session untuk auto-login
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_type'] = 'school';

    return [
        'success' => true,
        'message' => 'Email berhasil diverifikasi!',
        'redirect_url' => 'admin-dashboard.php'
    ];
}


// ============================================================
// 5. JAVASCRIPT - MODAL VERIFICATION
// ============================================================

/*
// Location: index.php - <script> section

function openVerificationModal(userId, email) {
    document.getElementById('verificationUserId').value = userId;
    document.getElementById('verificationEmail').textContent = email;
    document.getElementById('verificationModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';

    // Reset form
    document.querySelectorAll('.code-input').forEach(input => {
        input.value = '';
        input.classList.remove('error');
    });

    // Start countdown timer (15 minutes)
    startVerificationTimer();
}

function closeVerificationModal(e) {
    if (e && e.target.id !== 'verificationModal') return;
    document.getElementById('verificationModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Handle code input - auto focus ke field berikutnya
document.querySelectorAll('.code-input').forEach((input, index) => {
    input.addEventListener('input', (e) => {
        // Hanya angka
        e.target.value = e.target.value.replace(/[^0-9]/g, '');

        // Auto focus ke next field
        if (e.target.value && index < 5) {
            document.getElementById('code' + (index + 2)).focus();
        }
    });
});

// Handle verification submission
document.getElementById('verificationForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    // Ambil kode dari 6 input
    const codeInputs = document.querySelectorAll('.code-input');
    const verificationCode = Array.from(codeInputs).map(input => input.value).join('');

    // Validasi
    if (verificationCode.length !== 6) {
        alert('Silakan masukkan 6 digit kode verifikasi');
        return;
    }

    const userId = document.getElementById('verificationUserId').value;

    // Send ke API
    const response = await fetch('public/api/verify-email.php', {
        method: 'POST',
        body: new FormData({
            user_id: userId,
            verification_code: verificationCode
        })
    });

    const data = await response.json();
    if (data.success) {
        // Redirect
        window.location.href = 'public/' + data.redirect_url;
    } else {
        alert(data.message);
    }
});
*/


// ============================================================
// 6. DATABASE QUERIES
// ============================================================

/*
-- Tambah kolom untuk verification
ALTER TABLE `users` ADD COLUMN `verification_code` VARCHAR(10) NULL AFTER `password`;
ALTER TABLE `users` ADD COLUMN `is_verified` TINYINT(1) DEFAULT 0 AFTER `verification_code`;
ALTER TABLE `users` ADD COLUMN `verified_at` TIMESTAMP NULL AFTER `is_verified`;

-- Tambah index untuk performa
ALTER TABLE `users` ADD INDEX `idx_verification_code` (`verification_code`);
ALTER TABLE `users` ADD INDEX `idx_is_verified` (`is_verified`);

-- Check status user
SELECT id, email, is_verified, verification_code, verified_at FROM users WHERE id = 42;

-- Update user jadi verified (manual)
UPDATE users SET is_verified = 1, verified_at = NOW() WHERE id = 42;

-- Lihat unverified users
SELECT id, email, verification_code, created_at FROM users WHERE is_verified = 0;
*/


// ============================================================
// 7. FLOW DIAGRAM
// ============================================================

/*
REGISTRATION FLOW:

┌─ User clicks "Daftarkan Sekarang"
│
├─ registerForm submission handler
│  │
│  ├─ Validate email format (@sch.id)
│  │
│  ├─ POST to public/api/register.php
│  │  │
│  │  ├─ Generate 6-digit code
│  │  ├─ Hash password
│  │  ├─ Insert school
│  │  ├─ Insert user (is_verified=0)
│  │  ├─ Send verification email
│  │  └─ Return user_id & email
│  │
│  └─ Call openVerificationModal(user_id, email)
│
├─ Verification Modal opens
│  │
│  ├─ Display verification email
│  ├─ Start 15-minute countdown timer
│  └─ Focus on first code input
│
├─ User inputs 6-digit code
│  │
│  ├─ Auto-focus between inputs
│  ├─ Clear on backspace
│  └─ Numeric only validation
│
├─ Click "Verifikasi Email"
│  │
│  ├─ POST to public/api/verify-email.php
│  │  │
│  │  ├─ Get user with verification_code
│  │  ├─ Check code matches
│  │  ├─ Check not expired (15 min)
│  │  ├─ Update is_verified=1
│  │  ├─ Delete verification_code
│  │  ├─ Set verified_at=NOW()
│  │  ├─ Set session (auto-login)
│  │  └─ Return redirect URL
│  │
│  └─ Show success message
│
└─ Auto-redirect to dashboard (2 sec)
   └─ User logged in & account activated ✓
*/


// ============================================================
// 8. ERROR HANDLING EXAMPLES
// ============================================================

/*
// Error Case 1: Email sudah terdaftar
if ($stmt->fetchColumn() > 0) {
    return ['success' => false, 'message' => 'Email sudah terdaftar'];
}

// Error Case 2: Gagal mengirim email
if (!$email_sent) {
    return ['success' => false, 'message' => 'Gagal mengirim email verifikasi'];
}

// Error Case 3: Kode tidak sesuai
if (!$user) {
    return ['success' => false, 'message' => 'Kode verifikasi tidak valid'];
}

// Error Case 4: Kode expired
if (time() > $expiry) {
    return ['success' => false, 'message' => 'Kode verifikasi telah kadaluarsa. Silakan daftar ulang.'];
}

// Error Case 5: User input salah
if (verificationCode.length !== 6) {
    alert('Silakan masukkan 6 digit kode verifikasi');
}
*/


// ============================================================
// 9. TESTING QUERIES
// ============================================================

/*
-- Test: Lihat pending registrations
SELECT id, email, is_verified, verification_code, created_at 
FROM users 
WHERE is_verified = 0
ORDER BY created_at DESC;

-- Test: Verify manually
UPDATE users 
SET is_verified = 1, verified_at = NOW(), verification_code = NULL 
WHERE id = 42;

-- Test: Reset for re-testing
UPDATE users 
SET is_verified = 0, verification_code = '123456', verified_at = NULL 
WHERE id = 42;

-- Test: Check email delivery (count users with valid codes)
SELECT COUNT(*) as pending_verifications 
FROM users 
WHERE is_verified = 0 AND verification_code IS NOT NULL;
*/


// ============================================================
// 10. CONFIGURATION EXAMPLES
// ============================================================

/*
// In php.ini for SMTP (Windows)
[mail function]
SMTP = smtp.mailtrap.io
smtp_port = 2525
sendmail_from = test@example.com

// Or for Gmail (requires app password)
[mail function]
SMTP = smtp.gmail.com
smtp_port = 587
sendmail_from = your-email@gmail.com

// In code: PHPMailer alternative (optional upgrade)
use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.mailtrap.io';
$mail->Port = 2525;
$mail->setFrom('noreply@perpustakaan.edu');
$mail->addAddress($recipient_email);
$mail->Subject = 'Verifikasi Email';
$mail->Body = $message;
$mail->isHTML(true);
$mail->send();
*/

?>

<!-- 

NOTES:
- Ini adalah file reference/dokumentasi dalam bentuk PHP comments
- Tidak perlu dijalankan, cukup untuk referensi kode
- Untuk implementasi, lihat file-file yang sebenarnya:
  - src/EmailHelper.php
  - public/api/register.php
  - public/api/verify-email.php
  - index.php (JavaScript section)
  
UNTUK SETUP:
1. Baca QUICK_START.md (3 langkah mudah)
2. Jalankan migration: http://localhost/perpustakaan-online/sql/run-migration.php
3. Test registrasi dengan email verification

UNTUK DETAIL:
1. EMAIL_VERIFICATION_DOCS.md - Dokumentasi lengkap
2. IMPLEMENTATION_GUIDE.md - Setup detail guide

-->