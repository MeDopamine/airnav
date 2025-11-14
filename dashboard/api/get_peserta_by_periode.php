<?php
require_once __DIR__ . '/../../auth.php';
require_login();
// only admin may fetch peserta by periode
if (!is_admin()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'data' => [], 'error' => 'Akses ditolak']);
    exit;
}
// API: get_peserta_by_periode.php
// Param: ?periode=yyyymm
// Output: JSON array of peserta for periode
include '../../db/db.php';
header('Content-Type: application/json');
$periode = '';
$jenis = '';
if (isset($_GET['periode'])) {
    $raw = $_GET['periode'];
    // Normalize to YYYYMM if possible
    if (preg_match('/^\d{4}-\d{2}$/', $raw)) {
        $periode = str_replace('-', '', $raw);
    } else {
        $digits = preg_replace('/[^0-9]/', '', $raw);
        if (strlen($digits) >= 6) $periode = substr($digits, 0, 6);
        elseif (preg_match('/^\d{6}$/', $digits)) $periode = $digits;
        else $periode = $raw;
    }
    $periode = mysqli_real_escape_string($conn, $periode);
}
if (isset($_GET['jenis'])) {
    $jenis = mysqli_real_escape_string($conn, $_GET['jenis']);
}
$data = [];
if ($periode) {
    // Build WHERE clause
    $where = "periode='$periode'";
    if ($jenis !== '') {
        $where .= " AND jenis_premi='$jenis'";
    }
    $sql = "SELECT id, nik, periode, jenis_premi, jml_premi_krywn, jml_premi_pt, total_premi, pic, status_data, created_at FROM data_peserta WHERE $where ORDER BY id DESC";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $data[] = $row;
        }
        mysqli_free_result($res);
    }
}
echo json_encode(['ok'=>true, 'data'=>$data]);
