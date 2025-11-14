<?php
require_once __DIR__ . '/../../auth.php';
require_login();
if (!is_admin()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Akses ditolak']);
    exit;
}

include __DIR__ . '/../../db/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Invalid invoice id']);
    exit;
}

$sql = "SELECT * FROM invoice_airnav WHERE id = " . $id . " LIMIT 1";
$res = mysqli_query($conn, $sql);
if (!$res || mysqli_num_rows($res) == 0) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Invoice not found']);
    exit;
}

$row = mysqli_fetch_assoc($res);
mysqli_free_result($res);

header('Content-Type: application/json');
echo json_encode([
    'ok' => true,
    'data' => [
        'id' => (int)$row['id'],
        'periode' => $row['periode'] ?? '',
        'jenis_premi' => $row['jenis_premi'] ?? '',
        'invoice_no' => $row['invoice_no'] ?? '',
        'tgl_invoice' => $row['printed_at'] ?? $row['created_at'] ?? '',
        'jumlah' => (int)($row['jumlah'] ?? 0),
        'jml_premi_krywn' => (float)($row['jml_premi_krywn'] ?? 0),
        'jml_premi_pt' => (float)($row['jml_premi_pt'] ?? 0),
        'total_premi' => (float)($row['total_premi'] ?? 0),
        'pic' => $row['pic'] ?? '',
        'urlinvoice' => $row['urlinvoice'] ?? '',
        'flag' => (int)($row['flag'] ?? 0)
    ]
]);

mysqli_close($conn);
?>
