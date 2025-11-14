<?php
require_once '../../auth.php';
require_login();
// only admin may read riwayat upload
if (!is_admin()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Akses ditolak']);
    exit;
}
require_once '../../db/db.php';
header('Content-Type: application/json');
$limit = isset($_GET['all']) ? 1000 : 5;
$res = mysqli_query($conn, "SELECT * FROM riwayat_upload ORDER BY tanggal_upload DESC, id DESC LIMIT $limit");
$data = [];
while ($row = mysqli_fetch_assoc($res)) {
    $data[] = $row;
}
echo json_encode($data);
