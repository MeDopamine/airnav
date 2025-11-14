<?php
require_once __DIR__ . '/../../auth.php';
require_login();

include __DIR__ . '/../../db/db.php';

header('Content-Type: application/json');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : '';
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Invalid invoice id']);
    exit;
}

if (!in_array($action, ['verify', 'revise'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Invalid action']);
    exit;
}

// Get the invoice first to verify it exists
$sql = "SELECT id, invoice_no, flag FROM invoice_airnav WHERE id = " . $id . " LIMIT 1";
$res = mysqli_query($conn, $sql);
if (!$res || mysqli_num_rows($res) == 0) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'msg' => 'Invoice not found']);
    exit;
}
$invoice = mysqli_fetch_assoc($res);
mysqli_free_result($res);

// Determine new flag value based on action
// flag: 0=Pending, 1=Verified, 2=Revision
$newFlag = ($action === 'verify') ? 1 : 2;

// Update only the flag column
$updateSql = "UPDATE invoice_airnav SET flag = " . $newFlag . " WHERE id = " . $id;
if (mysqli_query($conn, $updateSql)) {
    $statusText = ($action === 'verify') ? 'Verified' : 'Revision Requested';
    echo json_encode([
        'ok' => true, 
        'msg' => 'Invoice ' . $statusText . ' successfully',
        'id' => $id,
        'action' => $action,
        'flag' => $newFlag,
        'status' => $statusText
    ]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Failed to update invoice: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>
