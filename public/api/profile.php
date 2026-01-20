<?php
/**
 * Student Profile API Endpoint
 * 
 * Actions:
 * - get_profile: GET profil siswa
 * - update_profile: POST update data profil
 * - upload_photo: POST upload foto profil
 * 
 * @version 1.0
 * @author Perpustakaan Online
 */

// Start session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek auth - match dengan session yang ada di sistem
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Silakan login terlebih dahulu'
    ]);
    exit;
}

// Load dependencies
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/StudentProfileModel.php';
require_once __DIR__ . '/../src/PhotoUploadHandler.php';

try {
    // Get database connection
    $pdo = require __DIR__ . '/../src/db.php';
    $studentId = (int)$_SESSION['user']['id'];
    
    // Init model
    $profileModel = new StudentProfileModel($pdo);
    
    // Get action dari query string atau POST
    $action = $_GET['action'] ?? $_POST['action'] ?? 'get_profile';
    
    switch ($action) {
        
        // GET profil siswa
        case 'get_profile':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                break;
            }
            
            $profile = $profileModel->getProfile($studentId);
            
            if ($profile) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'data' => $profile
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Profil siswa tidak ditemukan'
                ]);
            }
            break;
        
        // POST update profil
        case 'update_profile':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                break;
            }
            
            // Validasi input
            $updateData = [];
            
            // nama_lengkap
            if (isset($_POST['nama_lengkap'])) {
                $nama = trim($_POST['nama_lengkap']);
                if (strlen($nama) < 3) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Nama minimal 3 karakter'
                    ]);
                    exit;
                }
                $updateData['nama_lengkap'] = $nama;
                $updateData['nama'] = $nama; // alias
            }
            
            // email
            if (isset($_POST['email'])) {
                $email = trim($_POST['email']);
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Format email tidak valid'
                    ]);
                    exit;
                }
                $updateData['email'] = $email;
            }
            
            // no_hp
            if (isset($_POST['no_hp'])) {
                $noHp = trim($_POST['no_hp']);
                if (!empty($noHp) && !preg_match('/^(\+62|62|0)[0-9]{9,12}$/', $noHp)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Format nomor HP tidak valid'
                    ]);
                    exit;
                }
                $updateData['no_hp'] = $noHp;
                $updateData['no_telepon'] = $noHp; // alias
            }
            
            // alamat
            if (isset($_POST['alamat'])) {
                $updateData['alamat'] = trim($_POST['alamat']);
            }
            
            if (empty($updateData)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Tidak ada data untuk diupdate'
                ]);
                break;
            }
            
            $result = $profileModel->updateProfile($studentId, $updateData);
            
            if ($result['success']) {
                http_response_code(200);
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }
            break;
        
        // POST upload foto
        case 'upload_photo':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                break;
            }
            
            // Validasi file uploaded
            if (!isset($_FILES['photo'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Tidak ada file yang diupload'
                ]);
                break;
            }
            
            // Handle upload
            $uploadHandler = new PhotoUploadHandler();
            $uploadResult = $uploadHandler->handleUpload($_FILES['photo'], $studentId);
            
            if (!$uploadResult['success']) {
                http_response_code(400);
                echo json_encode($uploadResult);
                break;
            }
            
            // Delete old photo
            $profile = $profileModel->getProfile($studentId);
            if ($profile && !empty($profile['foto'])) {
                $uploadHandler->deleteOldPhoto($profile['foto']);
            }
            
            // Update photo path di database
            $updateResult = $profileModel->updatePhotoPath($studentId, $uploadResult['path']);
            
            if ($updateResult) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'path' => $uploadResult['path'],
                    'message' => 'Foto berhasil diupload dan disimpan'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Gagal menyimpan path foto ke database'
                ]);
            }
            break;
        
        // Action tidak ditemukan
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Action tidak dikenali: ' . htmlspecialchars($action)
            ]);
            break;
    }
    
} catch (Exception $e) {
    error_log("Error API profile.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
