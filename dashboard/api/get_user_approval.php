<?php
include_once __DIR__ . '/../../auth.php';
require_login();

header('Content-Type: application/json');
require_once __DIR__ . '/../../db/db.php';

$user = current_user();
if (!$user) {
    echo json_encode(['ok' => false, 'message' => 'not_authenticated']);
    exit;
}

$approved = 0;

// Check latest data_peserta for this user (userid stored as varchar)
$stmt = mysqli_prepare($conn, 'SELECT status_data FROM data_peserta WHERE userid = ? ORDER BY id DESC LIMIT 1');
if ($stmt) {
    $userid_str = (string)$user['id'];
    mysqli_stmt_bind_param($stmt, 's', $userid_str);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $status_data_val);
    if (mysqli_stmt_fetch($stmt)) {
        $approved = ((int)$status_data_val === 1) ? 1 : 0;
    }
    mysqli_stmt_close($stmt);
}

// Fallback to registrasi_peserta.is_verify if not approved via data_peserta
if (!$approved) {
    $sreg = mysqli_prepare($conn, 'SELECT is_verify FROM registrasi_peserta WHERE email = ? ORDER BY id DESC LIMIT 1');
    if ($sreg) {
        $email = $user['email'] ?? '';
        mysqli_stmt_bind_param($sreg, 's', $email);
        mysqli_stmt_execute($sreg);
        $r = mysqli_stmt_get_result($sreg);
        $rrow = mysqli_fetch_assoc($r);
        mysqli_stmt_close($sreg);
        if ($rrow && !empty($rrow['is_verify'])) {
            $approved = 1;
        }
    }
}

echo json_encode(['ok' => true, 'approved' => (int)$approved]);

mysqli_close($conn);
?>
