<?php
include_once __DIR__ . '/../../auth.php';
require_login();

$user = current_user();
// Only allow normal users (and admins editing their own profile)
if ($user['role'] !== 'user' && $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit;
}

require_once __DIR__ . '/../../db/db.php';

// read POST body (application/x-www-form-urlencoded or JSON)
$input = $_POST;
if (empty($input) && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $d = json_decode($raw, true);
    if (is_array($d)) $input = array_merge($input, $d);
}

// parse inputs
$name = trim($input['nama'] ?? '');
$email_input = array_key_exists('email', $input) ? trim($input['email']) : null; // distinguish between not-sent and empty
$no_hp = trim($input['no_hp'] ?? '');
$tgl = trim($input['tgl'] ?? '');
$nik = trim($input['nik'] ?? '');
// accept tgl_lahir separately (date of birth)
$tgl_lahir = trim($input['tgl_lahir'] ?? '');

// helper: parse common date formats and return Y-m-d or false
function parse_date_normalize($input) {
    $input = trim($input);
    if ($input === '') return false;
    $d = DateTime::createFromFormat('Y-m-d', $input);
    if ($d && $d->format('Y-m-d') === $input) return $d->format('Y-m-d');
    $formats = ['d-m-Y','d/m/Y','Y/m/d','Y.m.d','d.m.Y'];
    foreach ($formats as $f) {
        $d = DateTime::createFromFormat($f, $input);
        if ($d) return $d->format('Y-m-d');
    }
    $ts = strtotime($input);
    if ($ts !== false) return date('Y-m-d', $ts);
    return false;
}

// basic validation for name only for now; we'll handle email after determining existing value
$errors = [];
if ($name === '') $errors[] = 'Nama wajib diisi';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode('; ', $errors)]);
    exit;
}

mysqli_begin_transaction($conn);
try {
    // find registrasi_peserta row for this user by email (most recent)
    $s = mysqli_prepare($conn, 'SELECT id FROM registrasi_peserta WHERE email = ? ORDER BY id DESC LIMIT 1');
    mysqli_stmt_bind_param($s, 's', $user['email']);
    mysqli_stmt_execute($s);
    $r = mysqli_stmt_get_result($s);
    $reg = mysqli_fetch_assoc($r);

    // determine the email to use: prefer provided email only if present; otherwise keep existing
    if ($email_input !== null && $email_input !== '') {
        $email = $email_input;
        // validate provided email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // client-side should have validated, but double-check
            mysqli_rollback($conn);
            echo json_encode(['success' => false, 'message' => 'Email tidak valid']);
            exit;
        }
    } else {
        // not provided -> use existing registrasi email or user email
        $email = $reg['email'] ?? $user['email'];
    }

    // normalize tgl_lahir if provided
    $tgl_lahir_db = null;
    if ($tgl_lahir !== '') {
        $norm = parse_date_normalize($tgl_lahir);
        if ($norm === false) {
            mysqli_rollback($conn);
            echo json_encode(['success' => false, 'message' => 'Tanggal lahir tidak valid']);
            exit;
        }
        $tgl_lahir_db = $norm;
    }

    // validate phone number: only digits allowed (6-16 digits)
    if ($no_hp !== '' && !preg_match('/^[0-9]{6,16}$/', $no_hp)) {
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => 'Nomor HP tidak valid (hanya angka, 6-16 digit).']);
        exit;
    }

    if ($reg) {
        $id = $reg['id'];
    $u = mysqli_prepare($conn, 'UPDATE registrasi_peserta SET nama = ?, email = ?, no_hp = ?, tgl_lahir = ?, nik = ? WHERE id = ?');
    mysqli_stmt_bind_param($u, 'sssssi', $name, $email, $no_hp, $tgl_lahir_db, $nik, $id);
        mysqli_stmt_execute($u);
    } else {
        // if no registrasi exists, insert one (include tgl_lahir)
        $ins = mysqli_prepare($conn, 'INSERT INTO registrasi_peserta (nama, email, no_hp, tgl_lahir, nik, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
        mysqli_stmt_bind_param($ins, 'sssss', $name, $email, $no_hp, $tgl_lahir_db, $nik);
        mysqli_stmt_execute($ins);
        $id = mysqli_insert_id($conn);
    }

    // keep users table in sync if present
    if (!empty($user['id'])) {
        $up = mysqli_prepare($conn, 'UPDATE users SET name = ?, email = ? WHERE id = ?');
        mysqli_stmt_bind_param($up, 'ssi', $name, $email, $user['id']);
        mysqli_stmt_execute($up);
        // update session so header reflects new name/email immediately
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
        }
    }

    mysqli_commit($conn);

    // Return updated values
    echo json_encode(['success' => true, 'data' => ['nama' => $name, 'email' => $email, 'no_hp' => $no_hp, 'tgl_lahir' => $tgl_lahir, 'nik' => $nik], 'message' => 'Profil berhasil diperbarui']);
} catch (Exception $e) {
    mysqli_rollback($conn);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server']);
}

?>
