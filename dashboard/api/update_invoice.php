<?php
require_once __DIR__ . '/../../auth.php';
require_login();
if (!is_admin_or_admintl()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Akses ditolak']);
    exit;
}

include __DIR__ . '/../../db/db.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Invalid invoice id']);
    exit;
}

// Validate required fields
$errors = [];
$invoice_no = isset($_POST['invoice_no']) ? trim($_POST['invoice_no']) : '';
// $jml_premi_krywn = isset($_POST['jml_premi_krywn']) ? (float)$_POST['jml_premi_krywn'] : 0;
$jml_peserta = isset($_POST['jml_peserta']) ? (int)$_POST['jml_peserta'] : 0;
$jml_premi = isset($_POST['jml_premi']) ? (float)str_replace('.', '', $_POST['jml_premi']) : 0;
$pic = isset($_POST['pic']) ? trim($_POST['pic']) : '';
$tgl_invoice = isset($_POST['tgl_invoice']) ? trim($_POST['tgl_invoice']) : '';

if (!$invoice_no) $errors[] = 'No. Invoice wajib diisi';
// if (!$jml_premi_krywn) $errors[] = 'Jumlah Premi Karyawan wajib diisi';
if (!$jml_peserta) $errors[] = 'Jumlah Peserta wajib diisi';
if (!$jml_premi) $errors[] = 'Total Premi wajib diisi';

if (!empty($errors)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => implode(', ', $errors)]);
    exit;
}

// Update invoice
// jml_premi_krywn = " . $jml_premi_krywn . ",
$updateSql = "UPDATE invoice_airnav SET 
    invoice_no = '" . mysqli_real_escape_string($conn, $invoice_no) . "',
    jml_premi_pt = 0,
    jumlah = " . $jml_peserta . ",
    total_premi = " . $jml_premi . ",
    pic = '" . mysqli_real_escape_string($conn, $pic) . "'" .
    ($tgl_invoice ? ", printed_at = '" . mysqli_real_escape_string($conn, $tgl_invoice) . "'" : "") .
    " WHERE id = " . $id;

if (mysqli_query($conn, $updateSql)) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'msg' => 'Invoice berhasil diperbarui']);
} else {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Gagal update invoice: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
