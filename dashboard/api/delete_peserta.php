<?php
require_once __DIR__ . '/../../auth.php';
require_login();
// only admin may delete peserta
if (!is_admin()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Akses ditolak: admin only']);
    exit;
}
// API untuk menghapus data peserta berdasarkan ID
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Invalid request']);
    exit;
}

include_once __DIR__ . '/../../db/db.php';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id < 1) {
    echo json_encode(['ok' => false, 'msg' => 'ID tidak valid']);
    exit;
}

$res = mysqli_query($conn, "DELETE FROM data_peserta WHERE id = $id");
if ($res) {
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'msg' => 'Gagal menghapus data']);
}
mysqli_close($conn);
