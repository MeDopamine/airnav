<?php
// API untuk mendapatkan invoice dengan status pending atau revisi
header('Content-Type: application/json');

// Load dependencies
if (!defined('__DIR__')) {
    define('__DIR__', dirname(__FILE__));
}

require_once __DIR__ . '/../../auth.php';

// Check if user is logged in - if not, return error
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit;
}

// Hanya admin yang boleh akses
if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Forbidden']);
    exit;
}

include_once __DIR__ . '/../../db/db.php';

// Query invoice dengan status pending atau revisi
// Status disimpan di kolom flag: null/0 = pending, 2 = revisi
// Kolom: periode, jenis_premi (INT), invoice_no, flag
$sql = "SELECT id, periode, jenis_premi, invoice_no, flag FROM invoice_airnav 
        WHERE (flag IS NULL OR flag = 0 OR flag = 2)
        AND deleted_at IS NULL
        ORDER BY periode DESC, id DESC
        LIMIT 100";

$result = mysqli_query($conn, $sql);
if (!$result) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Database error']);
    exit;
}

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'id' => $row['id'],
        'periode' => $row['periode'],
        'jenis_premi' => $row['jenis_premi'],
        'no_invoice' => $row['invoice_no'],
        'status' => $row['flag']  // Ambil dari kolom flag
    ];
}
mysqli_free_result($result);

echo json_encode(['ok' => true, 'data' => $data]);
?>
