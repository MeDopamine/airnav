<?php
require_once __DIR__ . '/../../auth.php';
require_login();
// only admin may fetch all peserta
if (!is_admin_or_admintl()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'data' => [], 'msg' => 'Akses ditolak']);
    exit;
}
// Simple endpoint untuk return data peserta sebagai JSON
header('Content-Type: application/json');
include_once '../../db/db.php';
$result = mysqli_query($conn, "SELECT id, periode, jenis_premi, jml_premi_krywn, jml_premi_pt, total_premi, status_data FROM data_peserta ORDER BY periode DESC, id DESC");
$data = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    mysqli_free_result($result);
    echo json_encode(['ok' => true, 'data' => $data]);
} else {
    echo json_encode(['ok' => false, 'msg' => 'DB error']);
}
