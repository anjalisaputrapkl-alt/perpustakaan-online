<?php
/**
 * School Profile API
 * Menangani upload foto dan update data sekolah
 */

session_start();
require __DIR__ . '/../../src/db.php';
require __DIR__ . '/../../src/SchoolProfileModel.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$school_id = $_SESSION['user']['school_id'];
$model = new SchoolProfileModel($pdo);

// Determine action
$action = $_GET['action'] ?? $_POST['action'] ?? null;

switch ($action) {
    case 'upload_photo':
        handlePhotoUpload($school_id, $model);
        break;

    case 'update_data':
        handleUpdateData($school_id, $model);
        break;

    case 'delete_photo':
        handleDeletePhoto($school_id, $model);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

/**
 * Handle photo upload
 */
function handlePhotoUpload($school_id, $model)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        die(json_encode(['success' => false, 'message' => 'Method not allowed']));
    }

    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'File tidak ditemukan']));
    }

    try {
        // Delete old photo if exists
        $old_photo = $model->getSchoolPhoto($school_id);
        if ($old_photo) {
            $old_path = __DIR__ . '/../../public/uploads/school-photos/' . basename($old_photo);
            if (file_exists($old_path)) {
                unlink($old_path);
            }
        }

        // Save new photo
        $filename = $model->savePhotoFile($_FILES['photo']);

        // Update database
        $photo_path = 'uploads/school-photos/' . $filename;
        $model->updateSchoolPhoto($school_id, $photo_path);

        echo json_encode([
            'success' => true,
            'message' => 'Foto berhasil diunggah',
            'photo_path' => $photo_path
        ]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Handle data update
 */
function handleUpdateData($school_id, $model)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        die(json_encode(['success' => false, 'message' => 'Method not allowed']));
    }

    try {
        $data = [];

        // Validate and prepare data
        if (isset($_POST['name'])) {
            $name = trim($_POST['name']);
            if (empty($name)) {
                throw new Exception('Nama sekolah tidak boleh kosong');
            }
            $data['name'] = $name;
        }

        if (isset($_POST['npsn'])) {
            $data['npsn'] = trim($_POST['npsn']) ?: null;
        }

        if (isset($_POST['email'])) {
            $email = trim($_POST['email']);
            if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Format email tidak valid');
            }
            $data['email'] = $email ?: null;
        }

        if (isset($_POST['phone'])) {
            $data['phone'] = trim($_POST['phone']) ?: null;
        }

        if (isset($_POST['address'])) {
            $data['address'] = trim($_POST['address']) ?: null;
        }

        if (isset($_POST['website'])) {
            $website = trim($_POST['website']);
            if ($website && !filter_var($website, FILTER_VALIDATE_URL)) {
                throw new Exception('Format website tidak valid');
            }
            $data['website'] = $website ?: null;
        }

        if (isset($_POST['founded_year'])) {
            $year = intval($_POST['founded_year']);
            $data['founded_year'] = ($year > 0) ? $year : null;
        }

        if (empty($data)) {
            throw new Exception('Tidak ada data yang diubah');
        }

        // Update data
        $model->updateSchoolProfile($school_id, $data);

        echo json_encode([
            'success' => true,
            'message' => 'Data sekolah berhasil diperbarui'
        ]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Handle photo deletion
 */
function handleDeletePhoto($school_id, $model)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        die(json_encode(['success' => false, 'message' => 'Method not allowed']));
    }

    try {
        $model->deleteSchoolPhoto($school_id);

        echo json_encode([
            'success' => true,
            'message' => 'Foto berhasil dihapus'
        ]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
