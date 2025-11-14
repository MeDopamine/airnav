<?php
require_once __DIR__ . '/../../auth.php';
require_login();
// only admin may update peserta
if (!is_admin()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Akses ditolak']);
    exit;
}
// API untuk update data peserta berdasarkan ID
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
$nik = mysqli_real_escape_string($conn, $_POST['nik'] ?? '');
$periode_raw = $_POST['periode'] ?? '';
// Normalize periode to YYYYMM where possible
$periode_digits = preg_replace('/[^0-9]/', '', $periode_raw);
if (preg_match('/^\d{4}-\d{2}$/', $periode_raw)) {
    $periode = str_replace('-', '', $periode_raw);
} elseif (strlen($periode_digits) >= 6) {
    $periode = substr($periode_digits, 0, 6);
} elseif (preg_match('/^\d{6}$/', $periode_digits)) {
    $periode = $periode_digits;
} else {
    $periode = mysqli_real_escape_string($conn, $periode_raw);
}
$periode = mysqli_real_escape_string($conn, $periode);
$tgl_lahir = mysqli_real_escape_string($conn, $_POST['tgl_lahir'] ?? '');
$total_premi = (float)($_POST['total_premi'] ?? 0);
$pic = mysqli_real_escape_string($conn, $_POST['pic'] ?? '');

$sql = "UPDATE data_peserta SET nik='$nik', periode='$periode', tgl_lahir='$tgl_lahir', total_premi='$total_premi', pic='$pic' WHERE id=$id";
$res = mysqli_query($conn, $sql);
if ($res) {
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'msg' => 'Gagal update data']);
}
mysqli_close($conn);
