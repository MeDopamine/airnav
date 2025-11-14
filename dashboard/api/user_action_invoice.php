<?php
require_once __DIR__ . '/../../auth.php';
require_login();

include __DIR__ . '/../../db/db.php';
header('Content-Type: application/json');

$user = current_user();
// only regular users should call this endpoint (but admins allowed too)
if (!$user) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Akses ditolak']);
    exit;
}

$invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : '';
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

if (!$invoice_id || !in_array($action, ['approve', 'revise'], true)) {
    echo json_encode(['ok' => false, 'msg' => 'Parameter tidak lengkap']);
    exit;
}

// Validate invoice exists
$q = "SELECT id FROM invoice_airnav WHERE id=" . $invoice_id . " LIMIT 1";
$r = mysqli_query($conn, $q);
if (!$r || mysqli_num_rows($r) === 0) {
    echo json_encode(['ok' => false, 'msg' => 'Invoice tidak ditemukan']);
    exit;
}

// Map action to status int for invoice_airnav.status (0=pending,1=approved,2=revision)
$status_map = ['approve' => 1, 'revise' => 2];
$new_status = $status_map[$action];

// Insert action record
$stmt = mysqli_prepare($conn, "INSERT INTO invoice_user_actions (invoice_id, user_id, action, comment) VALUES (?,?,?,?)");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'iiss', $invoice_id, $user['id'], $action, $comment);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
} else {
    // best-effort insert
    $esc_comment = mysqli_real_escape_string($conn, $comment);
    mysqli_query($conn, "INSERT INTO invoice_user_actions (invoice_id, user_id, action, comment) VALUES ($invoice_id, " . intval($user['id']) . ", '" . mysqli_real_escape_string($conn, $action) . "', '$esc_comment')");
}

// Update invoice status
$u = "UPDATE invoice_airnav SET status=" . intval($new_status) . " WHERE id=" . $invoice_id;
mysqli_query($conn, $u);

echo json_encode(['ok' => true, 'msg' => 'Aksi tersimpan', 'status' => $new_status]);
exit;

?>
