<?php
require_once __DIR__ . '/../../auth.php';
require_login();
if (!is_admin()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'data' => [], 'error' => 'Akses ditolak']);
    exit;
}
include '../../db/db.php';
header('Content-Type: application/json');
$jenis = '';
if (isset($_GET['jenis'])) {
    $raw = $_GET['jenis'];
    // Expect numeric jenis_premi; sanitize
    $digits = preg_replace('/[^0-9]/', '', $raw);
    if ($digits === '') {
        echo json_encode(['ok' => false, 'data' => [], 'error' => 'Parameter jenis tidak valid']);
        exit;
    }
    $jenis = intval($digits);
}
$data = [];
if ($jenis !== '') {
    $sql = "SELECT id, nik, periode, jenis_premi, jml_premi_krywn, jml_premi_pt, total_premi, pic, status_data, created_at FROM data_peserta WHERE jenis_premi = " . intval($jenis) . " ORDER BY nik ASC";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $data[] = $row;
        }
        mysqli_free_result($res);
    }
}
echo json_encode(['ok'=>true, 'data'=>$data]);
