<?php
require_once __DIR__ . '/../../auth.php';
require_login();
// only admin may change approval status
if (!is_admin()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Akses ditolak']);
    exit;
}
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Invalid request']);
    exit;
}

include_once __DIR__ . '/../../db/db.php';

// Bulk approve by periode
if (isset($_POST['approve_all']) && isset($_POST['periode'])) {
    $periode_raw = trim($_POST['periode']);
    // Normalize periode to YYYYMM if possible
    $digits = preg_replace('/[^0-9]/', '', $periode_raw);
    if (preg_match('/^\d{4}-\d{2}$/', $periode_raw)) {
        $periode = str_replace('-', '', $periode_raw);
    } elseif (preg_match('/^\d{6}$/', $digits)) {
        $periode = $digits;
    } else {
        // invalid format
        echo json_encode(['ok' => false, 'msg' => 'Periode tidak valid']);
        mysqli_close($conn);
        exit;
    }

    // prepared statement, optionally filter by jenis_premi
    if (isset($_POST['jenis']) && $_POST['jenis'] !== '') {
        $jenis_raw = trim($_POST['jenis']);
        $jenis = mysqli_real_escape_string($conn, $jenis_raw);
        $sql = "UPDATE data_peserta SET status_data = 1 WHERE periode = ? AND jenis_premi = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 'ss', $periode, $jenis);
            $exec = mysqli_stmt_execute($stmt);
            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            if ($exec) {
                echo json_encode(['ok' => true, 'affected' => $affected]);
            } else {
                echo json_encode(['ok' => false, 'msg' => 'Gagal approve semua peserta']);
            }
        } else {
            echo json_encode(['ok' => false, 'msg' => 'Failed to prepare statement']);
        }
    } else {
        $sql = "UPDATE data_peserta SET status_data = 1 WHERE periode = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 's', $periode);
            $exec = mysqli_stmt_execute($stmt);
            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            if ($exec) {
                echo json_encode(['ok' => true, 'affected' => $affected]);
            } else {
                echo json_encode(['ok' => false, 'msg' => 'Gagal approve semua peserta']);
            }
        } else {
            echo json_encode(['ok' => false, 'msg' => 'Failed to prepare statement']);
        }
    }
    mysqli_close($conn);
    exit;
}

// Single approve
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = isset($_POST['status']) ? (int)$_POST['status'] : 0;
if ($id < 1) {
    echo json_encode(['ok' => false, 'msg' => 'ID tidak valid']);
    mysqli_close($conn);
    exit;
}

// Use prepared statement for single update
$sql = "UPDATE data_peserta SET status_data = ? WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, 'ii', $status, $id);
    $exec = mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    if ($exec) {
        echo json_encode(['ok' => true, 'affected' => $affected]);
    } else {
        echo json_encode(['ok' => false, 'msg' => 'Gagal update status']);
    }
} else {
    echo json_encode(['ok' => false, 'msg' => 'Failed to prepare statement']);
}
mysqli_close($conn);