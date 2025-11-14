<?php
ob_start();
require_once __DIR__ . '/../../auth.php';
ob_end_clean();

require_login();
// Hanya admin yang bisa approve invoice
require_admin();

include __DIR__ . '/../../db/db.php';

header('Content-Type: application/json; charset=utf-8');

// Get POST data
$invoice_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : ''; // 'approve' atau 'reject'

if ($invoice_id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Invoice ID tidak valid']);
    exit;
}

if (!in_array($action, ['approve', 'reject'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Action tidak valid']);
    exit;
}

// Get current user
$user = current_user();
$user_id = $user['id'] ?? 0;

// Check if invoice exists
$check_sql = "SELECT * FROM invoice_airnav WHERE id = $invoice_id";
$check_result = mysqli_query($conn, $check_sql);

if (!$check_result || mysqli_num_rows($check_result) === 0) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'msg' => 'Invoice tidak ditemukan']);
    exit;
}

$invoice = mysqli_fetch_assoc($check_result);
mysqli_free_result($check_result);

// Check if invoice is in pending state
if ((int)$invoice['flag'] !== 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Invoice tidak dapat diubah karena sudah diproses atau dalam status lain']);
    exit;
}

$new_flag = ($action === 'approve') ? 1 : 2;

// === JIKA REJECT: update data_peserta ===
if ($action === 'reject') {
    $sql_peserta = "
        UPDATE data_peserta 
        SET status_data = $new_flag 
        WHERE periode = '{$invoice['periode']}' 
          AND jenis_premi = '{$invoice['jenis_premi']}'
    ";

    if (!mysqli_query($conn, $sql_peserta)) {
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'msg' => 'Gagal mengupdate data peserta: ' . mysqli_error($conn)
        ]);
        exit;
    }
}

// === UPDATE ALWAYS: invoice_airnav ===
$sql_invoice = "UPDATE invoice_airnav SET flag = $new_flag WHERE id = $invoice_id";

if (!mysqli_query($conn, $sql_invoice)) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'msg' => 'Gagal mengupdate invoice: ' . mysqli_error($conn)
    ]);
    exit;
}

// Jika semua berhasil
echo json_encode([
    'ok' => true,
    'msg' => ($action === 'approve') 
        ? 'Invoice berhasil di-approve' 
        : 'Invoice berhasil di-reject'
]);
exit;

// Update status data_peserta if invoice is approved
mysqli_close($conn);
?>
