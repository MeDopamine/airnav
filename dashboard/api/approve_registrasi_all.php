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

// Find admin identity to store in user_verify
$admin = current_user();
$admin_name = $admin['name'] ?? null;
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
$admin_ident = $admin_name ?? ($admin['email'] ?? 'admin');

// select all registrasi_peserta that are not verified
$sel = mysqli_prepare($conn, "SELECT id, nik, email FROM registrasi_peserta WHERE is_verify IS NULL OR is_verify <> '1'");
mysqli_stmt_execute($sel);
$res = mysqli_stmt_get_result($sel);
$rows = [];
while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;

if (empty($rows)) {
    echo json_encode(['ok' => true, 'count' => 0, 'audit' => ['user_verify' => $admin_ident, 'tgl' => date('Y-m-d H:i:s')]]);
    exit;
}

mysqli_begin_transaction($conn);
try {
    $updated = 0;
    foreach ($rows as $r) {
        $id = (int)$r['id'];
        $nik = $r['nik'];
        $email = $r['email'];

        // find users row by email to get userid
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

        // if matching data_peserta exists for userid & nik, update status_data = 1
        if ($userid !== null) {
            $q = mysqli_prepare($conn, 'SELECT id FROM data_peserta WHERE userid = ? AND nik = ? ORDER BY id DESC LIMIT 1');
            if ($q) {
                mysqli_stmt_bind_param($q, 'ss', $userid, $nik);
                mysqli_stmt_execute($q);
                $rQ = mysqli_stmt_get_result($q);
                $existing = mysqli_fetch_assoc($rQ);
                mysqli_stmt_close($q);
                if ($existing && !empty($existing['id'])) {
                    $dpid = (int)$existing['id'];
                    $u = mysqli_prepare($conn, 'UPDATE data_peserta SET status_data = 1 WHERE id = ?');
                    if ($u) {
                        mysqli_stmt_bind_param($u, 'i', $dpid);
                        mysqli_stmt_execute($u);
                        mysqli_stmt_close($u);
                    }
                }
            }
        }

        // mark registrasi_peserta as verified with audit
        $upd = mysqli_prepare($conn, 'UPDATE registrasi_peserta SET is_verify = 1, user_verify = ?, tgl = NOW() WHERE id = ?');
        if ($upd) {
            mysqli_stmt_bind_param($upd, 'si', $admin_ident, $id);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);
            $updated++;
        }
    }

    mysqli_commit($conn);
    echo json_encode(['ok' => true, 'count' => $updated, 'audit' => ['user_verify' => $admin_ident, 'tgl' => date('Y-m-d H:i:s')]]);
    exit;
} catch (Exception $ex) {
    mysqli_rollback($conn);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $ex->getMessage()]);
    exit;
}

?>
