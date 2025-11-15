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

header('Content-Type: application/json');

// Collect POST data
$periode = isset($_POST['periode']) ? trim($_POST['periode']) : '';
$bulan = isset($_POST['bulan']) ? trim($_POST['bulan']) : '';
$tahun = isset($_POST['tahun']) ? trim($_POST['tahun']) : '';
$jenis_invoice = isset($_POST['jenis_invoice']) ? (int)$_POST['jenis_invoice'] : 0;
$noinvoice = isset($_POST['noinvoice']) ? trim($_POST['noinvoice']) : '';
$tgl_invoice = isset($_POST['tgl_invoice']) ? trim($_POST['tgl_invoice']) : '';
$jml_peserta = isset($_POST['jml_peserta']) ? (int)$_POST['jml_peserta'] : 0;
$jml_premi = isset($_POST['jml_premi']) ? (float)$_POST['jml_premi'] : 0;
$pic = isset($_POST['pic']) ? trim($_POST['pic']) : '';
$idbatch = isset($_POST['idbatch']) ? $_POST['idbatch'] : '';

// Validate required fields
$errors = [];
if (!$periode) $errors[] = 'Periode wajib diisi';
if (!$jenis_invoice) $errors[] = 'Jenis Invoice wajib diisi';
if (!$noinvoice) $errors[] = 'No. Invoice wajib diisi';
if (!$tgl_invoice) $errors[] = 'Tanggal Invoice wajib diisi';
if (!$jml_peserta) $errors[] = 'Jumlah Peserta wajib diisi';
if (!$jml_premi) $errors[] = 'Total Premi wajib diisi';
if (!$pic) $errors[] = 'PIC wajib diisi';
if (!$idbatch) $errors[] = 'ID Batch wajib diisi';
if (!isset($_FILES['link_file']) || $_FILES['link_file']['error'] !== UPLOAD_ERR_OK) {
    $errors[] = 'File Invoice (PDF) wajib diunggah';
}
if (!isset($_FILES['link_peserta']) || $_FILES['link_peserta']['error'] !== UPLOAD_ERR_OK) {
    $errors[] = 'Data Peserta (Excel) wajib diunggah';
}

if (!empty($errors)) {
    echo json_encode(['ok' => false, 'msg' => implode(', ', $errors)]);
    exit;
}

// Sanitize inputs
$periode = mysqli_real_escape_string($conn, $periode);
$bulan = mysqli_real_escape_string($conn, $bulan);
$tahun = mysqli_real_escape_string($conn, $tahun);
$noinvoice = mysqli_real_escape_string($conn, $noinvoice);
$tgl_invoice = mysqli_real_escape_string($conn, $tgl_invoice);
$pic = mysqli_real_escape_string($conn, $pic);


// Handle file uploads (optional)
$link_file = '';
$link_peserta = '';

// Create uploads directory if not exists
$upload_dir = __DIR__ . '/../../uploads/invoices/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Handle PDF invoice file upload
if (isset($_FILES['link_file']) && $_FILES['link_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['link_file'];
    $allowed_pdf = ['application/pdf'];
    $mime_type = mime_content_type($file['tmp_name']);

    if (in_array($mime_type, $allowed_pdf)) {
        // Generate unique filename: periode_jenis_invoice_timestamp.pdf
        $filename = $periode . '_' . $jenis_invoice . '_' . time() . '.pdf';
        $file_path = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            $link_file = 'uploads/invoices/' . $filename;
        }
    }
}

// Handle Excel peserta file upload
if (isset($_FILES['link_peserta']) && $_FILES['link_peserta']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['link_peserta'];
    $allowed_excel = ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    $mime_type = mime_content_type($file['tmp_name']);

    if (in_array($mime_type, $allowed_excel)) {
        // Generate unique filename: periode_jenis_invoice_peserta_timestamp.xlsx
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $periode . '_' . $jenis_invoice . '_peserta_' . time() . '.' . $ext;
        $file_path = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            $link_peserta = 'uploads/invoices/' . $filename;
        }
    }
}

// Insert into invoice_airnav table
// Using mapped field names to match database schema
// Note: we do NOT add new columns; uploaded peserta files are stored on disk in uploads/invoices/
$sql = "INSERT INTO invoice_airnav (periode, jenis_premi, invoice_no, jml_premi_krywn, total_premi, jumlah, pic, flag, idbatch, created_at, urlinvoice) ";
$sql .= "VALUES ('$periode', $jenis_invoice, '$noinvoice', $jml_premi, $jml_premi, $jml_peserta, '$pic', 0, '$idbatch', '$tgl_invoice', '$link_file')";

if (mysqli_query($conn, $sql)) {
    $invoice_id = mysqli_insert_id($conn);
    echo json_encode(['ok' => true, 'msg' => 'Invoice berhasil ditambahkan', 'id' => $invoice_id]);
} else {
    echo json_encode(['ok' => false, 'msg' => 'Database error: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
