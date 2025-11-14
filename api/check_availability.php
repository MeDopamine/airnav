<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db/db.php';

$body = json_decode(file_get_contents('php://input'), true);
$email = trim((string)($body['email'] ?? ''));
$nik = trim((string)($body['nik'] ?? ''));

$result = [
    'email_taken' => false,
    'nik_taken' => false,
];

if ($email !== '') {
    $stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) $result['email_taken'] = true;
    }
}

if ($nik !== '') {
    $stmt = mysqli_prepare($conn, 'SELECT id FROM registrasi_peserta WHERE nik = ? LIMIT 1');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $nik);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) $result['nik_taken'] = true;
    }
}

echo json_encode($result);
exit;
