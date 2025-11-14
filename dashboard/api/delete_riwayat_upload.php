<?php
/**
 * API: delete_riwayat_upload.php
 * Menghapus satu riwayat upload berdasarkan ID
 */

require_once __DIR__ . '/../../auth.php';
require_login();

// Only admin can delete
if (!is_admin()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Akses ditolak']);
    exit;
}

require_once '../../db/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Metode tidak diizinkan']);
    exit;
}

// Get JSON body
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || empty($input['id'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'ID riwayat tidak ditemukan']);
    exit;
}

$riwayatId = intval($input['id']);

try {
    // Delete from riwayat_upload table
    $sql = "DELETE FROM riwayat_upload WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $riwayatId);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $affectedRows = $stmt->affected_rows;
    $stmt->close();
    
    if ($affectedRows > 0) {
        echo json_encode(['ok' => true, 'msg' => 'Riwayat berhasil dihapus']);
    } else {
        echo json_encode(['ok' => false, 'msg' => 'Riwayat tidak ditemukan']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
}
?>
