<?php
require_once __DIR__ . '/../../auth.php';
require_login();
if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Akses ditolak']);
    exit;
}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../db/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid id']);
    exit;
}

// Fetch registrasi_peserta row
$s = mysqli_prepare($conn, 'SELECT nik, tgl_lahir, email FROM registrasi_peserta WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($s, 'i', $id);
mysqli_stmt_execute($s);
$res = mysqli_stmt_get_result($s);
$row = mysqli_fetch_assoc($res);
mysqli_stmt_close($s);

if (!$row) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Registrasi tidak ditemukan']);
    exit;
}

$nik = $row['nik'];
$tgl_lahir = $row['tgl_lahir'];
$email = $row['email'];

// Find matching users row by email to obtain userid (string)
$userid = null;
if (!empty($email)) {
    $su = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? ORDER BY id DESC LIMIT 1');
    if ($su) {
        mysqli_stmt_bind_param($su, 's', $email);
        mysqli_stmt_execute($su);
        $r2 = mysqli_stmt_get_result($su);
        $urow = mysqli_fetch_assoc($r2);
        mysqli_stmt_close($su);
        if ($urow) $userid = (string)$urow['id'];
    }
}

if ($userid === null) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Tidak menemukan akun pengguna untuk registrasi ini']);
    exit;
}

// Begin transaction: create or update data_peserta for this userid/nik
mysqli_begin_transaction($conn);
try {
    // look for existing data_peserta for this userid & nik (any periode)
    $q = mysqli_prepare($conn, 'SELECT id FROM data_peserta WHERE userid = ? AND nik = ? ORDER BY id DESC LIMIT 1');
    mysqli_stmt_bind_param($q, 'ss', $userid, $nik);
    mysqli_stmt_execute($q);
    $rQ = mysqli_stmt_get_result($q);
    $existing = mysqli_fetch_assoc($rQ);
    mysqli_stmt_close($q);

    if ($existing && !empty($existing['id'])) {
        // If there is an existing data_peserta row for this user, mark it approved.
        $dpid = (int)$existing['id'];
        $u = mysqli_prepare($conn, 'UPDATE data_peserta SET status_data = 1 WHERE id = ?');
        if ($u) {
            mysqli_stmt_bind_param($u, 'i', $dpid);
            mysqli_stmt_execute($u);
            mysqli_stmt_close($u);
        }
    } else {
        // Do NOT create a new data_peserta row here. Approval alone should not create premi/payment entries.
        // If admin wants to create a payment/premi entry, they should use the Add Peserta / Add Payment flows.
    }

    // mark registrasi_peserta as verified
    $admin = current_user();
    // prefer to store admin display name in user_verify
    $admin_name = $admin['name'] ?? null;
    // if name not available but id exists, try to lookup name from users table
    if (empty($admin_name) && !empty($admin['id'])) {
        $sname = mysqli_prepare($conn, 'SELECT name FROM users WHERE id = ? LIMIT 1');
        if ($sname) {
            mysqli_stmt_bind_param($sname, 'i', $admin['id']);
            mysqli_stmt_execute($sname);
            $rname = mysqli_stmt_get_result($sname);
            $nr = mysqli_fetch_assoc($rname);
            mysqli_stmt_close($sname);
            if ($nr && !empty($nr['name'])) $admin_name = $nr['name'];
        }
    }
    // final fallback to email or generic 'admin'
    $admin_ident = $admin_name ?? ($admin['email'] ?? 'admin');
    $upd = mysqli_prepare($conn, 'UPDATE registrasi_peserta SET is_verify = 1, user_verify = ?, tgl = NOW() WHERE id = ?');
    mysqli_stmt_bind_param($upd, 'si', $admin_ident, $id);
    mysqli_stmt_execute($upd);
    mysqli_stmt_close($upd);

    mysqli_commit($conn);
    // fetch updated verification audit fields to return to client
    $s2 = mysqli_prepare($conn, 'SELECT user_verify, tgl FROM registrasi_peserta WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($s2, 'i', $id);
    mysqli_stmt_execute($s2);
    $r2 = mysqli_stmt_get_result($s2);
    $aud = mysqli_fetch_assoc($r2);
    mysqli_stmt_close($s2);

    echo json_encode(['ok' => true, 'audit' => [
        'user_verify' => $aud['user_verify'] ?? null,
        'tgl' => $aud['tgl'] ?? null
    ]]);
    exit;
    exit;
} catch (Exception $ex) {
    mysqli_rollback($conn);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $ex->getMessage()]);
    exit;
}

?>
